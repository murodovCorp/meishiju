<?php

namespace App\Services\PaymentService;

use App\Models\Order;
use App\Models\Payment;
use App\Models\PaymentPayload;
use App\Models\PaymentProcess;
use App\Models\Payout;
use App\Models\Shop;
use App\Models\Subscription;
use Exception;
use Illuminate\Database\Eloquent\Model;
use Log;
use MercadoPago\Config;
use MercadoPago\Item;
use MercadoPago\Preference;
use MercadoPago\SDK;
use Throwable;

class MercadoPagoService extends BaseService
{
    protected function getModelClass(): string
    {
        return Payout::class;
    }

    /**
     * @param array $data
     * @return PaymentProcess|Model
     * @throws Throwable
     */
    public function orderProcessTransaction(array $data): Model|PaymentProcess
    {
        $payment        = Payment::where('tag', 'mercadoPago')->first();

        $paymentPayload = PaymentPayload::where('payment_id', $payment?->id)->first();
        $payload        = $paymentPayload?->payload;

        $order          = Order::find(data_get($data, 'order_id'));
        $totalPrice     = ceil($order->rate_total_price * 2 * 100) / 2;

        $order->update([
            'total_price' => ($totalPrice / $order->rate) / 100
        ]);

        $host = request()->getSchemeAndHttpHost();

        $token = data_get($payload, 'token', 'TEST-6668869803819899-030119-21a35cc741cf11c0619a6eec892e3465-96344171');

        SDK::setAccessToken($token);

        $config = new Config();
        $config->set('sandbox', (bool)data_get($payload, 'sandbox', true));
        $config->set('access_token', $token);

        $trxRef = "$order->id-" . time();

        $item               = new Item;
        $item->id           = $trxRef;
        $item->title        = $order->id;
        $item->quantity     = $order->order_details_sum_quantity;
        $item->unit_price   = $order->order_details_sum_total_price;

        $preference             = new Preference;
        $preference->items      = [$item];
        $preference->back_urls  = [
            'success' => "$host/order-stripe-success?order_id=$order->id",
            'failure' => "$host/order-stripe-success?order_id=$order->id",
            'pending' => "$host/order-stripe-success?order_id=$order->id"
        ];

        $preference->auto_return = 'approved';

        $preference->save();

        $payment_link = $preference->init_point;

        Log::info('$preference', [$preference]);

        if (!$payment_link) {
            throw new Exception('ERROR IN MERCADO PAGO');
        }

        return PaymentProcess::updateOrCreate([
            'user_id'   => auth('sanctum')->id(),
            'order_id'  => data_get($data, 'order_id'),
        ], [
            'id'    => $trxRef,
            'data'  => [
                'url'       => $payment_link,
                'price'     => $totalPrice,
                'order_id'  => $order->id
            ]
        ]);
    }

    /**
     * @param array $data
     * @param Shop $shop
     * @param $currency
     * @return Model|array|PaymentProcess
     * @throws Exception
     */
    public function subscriptionProcessTransaction(array $data, Shop $shop, $currency): Model|array|PaymentProcess
    {
        $payment = Payment::where('tag', 'stripe')->first();

        $paymentPayload = PaymentPayload::where('payment_id', $payment?->id)->first();
        $payload        = $paymentPayload?->payload;

        $host           = request()->getSchemeAndHttpHost();

        /** @var Subscription $subscription */
        $subscription   = Subscription::find(data_get($data, 'subscription_id'));

        $token = data_get($payload, 'token', 'TEST-6668869803819899-030119-21a35cc741cf11c0619a6eec892e3465-96344171');

        SDK::setAccessToken($token);

        $config = new Config();
        $config->set('sandbox', (bool)data_get($payload, 'sandbox', true));
        $config->set('access_token', $token);

        $trxRef = "$subscription->id-" . time();

        $item               = new Item;
        $item->id           = $trxRef;
        $item->title        = $subscription->id;
        $item->quantity     = 1;
        $item->unit_price   = ceil($subscription->price) * 100;

        $preference = new Preference;
        $preference->items = [$item];
        $preference->back_urls = [
            'success' => "$host/subscription-stripe-success?subscription_id=$subscription->id",
            'failure' => "$host/subscription-stripe-success?subscription_id=$subscription->id",
            'pending' => "$host/subscription-stripe-success?subscription_id=$subscription->id"
        ];

        $preference->auto_return = 'approved';

        $preference->save();

        $payment_link = $preference->init_point;

        if (!$payment_link) {
            throw new Exception('ERROR IN MERCADO PAGO');
        }

        return PaymentProcess::updateOrCreate([
            'user_id'   => auth('sanctum')->id(),
            'order_id'  => data_get($data, 'order_id'),
        ], [
            'id'    => $trxRef,
            'data'  => [
                'url'       => $payment_link,
                'price'     => ceil($subscription->price) * 100,
                'order_id'  => $subscription->id
            ]
        ]);
    }
}
