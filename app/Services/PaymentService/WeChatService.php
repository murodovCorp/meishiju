<?php

namespace App\Services\PaymentService;
//require 'vendor/autoload.php';

use App\Helpers\AesUtil;
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

    public function generateSalt($length = 10): string
    {
        $chars    = '1234567890qwertyuiopasdfghjklzxcvbnmQWERTYUIOPASDFGHJKLZXCVBNM';
        $char_len = strlen($chars) - 1;
        $output   = '';

        while (strlen($output) < $length) {
            $output .= $chars[ rand(0, $char_len) ];
        }

        return $output;
    }

    public function getCertificate(): array {

        $timestamp      = time();
        $nonce          = $this->generateSalt(22);
        $body           = '';
        $url_parts      = parse_url('https://api.mch.weixin.qq.com/v3/certificates');
        $canonical_url  = ($url_parts['path'] . (!empty($url_parts['query']) ? "?${url_parts['query']}" : ""));
        $schema         = 'WECHATPAY2-SHA256-RSA2048';

        $message = 'GET'."\n". $canonical_url."\n". $timestamp."\n". $nonce."\n". $body."\n";

        openssl_sign(
            $message,
            $rawSign,
            file_get_contents('storage/wechat/apiclient_key.pem'),
            'sha256WithRSAEncryption'
        );

        $sign   = base64_encode($rawSign);
        $token  = sprintf('mchid="%s",nonce_str="%s",timestamp="%d",serial_no="%s",signature="%s"',
            '1577963751', $nonce, $timestamp, '766D55E9A5223A06DAD2758B4D72829DE4598219', $sign);

        $request = Http::withHeaders([
            'Authorization' => $schema . ' ' . $token,
            'User-Agent'    => $_SERVER['HTTP_USER_AGENT'],
            'Accept'    => $_SERVER['HTTP_ACCEPT'],
        ])->get('https://api.mch.weixin.qq.com/v3/certificates');

        return $request->json();

//        array:1 [
//            "data" => array:1 [
//            0 => array:4 [
//            "effective_time" => "2020-02-29T22:58:58+08:00"
//      "encrypt_certificate" => array:4 [
//            "algorithm" => "AEAD_AES_256_GCM"
//        "associated_data" => "certificate"
//        "ciphertext" => "ZPlXRz61rQPTgph3JVsQ1ZQsXQq6HvHpn5XOGhGOSylINjcLE7peVmjBTlILe0HMoa987oI1ORkk/Nubi6jjT3sO6K+HqWQoerAgWJPdhB/y6TxbYEKBHeoPKM2iTk6K6gTR4ymnioZUiRjVL2gJuCNxyCi8+0WSoEivZRq/n1aFwrQQfocaBNNs9XNNM+S6pFkS9JY6zEomFpZrBfQq/k0KvayB9XG3N2x3wB35GnbSl59xBUmaT6MG2Sh86sNluSDIOtaVEf2PckYMlvUp5OsV8TCzqbD6EGoyQiaEq/SUmCEpSsyZf3Nyqw1C7ZSjvbHVn0R9rMY0Yw6R8EVbN4A6TycehDn/F1CPPIf+HscXeRaiN+VCd1/hz2uiJRKQhpUJD7tWYAbQINdvQjb+ZSaWW10ZzwtADSbo/RAiiDnUV3jlD9AUH8YE0nr7jbX/MBE69wHOP6peoC1QE/+nDFXL8Ai+e7BVmVQoLqICQHZymsJcuwinG3losTM7bkgrwe6L+CpI8DmRQTUL5bxZK8kZafOgC35m7BZ0Dks9Z2VI6r/2MLnjvZebTjOLHW1AeWmZa+0gxJZAsMeCe/E38yHvusCbvg3WEFY02EQ6MkYvsLZSK/FVwWfnuCSwOIhLSzUt4upB05DwJk7efPsowpPC0wjmsZuz1+ick4+/bBK19poTYnVf0yWmmdSJECJYLvX0CbDXZ3TWpV10eQ6Vy5X/b46/kjf3jJjcQCU1Ww8GlOfwwrjWhENNb4UkYaKWMQFYZVGWvQrEJ7GBEgxdwGkmAIF/O3iOxaml4zoc99GdIM3ecm+qPZlkRb8G7Ft3+6Ap9r0P5jCfweOHNRREIC5g+gfsCaFjazKpJbCHw+GAIgBHpx8pfQskaj8FRumONgyBASb4M78QYHPIRai1y+TH3eRcW+ERSc2Ecrr4SxyQiYzYSSBB1r4OX3Nm35MBL9MMf9hUY7c5/AU5oUFH/E3XuFmewbkbGHuLWa0RvmqkjKXH2RCqwJaZc4NxRKVnqbozPJ/gh/QiZFEumUCnKtfT908QXpHxJNSfMUwfQNOssbSmUW6a3ADnt4zIKepSWKabqEVAWjMc1+HPjLwFni98aUYCZO8oJ6+uacDCtDDYY0Kv5byTra/47Cx0XeO5a3bBlMlxlPmLQ1/9+1ncvatNUUgzVONz3DR3oH68aSUKo7EmZEnHn6QIOCZcFYJ1n1y5H85UyVkvAdCeI4yY1v5V3mZ5EgO5YUQrn6bYiWTSPSjbTiUKktSwNY9K8qG0FEwCoDJ9QSrzfW+cCqX3z+I9KlkNvOP3cBK/MW2qQZsxw4SB5L2GDkGl2w358I+Oxd8E5zztq+v9CXwz+Xf76M9A0criFRr7gHWvbGft0Jy4U75i5Xz9OGUE/Gx1/X6pnYFdPynMU9nbSxl+Q2IsqjmYOrjAoIiiek63hIyZMrlUOluQ5g1ysoBIHo58nT7XdyxqcojDytj1ECRtHI42+KbNKQKCMtcq2v+lOwwHwzndEi+Pf5dm8vsDrmqQWNpeTBQdl7YXlhAyNfcSLDRCxOBD/0iaCXki133S+YoGkceXWEuLpt/7llJgV34wyC30m8ZczgXgZBveAY/92bHTZwclydjpT1mwo8BK5hCTIchDDYtFogPcU+hfs4E30U9r7TnsE/IGDAGs19aNU/In2w9dmyqzf5T3sxjC9cEZ8rTK4dxpPxj11LJwZ9G+dmrZaG9Zio2rG3lQKW9CDhaJ9Yp+S1NrZx70AHMgDDhCFaEaslVqI3mDjfXjdkFS+i9QdTCPFAGz+9suITN3elljcyw9/XairqNR5t0FYSw5mUYMUTLp0Ey0t3QeQwULny/9jwA0NAPNLOaLe2NIELqZ/1PvH9O2dw=="
//        "nonce" => "bd8fc9993d6e"
//      ]
//      "expire_time" => "2025-02-27T22:58:58+08:00"
//      "serial_no" => "34AA489F1F6082D039CC339105F24709E60A44B6"
//    ]
//  ]
//]
        //$this->orderProcessTransaction([]);
    }
    /**
     * @param array $data
     * @return PaymentProcess|Model
     * @throws Throwable
     */
    public function orderProcessTransaction(array $data): Model|PaymentProcess
    {
//        dd((new AesUtil('HLptcPSOUM95cpILuyio9awKxaud7aa7'))->decryptToString('certificate', 'bd8fc9993d6e', 'ZPlXRz61rQPTgph3JVsQ1ZQsXQq6HvHpn5XOGhGOSylINjcLE7peVmjBTlILe0HMoa987oI1ORkk/Nubi6jjT3sO6K+HqWQoerAgWJPdhB/y6TxbYEKBHeoPKM2iTk6K6gTR4ymnioZUiRjVL2gJuCNxyCi8+0WSoEivZRq/n1aFwrQQfocaBNNs9XNNM+S6pFkS9JY6zEomFpZrBfQq/k0KvayB9XG3N2x3wB35GnbSl59xBUmaT6MG2Sh86sNluSDIOtaVEf2PckYMlvUp5OsV8TCzqbD6EGoyQiaEq/SUmCEpSsyZf3Nyqw1C7ZSjvbHVn0R9rMY0Yw6R8EVbN4A6TycehDn/F1CPPIf+HscXeRaiN+VCd1/hz2uiJRKQhpUJD7tWYAbQINdvQjb+ZSaWW10ZzwtADSbo/RAiiDnUV3jlD9AUH8YE0nr7jbX/MBE69wHOP6peoC1QE/+nDFXL8Ai+e7BVmVQoLqICQHZymsJcuwinG3losTM7bkgrwe6L+CpI8DmRQTUL5bxZK8kZafOgC35m7BZ0Dks9Z2VI6r/2MLnjvZebTjOLHW1AeWmZa+0gxJZAsMeCe/E38yHvusCbvg3WEFY02EQ6MkYvsLZSK/FVwWfnuCSwOIhLSzUt4upB05DwJk7efPsowpPC0wjmsZuz1+ick4+/bBK19poTYnVf0yWmmdSJECJYLvX0CbDXZ3TWpV10eQ6Vy5X/b46/kjf3jJjcQCU1Ww8GlOfwwrjWhENNb4UkYaKWMQFYZVGWvQrEJ7GBEgxdwGkmAIF/O3iOxaml4zoc99GdIM3ecm+qPZlkRb8G7Ft3+6Ap9r0P5jCfweOHNRREIC5g+gfsCaFjazKpJbCHw+GAIgBHpx8pfQskaj8FRumONgyBASb4M78QYHPIRai1y+TH3eRcW+ERSc2Ecrr4SxyQiYzYSSBB1r4OX3Nm35MBL9MMf9hUY7c5/AU5oUFH/E3XuFmewbkbGHuLWa0RvmqkjKXH2RCqwJaZc4NxRKVnqbozPJ/gh/QiZFEumUCnKtfT908QXpHxJNSfMUwfQNOssbSmUW6a3ADnt4zIKepSWKabqEVAWjMc1+HPjLwFni98aUYCZO8oJ6+uacDCtDDYY0Kv5byTra/47Cx0XeO5a3bBlMlxlPmLQ1/9+1ncvatNUUgzVONz3DR3oH68aSUKo7EmZEnHn6QIOCZcFYJ1n1y5H85UyVkvAdCeI4yY1v5V3mZ5EgO5YUQrn6bYiWTSPSjbTiUKktSwNY9K8qG0FEwCoDJ9QSrzfW+cCqX3z+I9KlkNvOP3cBK/MW2qQZsxw4SB5L2GDkGl2w358I+Oxd8E5zztq+v9CXwz+Xf76M9A0criFRr7gHWvbGft0Jy4U75i5Xz9OGUE/Gx1/X6pnYFdPynMU9nbSxl+Q2IsqjmYOrjAoIiiek63hIyZMrlUOluQ5g1ysoBIHo58nT7XdyxqcojDytj1ECRtHI42+KbNKQKCMtcq2v+lOwwHwzndEi+Pf5dm8vsDrmqQWNpeTBQdl7YXlhAyNfcSLDRCxOBD/0iaCXki133S+YoGkceXWEuLpt/7llJgV34wyC30m8ZczgXgZBveAY/92bHTZwclydjpT1mwo8BK5hCTIchDDYtFogPcU+hfs4E30U9r7TnsE/IGDAGs19aNU/In2w9dmyqzf5T3sxjC9cEZ8rTK4dxpPxj11LJwZ9G+dmrZaG9Zio2rG3lQKW9CDhaJ9Yp+S1NrZx70AHMgDDhCFaEaslVqI3mDjfXjdkFS+i9QdTCPFAGz+9suITN3elljcyw9/XairqNR5t0FYSw5mUYMUTLp0Ey0t3QeQwULny/9jwA0NAPNLOaLe2NIELqZ/1PvH9O2dw=='));
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

        $merchantId                  = '1577963751';
        $merchantCertificateSerial   = '766D55E9A5223A06DAD2758B4D72829DE4598219';

        $merchantPrivateKeyFilePath  = file_get_contents('storage/wechat/apiclient_key.pem');
        $merchantPrivateKeyInstance  = Rsa::from($merchantPrivateKeyFilePath, Rsa::KEY_TYPE_PRIVATE);

        $platformCertificateFilePath = file_get_contents('storage/wechat/apiclient_cert.pem');
        $platformPublicKeyInstance   = Rsa::from('-----BEGIN CERTIFICATE-----
ZPlXRz61rQPTgph3JVsQ1ZQsXQq6HvHpn5XOGhGOSylINjcLE7peVmjBTlILe0HMoa987oI1ORkk/Nubi6jjT3sO6K+HqWQoerAgWJPdhB/y6TxbYEKBHeoPKM2iTk6K6gTR4ymnioZUiRjVL2gJuCNxyCi8+0WSoEivZRq/n1aFwrQQfocaBNNs9XNNM+S6pFkS9JY6zEomFpZrBfQq/k0KvayB9XG3N2x3wB35GnbSl59xBUmaT6MG2Sh86sNluSDIOtaVEf2PckYMlvUp5OsV8TCzqbD6EGoyQiaEq/SUmCEpSsyZf3Nyqw1C7ZSjvbHVn0R9rMY0Yw6R8EVbN4A6TycehDn/F1CPPIf+HscXeRaiN+VCd1/hz2uiJRKQhpUJD7tWYAbQINdvQjb+ZSaWW10ZzwtADSbo/RAiiDnUV3jlD9AUH8YE0nr7jbX/MBE69wHOP6peoC1QE/+nDFXL8Ai+e7BVmVQoLqICQHZymsJcuwinG3losTM7bkgrwe6L+CpI8DmRQTUL5bxZK8kZafOgC35m7BZ0Dks9Z2VI6r/2MLnjvZebTjOLHW1AeWmZa+0gxJZAsMeCe/E38yHvusCbvg3WEFY02EQ6MkYvsLZSK/FVwWfnuCSwOIhLSzUt4upB05DwJk7efPsowpPC0wjmsZuz1+ick4+/bBK19poTYnVf0yWmmdSJECJYLvX0CbDXZ3TWpV10eQ6Vy5X/b46/kjf3jJjcQCU1Ww8GlOfwwrjWhENNb4UkYaKWMQFYZVGWvQrEJ7GBEgxdwGkmAIF/O3iOxaml4zoc99GdIM3ecm+qPZlkRb8G7Ft3+6Ap9r0P5jCfweOHNRREIC5g+gfsCaFjazKpJbCHw+GAIgBHpx8pfQskaj8FRumONgyBASb4M78QYHPIRai1y+TH3eRcW+ERSc2Ecrr4SxyQiYzYSSBB1r4OX3Nm35MBL9MMf9hUY7c5/AU5oUFH/E3XuFmewbkbGHuLWa0RvmqkjKXH2RCqwJaZc4NxRKVnqbozPJ/gh/QiZFEumUCnKtfT908QXpHxJNSfMUwfQNOssbSmUW6a3ADnt4zIKepSWKabqEVAWjMc1+HPjLwFni98aUYCZO8oJ6+uacDCtDDYY0Kv5byTra/47Cx0XeO5a3bBlMlxlPmLQ1/9+1ncvatNUUgzVONz3DR3oH68aSUKo7EmZEnHn6QIOCZcFYJ1n1y5H85UyVkvAdCeI4yY1v5V3mZ5EgO5YUQrn6bYiWTSPSjbTiUKktSwNY9K8qG0FEwCoDJ9QSrzfW+cCqX3z+I9KlkNvOP3cBK/MW2qQZsxw4SB5L2GDkGl2w358I+Oxd8E5zztq+v9CXwz+Xf76M9A0criFRr7gHWvbGft0Jy4U75i5Xz9OGUE/Gx1/X6pnYFdPynMU9nbSxl+Q2IsqjmYOrjAoIiiek63hIyZMrlUOluQ5g1ysoBIHo58nT7XdyxqcojDytj1ECRtHI42+KbNKQKCMtcq2v+lOwwHwzndEi+Pf5dm8vsDrmqQWNpeTBQdl7YXlhAyNfcSLDRCxOBD/0iaCXki133S+YoGkceXWEuLpt/7llJgV34wyC30m8ZczgXgZBveAY/92bHTZwclydjpT1mwo8BK5hCTIchDDYtFogPcU+hfs4E30U9r7TnsE/IGDAGs19aNU/In2w9dmyqzf5T3sxjC9cEZ8rTK4dxpPxj11LJwZ9G+dmrZaG9Zio2rG3lQKW9CDhaJ9Yp+S1NrZx70AHMgDDhCFaEaslVqI3mDjfXjdkFS+i9QdTCPFAGz+9suITN3elljcyw9/XairqNR5t0FYSw5mUYMUTLp0Ey0t3QeQwULny/9jwA0NAPNLOaLe2NIELqZ/1PvH9O2dw==
-----END CERTIFICATE-----', Rsa::KEY_TYPE_PUBLIC);

        $platformCertificateSerial   = PemUtil::parseCertificateSerialNo($platformCertificateFilePath);

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
