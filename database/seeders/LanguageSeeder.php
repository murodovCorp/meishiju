<?php

namespace Database\Seeders;

use App\Models\Language;
use Illuminate\Database\Seeder;

class LanguageSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run(): void
    {
        $languages = [
            [
                'locale' => 'en',
                'title' => 'English',
                'default' => 1,
                'deleted_at' => null
            ]
        ];

        foreach ($languages as $language) {

            Language::withTrashed()
                ->updateOrInsert(['locale' => $language['locale']], $language);
        }
    }
}
