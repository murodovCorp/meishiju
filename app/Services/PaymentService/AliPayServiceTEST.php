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
use Yansongda\Pay\Pay;

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

        $order          = Order::first();
        $totalPrice     = ceil($order->rate_total_price * 2 * 100) / 2;

        $order->update([
            'total_price' => ($totalPrice / $order->rate) / 100
        ]);

        $host               = request()->getSchemeAndHttpHost();
        $method             = 'POST';
        $path               = 'https://open-na.alipay.com/ams/api/v1/payments/pay';
        $clientId           = 'SANDBOX_5Y8Z7N2Z4P5Q03943';
        $reqTime            = now()->format('Y-m-d\TH:i:sp');
        $paymentRequestId   = Str::uuid();
        $referenceOrderId   = "ORDER_$paymentRequestId";
        $currency           = Str::upper($order->currency?->title ?? data_get($payload, 'currency'));
        $privateKey         = 'KZrk8opYpRZNH5O4+Wwd4aVexGuRsllcAtTUn+Egl5kBVT6WBXc0/Zn0mquyLlNspAgMBAAECggEAdic3QitHBdqy6IhQmEMOC49UIlx/0xNXmuJd69WKqIJCtLFgBVu/n4FOyOM83UlErEIOCFQRMXQQIfKcYAMyJl0621FttbJmkbtQ0R5psT6fluKKpAiMMJSzhCeiqu/c5AlFZDmCi+YBlWNDosN3trtBNjJyeI75qftrqbMh3ioS3gzPOc1oVNOWyr580iwNc2ALrhrac2lbBuC5A8ORXLUAUtM1YEOf5my8RSfE/yW6Af/NXwVG2JR+NmVdpAiNfUTAG7yH8kJLGq2ep9d2lChHGaZtBWGS1gCnai8IPgVCACOFP+4+pkeTSKNONRLPN8DyJnsFvX2Al4L6KIXQUQKBgQDmdC5TU6Nt1eOMPbQCUD9BePip0eDENr6e7SWJQxjuZWt18VRTHfqQDf+qMM5NdS2GP0JPH1xgWiyMAEeFPG/bpAiv6F7yGIfP+8REGFVZbdJRzPbtN+FiDTR7yPFWCicUQEuMu//NNKvqakuMLRgMT1Zg+uzqHYQIrTFYcOBrzQKBgQCnzbCosL3Ko+DX5dGPgk4c19JN/mlVAfIpumqv2ArojON/OW+GylcMPT7AZh8aSDTaRXxXTyUJTT++2xqlQENRXLCuxbGrtFWZgytjU4D4YYkn+ihtjLaeNib2vKLCCmfCN7chIzWBh3yhuKH/cdI7YbqDTugBFEEuhI3KhaCozQKBgE+spz+D0SLuKeeYhZ2vJM98BWyg9TahPrIvhyS3n+z7/3UdZGwAF1qqnFO43/qDoqOhR0mXrBZb1r7ocdGsnXewdJhsnDbTKFFN2AM67ncmsuo5FL3a7f86VYTeaiG3DN/Bgt07Ois2JKG88jWaeY/39gM9fZ9LaRSe3EqZa92ZAoGAQ6mDBGJQBTfTX/sBZzaJvMOhv2VIn8hrFzxd7I3WyDKXQSOtvtI0C3FerkH/ZJ+dAC5oluQI8Rk/DPxYYC3rdxFDBRYeMDhFE+N6SVDQflcF8SLDznig4ma/i1pA3rFHaV8B7tC9sH8rWCKU3+XLebpMdMoKbvT124YDjpgXUjkCgYBYmwwm5RYe2wEp9LHabXA91SArtfa2DkgSNPVcE1YJTgIK4Ubo88+zrhq84puIiPsrYocwMTm+P7XIEKddqpv8XTlGwFq9M5j5YzQqE9ukkze2UR7o9qlniUTL+aKgfAVY2i9pSrMYELLEXY6AXV+XWrVwqEhccgzPFtAESne8bA==';
