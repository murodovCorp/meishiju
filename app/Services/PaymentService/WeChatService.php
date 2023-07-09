<?php

namespace App\Services\PaymentService;
require 'vendor/autoload.php';

use App\Models\Order;
use App\Models\Payment;
use App\Models\PaymentPayload;
use App\Models\PaymentProcess;
use App\Models\Payout;
use App\Models\Shop;
use App\Models\Subscription;
use Exception;
use GuzzleHttp\Exception\RequestException;
use Http;
use Illuminate\Database\Eloquent\Model;
use Str;
use Throwable;
use WeChatPay\Builder;
use WeChatPay\Crypto\Rsa;
use WeChatPay\Util\PemUtil;

class WeChatService
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

    public function run(): void {
        $this->orderProcessTransaction([]);
    }
    /**
     * @param array $data
     * @return PaymentProcess|Model
     * @throws Throwable
     */
    public function orderProcessTransaction(array $data): Model|PaymentProcess
    {
//        $payment        = Payment::where('tag', 'klarna')->first();
//
//        $paymentPayload = PaymentPayload::where('payment_id', $payment?->id)->first();
//        $payload        = $paymentPayload?->payload;
//
//        $order          = Order::first();
//        $totalPrice     = ceil($order->rate_total_price * 2 * 100) / 2;
//
//        $order->update([
//            'total_price' => ($totalPrice / $order->rate) / 100
//        ]);

//        $host               = request()->getSchemeAndHttpHost();
//        $currency           = Str::upper($order->currency?->title ?? data_get($payload, 'currency'));

        $merchantId = '1577963751';

        $merchantPrivateKeyFilePath = file_get_contents('public/wechat/apiclient_key.pem');
        $merchantPrivateKeyInstance = Rsa::from($merchantPrivateKeyFilePath, Rsa::KEY_TYPE_PRIVATE);

        $merchantCertificateSerial = 'HLptcPSOUM95cpILuyio9awKxaud7aa7';

        $platformCertificateFilePath = file_get_contents('public/wechat/apiclient_cert.pem');
        $platformPublicKeyInstance = Rsa::from($platformCertificateFilePath, Rsa::KEY_TYPE_PUBLIC);

        $platformCertificateSerial = PemUtil::parseCertificateSerialNo($platformCertificateFilePath);

        $instance = Builder::factory([
            'mchid'      => $merchantId,
            'serial'     => $merchantCertificateSerial,
            'privateKey' => $merchantPrivateKeyInstance,
            'certs'      => [
                $platformCertificateSerial => $platformPublicKeyInstance,
            ],
        ]);

        $resp = $instance->chain('v3/certificates')->get([
            'debug' => true
        ]);

        dd($resp->getBody());
        try {
            $resp = $instance
                ->chain('v3/pay/transactions/native')
                ->post(['json' => [
                    'mchid'        => $merchantId,
                    'out_trade_no' => Str::uuid(),
                    'appid'        => 'wxfd853ee7ec0c59ef',
                    'description'  => 'Image形象店-深圳腾大-QQ公仔',
                    'notify_url'   => 'https://waimaiapi.meishiju.co/api/v1/webhook/we-chat/payment',
                    'amount'       => [
                        'total'    => 1,
                        'currency' => 'CNY'
                    ],
                ]]);

            dd(
                $resp->getStatusCode(),
                $resp->getBody(),
                $resp->getReasonPhrase(),
                $resp->getHeaders(),
            );

        } catch (Throwable|RequestException $e) {
            dd(
                $e->getMessage(),
                $e->getFile(),
                $e->getLine(),
                $e->getTrace(),
                $e instanceof RequestException && $e->hasResponse() ? $e->getResponse() : null,
                $e->getResponse()->getStatusCode(),
                $e->getResponse()->getReasonPhrase(),
                $e->getResponse()->getBody(),
                $e->getResponse()->getHeaders(),
            );
        }


        return PaymentProcess::updateOrCreate([
            'user_id'   => auth('sanctum')->id(),
            'order_id'  => $order->id,
        ], [
            'id'    => data_get($result, 'paymentId'),
            'data'  => [
                'url'       => data_get($result, 'normalUrl'),
                'price'     => $totalPrice,
                'order_id'  => $order->id,
                'paymentRequestId' => data_get($result, 'paymentRequestId')
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
(new WeChatService())->run();
