<?php

namespace Database\Seeders;

use App\Models\Unit;
use App\Models\UnitTranslation;
use App\Traits\Loggable;
use Illuminate\Database\Seeder;
use Throwable;

class UnitSeeder extends Seeder
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
            // Units
            $units = [
                [
                    'id'         => 1,
                    'active'     => 1,
                    'position'   => 'after',
                    'created_at' => now(),
                    'updated_at' => now(),
                ],
            ];

            foreach ($units as $unit) {
                Unit::updateOrInsert(['id' => $unit['id']], $unit);
            }

            // Unit Languages
            $unitLangs = [
                [
                    'id' => 1,
                    'unit_id' => 1,
                    'locale' => 'en',
                    'title' => 'Quantity',
                ],
            ];

            foreach ($unitLangs as $lang) {
                UnitTranslation::updateOrInsert(['id' => $lang['id']], $lang);
            }
        } catch (Throwable $e) {
            $this->error($e);
        }
    }
}
