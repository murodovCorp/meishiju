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
use Http;
use Illuminate\Database\Eloquent\Model;
use Str;
use Throwable;

class AliPayService extends BaseService
{
    private array $currencies =  [
        'AUD' => 'en-AU',
        'EUR' => 'en-AT',
        'CAD' => 'en-CA',
        'CZK' => 'en-CZ',
        'DKK' => 'en-DK',
        'MXN' => 'en-MX',
        'NOK' => 'en-NO',
        'PLN' => 'en-PL',
        'RON' => 'en-RO',
        'SEK' => 'en-SE',
        'CHF' => 'en-CH',
        'GBP' => 'en-GB',
        'USD' => 'en-US',
    ];

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
        $payment        = Payment::where('tag', 'klarna')->first();

        $paymentPayload = PaymentPayload::where('payment_id', $payment?->id)->first();
        $payload        = $paymentPayload?->payload;

        $order          = Order::find(data_get($data, 'order_id'));
        $totalPrice     = ceil($order->rate_total_price * 2 * 100) / 2;

        $order->update([
            'total_price' => ($totalPrice / $order->rate) / 100
        ]);

        $host               = request()->getSchemeAndHttpHost();
        $method             = 'POST';
        $path               = 'https://open-na.alipay.com/ams/api/v1/payments/pay';
        $clientId           = '';
        $reqTime            = now()->format('Y-m-d\TH:i:sp');
        $paymentRequestId   = Str::uuid();
        $referenceOrderId   = "ORDER_$paymentRequestId";
        $currency           = Str::upper($order->currency?->title ?? data_get($payload, 'currency'));
        $privateKey         = '';
        $signature          = $this->sign($method, $path, $clientId, $reqTime, json_encode($data), $privateKey);

        $headers = [
            'Content-Type'  => 'application/json; charset=UTF-8',
            'client-id'     => $clientId,
            'request-time'  => $reqTime,
            'signature'     => "algorithm=RSA256,keyVersion=1,signature=$signature",
        ];

        if (empty(data_get($this->currencies, $currency))) {
            throw new Exception('We supported only this currencies:' . implode(', ', array_keys($this->currencies)));
        }

        $data = [
            'order' => [
                'orderAmount' => [
                   'currency'   => $currency,
                   'value'      => $totalPrice
                ],
                'orderDescription' => 'Cappuccino #grande (Mika\'s coffee shop)',
                'referenceOrderId' => $referenceOrderId,
                'env' => [
                    'terminalType' => 'WEB'
                ],
//                'merchant' => [
//                   'referenceMerchantId' => 'SM_001'
//                ],
                'extendInfo' => [
                    'chinaExtraTransInfo' => [
                        'totalQuantity'     => '1',
                        'otherBusinessType' => 'food',
                        'businessType'      => '1',
                        'goodsInfo'         => '1'
                    ]
                ]
            ],
            'paymentAmount' => [
                'currency'  => $currency,
                'value'     => $totalPrice
            ],
            'paymentMethod'         => [
                'paymentMethodType' => 'ALIPAY_CN'
            ],
            'settlementStrategy'    => [
                'settlementCurrency' => 'USD'
            ],
            'paymentNotifyUrl'   => "$host/api/v1/order-alipay-success?order_id=$order->id",
            'paymentRedirectUrl' => "$host/api/v1/order-alipay-success?order_id=$order->id",
            'paymentRequestId'   => $paymentRequestId,
            'productCode'        => 'CASHIER_PAYMENT'
        ];

        $username = data_get($payload, 'username', 'PK73219_7ae80a1edb3e');
        $password = data_get($payload, 'password', '5mKYHxpNOIYnqB3e');

        $orders = Http::withHeaders($headers)
            ->withBasicAuth($username, $password)
            ->post('https://open-na.alipay.com/ams/api/v1/payments/pay', $data);

        dd($orders->json(), $orders->status(), $orders->headers());

        if ($orders->status() > 299) {
            $message = 'Error in Klarna';

            if ($orders->status() === 401) {
                $message = 'Auth failed';
            }

            $errorMessages = $orders->json('error_messages');

            throw new Exception($errorMessages ? implode(', ', $errorMessages): $message);
        }

