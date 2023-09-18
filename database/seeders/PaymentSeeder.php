<?php

namespace Database\Seeders;

use App\Models\Payment;
use App\Traits\Loggable;
use Illuminate\Database\Seeder;
use Throwable;

class PaymentSeeder extends Seeder
{
    use Loggable;

    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run(): void
    {
        $payments = [
            ['tag' => 'cash',         'input' => 1],
            ['tag' => 'wallet',       'input' => 2],
            ['tag' => 'paytabs',      'input' => 3],
            ['tag' => 'flutterWave',  'input' => 4],
            ['tag' => 'paystack',     'input' => 5],
            ['tag' => 'mercado-pago', 'input' => 6],
            ['tag' => 'razorpay',     'input' => 7],
            ['tag' => 'stripe',       'input' => 8],
            ['tag' => 'paypal',       'input' => 9],
        ];

        foreach ($payments as $payment) {
            try {
                Payment::updateOrCreate([
                    'tag'   => data_get($payment, 'tag')
                ], [
                    'input' => data_get($payment, 'input')
                ]);
            } catch (Throwable $e) {
                $this->error($e);
            }
        }

    }

}
