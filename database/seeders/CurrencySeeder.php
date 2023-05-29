<?php

namespace Database\Seeders;

use App\Models\Currency;
use App\Traits\Loggable;
use Illuminate\Database\Seeder;
use Throwable;

class CurrencySeeder extends Seeder
{
    use Loggable;

    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run(): void
    {
        $currencies = [
            [
                'id' => 2,
                'symbol' => '$',
                'title' => 'USD',
                'rate' => 1.0,
                'default' => 1,
                'active' => 1,
                'deleted_at' => null
            ]
        ];

        foreach ($currencies as $currency) {
            try {
                Currency::withTrashed()->updateOrInsert(['id' => $currency['id']], $currency);
            } catch (Throwable $e) {
                $this->error($e);
            }
        }

    }
}