        return PaymentProcess::updateOrCreate([
            'user_id'   => auth('sanctum')->id(),
            'order_id'  => $order->id,
        ], [
            'id'    => $ordersId,
            'data'  => [
                'url'       => $url,
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
        $payment = Payment::where('tag', 'alipay')->first();

        $paymentPayload = PaymentPayload::where('payment_id', $payment?->id)->first();
        $payload        = $paymentPayload?->payload;

        $host           = request()->getSchemeAndHttpHost();

        /** @var Subscription $subscription */
        $subscription   = Subscription::find(data_get($data, 'subscription_id'));

        $headers = [
            'Accept' => 'application/json',
            'Content-Type' => 'application/json',
            'Authorization' => 'Bearer ' . data_get($payload, 'flw_sk')
        ];

        $trxRef = "$subscription->id-" . time();

        $data = [
            'tx_ref'            => $trxRef,
            'amount'            => 100,
            'currency'          => Str::lower(data_get($payload, 'currency', $currency)),
            'payment_options'   => 'card,account,ussd,mobilemoneyghana',
            'redirect_url'      => "$host/subscription-stripe-success?subscription_id=$subscription->id",
            'customer'          => [
                'name'          => "{$shop->seller?->firstname} {$shop->seller?->lastname}",
                'phonenumber'   => $shop->seller?->phone,
                'email'         => $shop->seller?->email
            ],
            'customizations'    => [
                'title'         => data_get($payload, 'title', ''),
                'description'   => data_get($payload, 'description', ''),
                'logo'          => data_get($payload, 'logo', ''),
            ]
        ];

        $request = Http::withHeaders($headers)->post('https://api.flutterwave.com/v3/payments', $data);

        $body    = json_decode($request->body());

        if (data_get($body, 'status') === 'error') {
            throw new Exception(data_get($body, 'message'));
        }

        return PaymentProcess::updateOrCreate([
            'user_id'   => auth('sanctum')->id(),
            'order_id'  => data_get($data, 'order_id'),
        ], [
            'id'    => $trxRef,
            'data'  => [
                'url'               => $body,
                'price'             => ceil($subscription->price) * 100,
                'shop_id'           => $shop->id,
                'subscription_id'   => $subscription->id
            ]
        ]);
    }

    static public function sign(
        string $httpMethod,
        string $path,
        string $clientId,
        string $reqTime,
        string $content,
        string $merchantPrivateKey
    ): string
    {
        $signContent = self::genSignContent($httpMethod, $path, $clientId, $reqTime, $content);

        $signValue   = self::signWithSHA256RSA($signContent, $merchantPrivateKey);

        return urlencode($signValue);
    }

    static public function verify($httpMethod, $path, $clientId, $rspTime, $rspBody, $signature, $alipayPublicKey): bool|int
    {
        $rspContent = self::genSignContent($httpMethod, $path, $clientId, $rspTime, $rspBody);
        return self::verifySignatureWithSHA256RSA($rspContent, $signature, $alipayPublicKey);
    }

    static private function genSignContent($httpMethod, $path, $clientId, $timeString, $content): string
    {
        return "$httpMethod $path\n$clientId.$timeString.$content";
    }

    static private function signWithSHA256RSA($signContent, $merchantPrivateKey): string
    {
        $priKey = "-----BEGIN RSA PRIVATE KEY-----\n" .
            wordwrap($merchantPrivateKey, 64, "\n", true) .
            "\n-----END RSA PRIVATE KEY-----";

        openssl_sign($signContent, $signValue, $priKey, OPENSSL_ALGO_SHA256);

        return base64_encode($signValue);
    }

    static private function verifySignatureWithSHA256RSA($rspContent, $rspSignValue, $alipayPublicKey): bool|int
    {
        $pubKey = "-----BEGIN PUBLIC KEY-----\n" .
            wordwrap($alipayPublicKey, 64, "\n", true) .
            "\n-----END PUBLIC KEY-----";

        $originalRspSignValue = base64_decode(urldecode($rspSignValue));

        if(
            strstr($rspSignValue, "=") ||
            strstr($rspSignValue, "+") ||
            strstr($rspSignValue, "/") ||
            $rspSignValue == base64_encode(base64_decode($rspSignValue))
        ) {
            $originalRspSignValue = base64_decode($rspSignValue);
        }

        return openssl_verify($rspContent, $originalRspSignValue, $pubKey, OPENSSL_ALGO_SHA256);
    }

}