//        $signature          = $this->sign($method, $path, $clientId, $reqTime, json_encode($data), $privateKey);
        $signature          = 'WcO+t3D8Kg71dTlKwN7r9PzUOXeaBJwp8/FOuSxcuSkXsoVYxBpsAidprySCjHCjmaglNcjoKJQLJ28/Asl93joTW39FX6i07lXhnbPknezAlwmvPdnQuI01HZsZF9V1i6ggZjBiAd5lG8bZtTxZOJ87ub2i9GuJ3Nr/NUc9VeY=';

        $headers = [
            'Content-Type'  => 'application/json; charset=UTF-8',
            'client-id'     => $clientId,
            'request-time'  => $reqTime,
            'signature'     => "algorithm=RSA256,keyVersion=1,signature=$signature",
        ];

//        $currency = 'CNY';
//        if (empty(data_get($this->currencies, $currency))) {
//            throw new Exception('We supported only this currencies:' . implode(', ', array_keys($this->currencies)));
//        }

        $config = [
            'alipay' => [
                'default' => [
                    // 必填-支付宝分配的 app_id
                    'app_id' => '9021000122687162',
                    // 必填-应用私钥 字符串或路径
                    // 在 https://open.alipay.com/develop/manage 《应用详情->开发设置->接口加签方式》中设置
                    'app_secret_cert' => 'MIIEowIBAAKCAQEAo9zSEblmFp3qAJywrwIjFYp1o/pwGzia+pEr3DokUejXIomcOSuzQ2GzVGXCZMF+BT4FEUIk49OVfiJxULCayA3yiVnIXnLNLQ3pkp+HusxCWlEb99ZizztnPOtPr40hcF2JdE4Cs8QTiMV1+840ovM6rPiw5xiW2BzYOpoUQ7jaiVts6hlWJoa0liyhBzU/vEq1pDkUIUsTKSlGBbCS5FdwRChjQrEQgWcdx5YBCFkuPNEjGaHfEa6pOwDf8mujfNUp/OyA4zPR/eQoB8atFh/OhROMTulBThyxvuIc1DYuj2M02X0sv0thqs02AzN94RpHf+ao9+pB51xq0b2clQIDAQABAoIBAARgxwAQOwkJqRHhHdtfLD7MFz6NV2dyyLk/HbPhnczOcskKcjEY2M5JEviDS4FTpiFPueTvJnsr0QewteRZA5mqvbsrue3EFXVJtN7lAkRcZr1xA1VI1hUBWk3HX7aqB6Wv6hdAalQRPhBebjuPE9rysrOiZV+XSZh2lNRj/3ybrdDpFSVTgGf/v/iOs2ML9jXKERCUtSko5s8wNIdCNcxB/T+EGr3H6oIJbZULvyPlAhzbU38//A9ZScF63h8ynhMbDWLPKZ+j4paQCbVQ7bCX4Lh+X/aynBAjs8taKf6sL6vDiR3fYUOM6CUC1wGfz1JPuyuMQE2IMVo7vliYVCECgYEA0C9onKzcDgORonO7RjiGPuAX3UzhSeBJ8xDNMB4nBmBGB1rhhh4MRGojLgtxUCXQMVuYzbY5v3muIavClZb4NAsGB6J6EJFIPacUxs2KZbXUudk5fM4aI7V381hYaXTQQ5iJOe4EKQ0QwLKRenfW3HjdE/+JTwCAdQosqIyeWOkCgYEAyX9kHKSn5vww3zYU9oyyCTrlq/J/J/0oT+Pe+lnCacwtQq24kk3sP3OHPsLSDzzdyLSQcRUePvp9zzuGR7dN0lFVbA84CnIoEuJ84dVouTD8q6TR89w/bxFLbIT0OzflpfbSP7iq/uG4QtPUzcB1yQKe8KVwO7v7lKj6dOEq2s0CgYAUkYqJaD65l7qszThkgLMqxSM2dyEPFnzX6gILzf7XD237zgvYH2Hg5IejRfglDgdayQz4zhc4hsIgi4LHGspdAfXPjUr4FhKIHNjdp3MUB3oD/qVCYm6MtqIqRcE+cg8djpWIRq7ci3DrSPk8m1h8Iejdz/J1/ruyVJYC9Rmz+QKBgEa7v4sGiiJd2VIiDA3Yqg7va/yGbfi9t88DsRy1MbguAp1rmmVRkWNczNdNUKwks75nFGV+AlYdXNjIjoTSZjE0eAYs+YFBPawTcb9dSRvphGlEvKccY/A7Y/y+V3YKU0WFdZ0E2JIM7sAW2Jc5vp9Hmj/j85gkj1ZKU1GAUajpAoGBAIi0sLcbqY6XZAQY8WUqW+zd/rXV5P9DYb+6jFedEePVDKSk4NFt6rxn1tNHp6PvJhzGfcWagCea6QSV6/rtqq5JjHx6xjIVdMMYkYfdZaoojA5YV3DRUTOh9IwK7YNgSzGZ2DRa3Adbt7iic+mulPwGou/oEtqB+cdMgA01VX1I',
                    // 必填-应用公钥证书 路径
                    // 设置应用私钥后，即可下载得到以下3个证书
                    'app_public_cert_path' => storage_path('app/public/appPublicCert.crt'),
                    // 必填-支付宝公钥证书 路径
                    'alipay_public_cert_path' => storage_path('app/public/alipayPublicCert.crt'),
                    // 必填-支付宝根证书 路径
                    'alipay_root_cert_path' => storage_path('app/public/alipayRootCert.crt'),
                    'return_url' => "https://9112-213-230-97-92.ngrok-free.app/api/v1/order-alipay-success",
                    'notify_url' => "https://9112-213-230-97-92.ngrok-free.app/api/v1/order-alipay-success",
                    // 选填-第三方应用授权token
                    'app_auth_token' => '',
                    // 选填-服务商模式下的服务商 id，当 mode 为 Pay::MODE_SERVICE 时使用该参数
                    'service_provider_id' => '',
                    // 选填-默认为正常模式。可选为： MODE_NORMAL, MODE_SANDBOX, MODE_SERVICE
                    'mode' => Pay::MODE_SANDBOX,
                ],
            ],

            'logger' => [
                'enable' => false,
                'file' => storage_path('logs/alipay.log'),
                'level' => 'info', // 建议生产环境等级调整为 info，开发环境为 debug
                'type' => 'single', // optional, 可选 daily.
                'max_file' => 30, // optional, 当 type 为 daily 时有效，默认 30 天
            ],
            'http' => [ // optional
                'timeout' => 5.0,
                'connect_timeout' => 5.0,
                // 更多配置项请参考 [Guzzle](https://guzzle-cn.readthedocs.io/zh_CN/latest/request-options.html)
            ],
        ];

        //PAYMENT_RESULT
        //ALIPAY_CN
        //20230608194010800190190000008877327
        //PAYMENT-202306081216416258578
        //a-1686316676
        $time = 'a-'.time();

        $data = [
            'order' => [
                'orderAmount' => [
                    'currency'   => $currency,
                    'value'      => $totalPrice
                ],
                'orderDescription' => 'Cappuccino #grande (Mika\'s coffee shop)',
                'referenceOrderId' => $referenceOrderId,
                'env' => [
                    'terminalType' => 'WEB',
                    'osType' => 'ANDROID',
                ],
                'merchant' => [
                   'referenceMerchantId' => 'SM_001'
                ],
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
//            'productCode'        => 'CASHIER_PAYMENT'
        ];

        $result = Pay::alipay($config)->app([
            'out_trade_no' => time(),
            'total_amount' => '100',
            'subject' => 'yansongda 测试 - 01',
        ]);
//
//        $aop = new AopClient ();
//        $aop->gatewayUrl = 'https://openapi.alipay.com/gateway.do';
//        $aop->appId = 'your app_id';
//        $aop->rsaPrivateKey = '请填写开发者私钥去头去尾去回车，一行字符串';
//        $aop->alipayrsaPublicKey='请填写支付宝公钥，一行字符串';
//        $aop->apiVersion = '1.0';
//        $aop->signType = 'RSA2';
//        $aop->postCharset='GBK';
//        $aop->format='json';
//        $object = new stdClass();
//        $object->out_trade_no = '20210817010101004';
//        $object->total_amount = 0.01;
//        $object->subject = '测试商品';
//        $object->product_code ='QUICK_MSECURITY_PAY';
//        $object->time_expire = '2022-08-01 22:00:00';
//////商品信息明细，按需传入
//// $goodsDetail = [
////     [
////         'goods_id'=>'goodsNo1',
////         'goods_name'=>'子商品1',
////         'quantity'=>1,
////         'price'=>0.01,
////     ],
//// ];
//// $object->goodsDetail = $goodsDetail;
//// //扩展信息，按需传入
//// $extendParams = [
////     'sys_service_provider_id'=>'2088511833207846',
//// ];
////  $object->extend_params = $extendParams;
//        $json = json_encode($object);
//        $request = new AlipayTradeAppPayRequest();
//        $request->setNotifyUrl('');
//        $request->setBizContent($json);
//
//        $result = $aop->sdkExecute ( $request);
//
//        $responseNode = str_replace(".", "_", $request->getApiMethodName()) . "_response";
//        $resultCode = $result->$responseNode->code;
        //[
        //  "actualPaymentAmount" => array:2 [
        //    "currency" => "USD"
        //    "value" => "1"
        //  ]
        //  "customsDeclarationAmount" => array:2 [
        //    "currency" => "CNY"
        //    "value" => "7"
        //  ]
        //  "notifyType" => "PAYMENT_RESULT"
        //  "paymentAmount" => array:2 [
        //    "currency" => "USD"
        //    "value" => "1"
        //  ]
        //  "paymentCreateTime" => "2023-06-08T20:16:42+08:00"
        //  "paymentId" => "20230608194010800190190000008877327"
        //  "paymentRequestId" => "PAYMENT-202306081216416258578"
        //  "paymentTime" => "2023-06-08T20:16:52+08:00"
        //  "pspCustomerInfo" => array:1 [
        //    "pspName" => "ALIPAY_CN"
        //  ]
        //  "result" => array:3 [
        //    "resultCode" => "SUCCESS"
        //    "resultMessage" => "success."
        //    "resultStatus" => "S"
        //  ]
        //  "order_id" => "2645"
        //]
//        $sign = $this->generateAlipaySignature([
//            'app_id'    => '2016082000295641',
//            'method'    => 'alipay.trade.pay',
//            'charset'   => 'UTF-8',
//            'sign_type' => 'RSA2',
//            'timestamp' => now(),
//        ]);
//        dd($sign);
        dd(
            request()->all(),
            request()->headers,
            @file_get_contents("php://input"),
            $time,
            $result,
//            $result->callback(
//                [
//                    'productCode'       => 'IN_STORE_PAYMENT',
//                    'paymentRequestId'  => request()->input('paymentRequestId'),
//                    'paymentFactor'     => [
//                        'inStorePaymentScenario' => 'EntryCode',
//                    ],
//                    'order' => [
//                        'orderAmount' => [
//                            'currency'   => $currency,
//                            'value'      => $totalPrice
//                        ],
//                        'orderDescription' => 'Cappuccino #grande (Mika\'s coffee shop)',
//                        'referenceOrderId' => $referenceOrderId,
////                'env' => [
////                    'terminalType' => 'WEB'
////                ],
////                'merchant' => [
////                   'referenceMerchantId' => 'SM_001'
////                ],
////                'extendInfo' => [
////                    'chinaExtraTransInfo' => [
////                        'totalQuantity'     => '1',
////                        'otherBusinessType' => 'food',
////                        'businessType'      => '1',
////                        'goodsInfo'         => '1'
////                    ]
////                ]
//                    ],
//                    'paymentAmount' => [
//                        'currency'  => $currency,
//                        'value'     => $totalPrice
//                    ],
//                    'sign'    => 'asd',
//                ],
//                [
//                    '_config' => 'yansongda'
//                ]
//            )
        );



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

    public function generateAlipaySignature($params): string
    {

        $privateKeyPath = storage_path('app/public/pr.pem');

        $paramStr = '';

        foreach ($params as $key => $value) {
            $paramStr .= $key . '=' . $value . '&';
        }

        $paramStr = rtrim($paramStr, '&');

        // Чтение приватного ключа
        $privateKey = openssl_pkey_get_private($privateKeyPath);

        // Создание подписи
        openssl_sign($paramStr, $signature, $privateKey, OPENSSL_ALGO_SHA256);

        // Освобождение памяти и закрытие ключа
        openssl_free_key($privateKey);

        // Кодирование подписи в Base64
        return base64_encode($signature);
    }

}
