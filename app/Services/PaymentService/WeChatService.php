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
use NiZerin\AliPayGlobal;
use NiZerin\Model\CustomerBelongsTo;
use NiZerin\Model\TerminalType;
use Str;
use Throwable;
use WeChatPay\Builder;
use WeChatPay\Crypto\Rsa;
use WeChatPay\Util\PemUtil;

class WeChatService extends BaseService
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

        $order          = Order::first();
        $totalPrice     = ceil($order->rate_total_price * 2 * 100) / 2;

        $order->update([
            'total_price' => ($totalPrice / $order->rate) / 100
        ]);

        $host               = request()->getSchemeAndHttpHost();
        $currency           = Str::upper($order->currency?->title ?? data_get($payload, 'currency'));

        $merchantId = '190000****';

        $merchantPrivateKeyFilePath = 'MIIEvAIBADANBgkqhkiG9w0BAQEFAASCBKYwggSiAgEAAoIBAQCXDvRJL3wAv2pvMZJS1F5XNoS9hHK4oDj2mJecQZMHs060vC3rQOmeMPFxfWoSPSq7Ugno/OgC3OUOoZvsHx0ePGJYd02RdlXZL4f3FjZsZwV8atw9kAZHNalbhY9baKO0V0h8cjzdUa9iDWtMHB5vStDJ25jCDlgR4lVk1C9evOhbB4mZ+s2k4+/J/qxURaRZa4gxPyEeTRCXCrOczGZB1JvNepWhlotRw6QgHn4VLuyR/3wXWkOA9J5zqjhlxKhXCZdVunUc2IZU0y9AcIIKZrk8opYpRZNH5O4+Wwd4aVexGuRsllcAtTUn+Egl5kBVT6WBXc0/Zn0mquyLlNspAgMBAAECggEAdic3QitHBdqy6IhQmEMOC49UIlx/0xNXmuJd69WKqIJCtLFgBVu/n4FOyOM83UlErEIOCFQRMXQQIfKcYAMyJl0621FttbJmkbtQ0R5psT6fluKKpAiMMJSzhCeiqu/c5AlFZDmCi+YBlWNDosN3trtBNjJyeI75qftrqbMh3ioS3gzPOc1oVNOWyr580iwNc2ALrhrac2lbBuC5A8ORXLUAUtM1YEOf5my8RSfE/yW6Af/NXwVG2JR+NmVdpAiNfUTAG7yH8kJLGq2ep9d2lChHGaZtBWGS1gCnai8IPgVCACOFP+4+pkeTSKNONRLPN8DyJnsFvX2Al4L6KIXQUQKBgQDmdC5TU6Nt1eOMPbQCUD9BePip0eDENr6e7SWJQxjuZWt18VRTHfqQDf+qMM5NdS2GP0JPH1xgWiyMAEeFPG/bpAiv6F7yGIfP+8REGFVZbdJRzPbtN+FiDTR7yPFWCicUQEuMu//NNKvqakuMLRgMT1Zg+uzqHYQIrTFYcOBrzQKBgQCnzbCosL3Ko+DX5dGPgk4c19JN/mlVAfIpumqv2ArojON/OW+GylcMPT7AZh8aSDTaRXxXTyUJTT++2xqlQENRXLCuxbGrtFWZgytjU4D4YYkn+ihtjLaeNib2vKLCCmfCN7chIzWBh3yhuKH/cdI7YbqDTugBFEEuhI3KhaCozQKBgE+spz+D0SLuKeeYhZ2vJM98BWyg9TahPrIvhyS3n+z7/3UdZGwAF1qqnFO43/qDoqOhR0mXrBZb1r7ocdGsnXewdJhsnDbTKFFN2AM67ncmsuo5FL3a7f86VYTeaiG3DN/Bgt07Ois2JKG88jWaeY/39gM9fZ9LaRSe3EqZa92ZAoGAQ6mDBGJQBTfTX/sBZzaJvMOhv2VIn8hrFzxd7I3WyDKXQSOtvtI0C3FerkH/ZJ+dAC5oluQI8Rk/DPxYYC3rdxFDBRYeMDhFE+N6SVDQflcF8SLDznig4ma/i1pA3rFHaV8B7tC9sH8rWCKU3+XLebpMdMoKbvT124YDjpgXUjkCgYBYmwwm5RYe2wEp9LHabXA91SArtfa2DkgSNPVcE1YJTgIK4Ubo88+zrhq84puIiPsrYocwMTm+P7XIEKddqpv8XTlGwFq9M5j5YzQqE9ukkze2UR7o9qlniUTL+aKgfAVY2i9pSrMYELLEXY6AXV+XWrVwqEhccgzPFtAESne8bA==';
        $merchantPrivateKeyInstance = Rsa::from($merchantPrivateKeyFilePath, Rsa::KEY_TYPE_PRIVATE);

        $merchantCertificateSerial = '3775B6A45ACD588826D15E583A95F5DD********';

        $platformCertificateFilePath = 'file:///path/to/wechatpay/cert.pem';
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
