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
            ['tag' => 'stripe',         'input' => 10],
            ['tag' => 'razorpay',       'input' => 9],
            ['tag' => 'mercado-pago',   'input' => 8],
            ['tag' => 'paystack',       'input' => 7],
            ['tag' => 'flutterWave',    'input' => 6],
            ['tag' => 'paytabs',        'input' => 5],
            ['tag' => 'wallet',         'input' => 4],
            ['tag' => 'we-chat',        'input' => 3],
            ['tag' => 'alipay',         'input' => 2],
            ['tag' => 'cash',           'input' => 1],
        ];

        foreach ($payments as $payment) {
            try {
                Payment::updateOrCreate([
                    'tag' => data_get($payment, 'tag')
                ], $payment);
            } catch (Throwable $e) {
                $this->error($e);
            }
        }

    }

}
