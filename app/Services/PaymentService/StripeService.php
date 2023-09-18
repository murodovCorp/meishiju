<?php

namespace App\Services\PaymentService;

use App\Models\Order;
use App\Models\ParcelOrder;
use App\Models\Payment;
use App\Models\PaymentPayload;
use App\Models\PaymentProcess;
use App\Models\Payout;
use App\Models\Shop;
use App\Models\Subscription;
use Illuminate\Database\Eloquent\Model;
use Str;
use Stripe\Checkout\Session;
use Stripe\Exception\ApiErrorException;
use Stripe\Stripe;

class StripeService extends BaseService
{
    protected function getModelClass(): string
    {
        return Payout::class;
    }

    /**
     * @param array $data
     * @param array $types
     * @return PaymentProcess|Model
     * @throws ApiErrorException
     */
    public function orderProcessTransaction(array $data, array $types = ['card']): Model|PaymentProcess
    {
        $payment = Payment::where('tag', 'stripe')->first();

        $paymentPayload = PaymentPayload::where('payment_id', $payment?->id)->first();
        $payload        = $paymentPayload?->payload;

        Stripe::setApiKey(data_get($payload, 'stripe_sk'));

        /** @var ParcelOrder $order */
        $order = data_get($data, 'parcel_id')
            ? ParcelOrder::find(data_get($data, 'parcel_id'))
            : Order::find(data_get($data, 'order_id'));

        $totalPrice = ceil($order->rate_total_price * 100);

        $order->update([
            'total_price' => ($totalPrice / $order->rate) / 100
        ]);

        $host = request()->getSchemeAndHttpHost();
        $url  = "$host/order-stripe-success?token={CHECKOUT_SESSION_ID}&" . (
            data_get($data, 'parcel_id') ? "parcel_id=$order->id" : "order_id=$order->id"
        );

        $session = Session::create([
            'payment_method_types' => $types,
            'currency' => Str::lower($order->currency?->title ?? data_get($payload, 'currency')),
            'line_items' => [
                [
                    'price_data' => [
                        'currency' => Str::lower($order->currency?->title ?? data_get($payload, 'currency')),
                        'product_data' => [
                            'name' => 'Payment'
                        ],
                        'unit_amount' => $totalPrice,
                    ],
                    'quantity' => 1,
                ]
            ],
            'mode'        => 'payment',
            'success_url' => $url,
            'cancel_url'  => $url,
        ]);

        return PaymentProcess::updateOrCreate([
            'user_id'    => auth('sanctum')->id(),
            'model_id'   => $order->id,
            'model_type' => get_class($order)
        ], [
            'id' => $session->payment_intent ?? $session->id,
            'data' => [
                'url'      => $session->url,
                'price'    => $totalPrice,
            ]
        ]);
    }

    /**
     * @param array $data
     * @param Shop $shop
     * @param $currency
     * @param array $types
     * @return Model|array|PaymentProcess
     * @throws ApiErrorException
     */
    public function subscriptionProcessTransaction(array $data, Shop $shop, $currency, array $types = ['card']): Model|array|PaymentProcess
    {
        $payment = Payment::where('tag', 'stripe')->first();

        $paymentPayload = PaymentPayload::where('payment_id', $payment?->id)->first();

        Stripe::setApiKey(data_get($paymentPayload?->payload, 'stripe_sk'));

        $host           = request()->getSchemeAndHttpHost();
        $subscription   = Subscription::find(data_get($data, 'subscription_id'));

        $session = Session::create([
            'payment_method_types' => $types,
            'line_items' => [
                [
                    'price_data' => [
                        'currency' => Str::lower(data_get($paymentPayload?->payload, 'currency', $currency)),
                        'product_data' => [
                            'name' => 'Payment'
                        ],
                        'unit_amount' => ceil($subscription->price) * 100,
                    ],
                    'quantity' => 1,
                ]
            ],
            'mode' => 'payment',
            'success_url' => "$host/subscription-stripe-success?token={CHECKOUT_SESSION_ID}&subscription_id=$subscription->id",
            'cancel_url' => "$host/subscription-stripe-success?token={CHECKOUT_SESSION_ID}&subscription_id=$subscription->id",
        ]);

        return PaymentProcess::updateOrCreate([
            'user_id'    => auth('sanctum')->id(),
			'model_id'   => $subscription->id,
			'model_type' => get_class($subscription)
        ], [
            'id' => $session->payment_intent ?? $session->id,
            'data' => [
                'url'             => $session->url,
                'price'           => ceil($subscription->price) * 100,
                'shop_id'         => $shop->id,
                'subscription_id' => $subscription->id,
            ]
        ]);
    }
}
