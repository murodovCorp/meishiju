<?php

namespace Database\Seeders;

use App\Models\Translation;
use App\Traits\Loggable;
use DB;
use Illuminate\Database\Seeder;
use Throwable;

class TranslationSeeder extends Seeder
{
    use Loggable;
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run(): void
    {
        try {
            if (file_exists(resource_path('lang/translations.php'))) {
                $this->byArray();
            } else if(file_exists(resource_path('lang/translations_en.sql'))) {
                $this->bySql();
            }
        } catch (Throwable $e) {
            $this->error($e);
        }
    }

    private function bySql() {
        Translation::truncate();
        DB::unprepared(file_get_contents(resource_path('lang/translations_en.sql')));
    }

    private function byArray() {

        $translations = include_once 'resources/lang/translations.php';

        foreach ($translations as $translation) {
            try {
                Translation::firstOrCreate([
                    'locale'    => $translation['locale'],
                    'group'     => $translation['group'],
                    'key'       => $translation['key'],
                ], [
                    'value'     => $translation['value']
                ]);
            } catch (Throwable $e) {
                $this->error($e);
            }
        }

    }
}
