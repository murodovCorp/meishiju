<?php

namespace App\Http\Controllers\API\v1\Dashboard\Payment;

use App\Helpers\ResponseError;
use App\Http\Requests\Payment\PaymentTopUpRequest;
use App\Models\Currency;
use App\Models\Payment;
use App\Models\PaymentPayload;
use App\Models\PaymentProcess;
use App\Models\Translation;
use App\Models\User;
use App\Models\Wallet;
use Exception;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use Http;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Matscode\Paystack\Transaction;
use Razorpay\Api\Api;
use Redirect;
use Str;
use Stripe\Checkout\Session;
use Stripe\Exception\ApiErrorException;
use Stripe\Stripe;
use Throwable;

class WalletPaymentController extends PaymentBaseController
{
    public function __construct(private string $host = '')
    {
        parent::__construct();
    }

    /**
     * Display a listing of the resource.
     */
    public function paymentTopUp(PaymentTopUpRequest $request): JsonResponse
    {
        /** @var User $user */
        $user           = auth('sanctum')->user();
        $payment        = Payment::where('tag', $request->input('payment_type'))->first();

        if (empty($user?->wallet)) {
            return $this->onErrorResponse([
                'code'    => ResponseError::ERROR_108,
                'message' => __('errors.' . ResponseError::ERROR_108)
            ]);
        }

        if (empty($payment)) {
            return $this->onErrorResponse([
                'code'    => ResponseError::ERROR_432,
                'message' => __('errors.' . ResponseError::ERROR_432)
            ]);
        }

        $paymentPayload = PaymentPayload::where('payment_id', $payment->id)->first();

        if (empty($paymentPayload)) {
            return $this->onErrorResponse([
                'code'    => ResponseError::ERROR_432,
                'message' => __('errors.' . ResponseError::ERROR_432)
            ]);
        }

        $payload = $paymentPayload->payload;

        $totalPrice = ceil($request->input('price')) * 100;

        /** @var Translation $transaction */
        $transaction = $user->wallet->createTransaction([
            'price'                 => $totalPrice,
            'user_id'               => $user->id,
            'payment_sys_id'        => $payment->id,
            'note'                  => "referral #{$user->wallet->id}",
            'perform_time'          => now(),
            'status_description'    => "Referral transaction for wallet #{$user->wallet->id}"
        ]);

        $host = request()->getSchemeAndHttpHost();

        $this->host = $host;

        try {

            if ($payment->tag === 'stripe') {

                $session = $this->stripe($payload, $totalPrice);

                return $this->successResponse(
                    'success',
                    $this->process(
                        userId: $user->id,
                        trxId:  $session->payment_intent ?? $session->id,
                        id:     $session->payment_intent ?? $session->id,
                        url:    $session->url,
                        totalPrice: $totalPrice
                    )
                );
            } else if($payment->tag === 'razorpay') {

                $paymentLink = $this->razorpay($payload, $totalPrice);

                return $this->successResponse(
                    'success',
                    $this->process(
                        userId: $user->id,
                        trxId:  $transaction->id,
                        id:     data_get($paymentLink, 'id'),
                        url:    data_get($paymentLink, 'short_url'),
                        totalPrice: $totalPrice
                    )
                );
            } else if($payment->tag === 'paystack') {

                $response = $this->payStack($payload, $totalPrice, $user);

                return $this->successResponse(
                    'success',
                    $this->process(
                        userId: $user->id,
                        trxId:  $transaction->id,
                        id:     data_get($response, 'reference'),
                        url:    data_get($response, 'authorizationUrl'),
                        totalPrice: $totalPrice
                    )
                );
            } else if($payment->tag === 'paypal') {

                $response = $this->paypal($payload, $totalPrice);

                return $this->successResponse(
                    'success',
                    $this->process(
                        userId: $user->id,
                        trxId:  $transaction->id,
                        id:     data_get($response, 'id'),
                        url:    data_get($response, 'url'),
                        totalPrice: $totalPrice
                    )
                );
            } else if($payment->tag === 'flutterWave') {

                $response = $this->flutterWave($payload, $totalPrice);

                return $this->successResponse(
                    'success',
                    $this->process(
                        userId: $user->id,
                        trxId:  $transaction->id,
                        id:     data_get($response, 'id'),
                        url:    data_get($response, 'url'),
                        totalPrice: $totalPrice
                    )
                );
            } else if($payment->tag === 'paytabs') {

                $response = $this->payTabs($payload, $totalPrice);

                return $this->successResponse(
                    'success',
                    $this->process(
                        userId: $user->id,
                        trxId:  $transaction->id,
                        id:     data_get($response, 'id'),
                        url:    data_get($response, 'url'),
                        totalPrice: $totalPrice
                    )
                );
            }

        } catch (Throwable $e) {
            return $this->onErrorResponse(['code' => $e->getCode(), 'message' => $e->getMessage()]);
        }

        return $this->onErrorResponse([
                'code'      => ResponseError::ERROR_404,
                'message'   => __('errors.' . ResponseError::ERROR_404, locale: $this->language)
            ]);
    }

    /**
     * @param array $payload
     * @param int|float $totalPrice
     * @return Session|Throwable
     * @throws ApiErrorException
     */
    public function stripe(array $payload, int|float $totalPrice): Session|Throwable
    {
        Stripe::setApiKey(data_get($payload, 'stripe_sk'));

        return Session::create([
            'payment_method_types' => ['card'],
            'line_items' => [
                [
                    'price_data' => [
                        'currency' => Str::lower(data_get($payload, 'currency')),
                        'product_data' => [
                            'name' => 'Payment'
                        ],
                        'unit_amount' => $totalPrice,
                    ],
                    'quantity' => 1,
                ]
            ],
            'mode' => 'payment',
            'success_url' => "$this->host/wallet-success?token={CHECKOUT_SESSION_ID}",
            'cancel_url' => "$this->host/wallet-success?token={CHECKOUT_SESSION_ID}",
        ]);
    }

    /**
     * @param array $payload
     * @param int|float $totalPrice
     * @return mixed
     */
    public function razorpay(array $payload, int|float $totalPrice): mixed
    {
        $key    = data_get($payload, 'razorpay_key');
        $secret = data_get($payload, 'razorpay_secret');

        $api    = new Api($key, $secret);

        return $api->paymentLink->create([
            'amount'                    => $totalPrice,
            'currency'                  => Str::upper(data_get($payload, 'currency')),
            'accept_partial'            => false,
            'first_min_partial_amount'  => $totalPrice,
            'description'               => "For topup",
            'callback_url'              => "$this->host/wallet-success",
            'callback_method'           => 'get'
        ]);
    }

	/**
	 * @param array $payload
	 * @param int|float $totalPrice
	 * @param User $user
	 * @return mixed
	 * @throws Exception
	 */
    public function payStack(array $payload, int|float $totalPrice, User $user): mixed
    {
        $payStack = new Transaction(data_get($payload, 'paystack_sk'));

        $data = [
            'email'     => $user->email,
            'amount'    => $totalPrice,
            'currency'  => Str::upper(data_get($payload, 'currency')), //ZAR
        ];

        return $payStack
            ->setCallbackUrl("$this->host/wallet-success")
            ->initialize($data);
    }

    /**
     * @param array $payload
     * @param int|float $totalPrice
     * @return array
     * @throws GuzzleException
     * @throws Exception
     */
    public function paypal(array $payload, int|float $totalPrice): array
    {
        $url            = 'https://api-m.sandbox.paypal.com';
        $clientId       = data_get($payload, 'paypal_sandbox_client_id');
        $clientSecret   = data_get($payload, 'paypal_sandbox_client_secret');

        if (data_get($payload, 'paypal_mode', 'sandbox') === 'live') {
            $url            = 'https://api-m.paypal.com';
            $clientId       = data_get($payload, 'paypal_live_client_id');
            $clientSecret   = data_get($payload, 'paypal_live_client_secret');
        }

        $provider = new Client();
        $responseAuth = $provider->post("$url/v1/oauth2/token", [
            'auth' => [
                $clientId,
                $clientSecret,
            ],
            'form_params' => [
                'grant_type' => 'client_credentials',
            ]
        ]);

        $responseAuth = json_decode($responseAuth->getBody(), true);

        $response = $provider->post("$url/v2/checkout/orders", [
            'json' => [
                'intent' => 'CAPTURE',
                'purchase_units' => [
                    [
                        'amount' => [
                            'currency_code' => Str::upper($order->currency?->title ?? data_get($payload, 'paypal_currency')),
                            'value' => $totalPrice,
                        ],
                    ],
                ],
                'application_context' => [
                    'return_url' =>  "$this->host/wallet-success?status=paid",
                    'cancel_url' =>  "$this->host/wallet-success?status=canceled",
                ],
            ],
            'headers' => [
                'Accept-Language'   => 'en_US',
                'Content-Type'      => 'application/json',
                'Authorization'     => data_get($responseAuth, 'token_type', 'Bearer') . ' ' . data_get($responseAuth, 'access_token'),
            ],
        ]);

        $response = json_decode($response->getBody(), true);

        if (data_get($response, 'error')) {

            $message = data_get($response, 'message', 'Something went wrong');

            throw new Exception($message);
        }

        $links = collect(data_get($response, 'links'));

        $checkoutNowUrl = data_get($links->where('rel', 'approve')->first(), 'href');
        $checkoutNowUrl = $checkoutNowUrl ?? data_get($links->where('rel', 'payer-action')->first(), 'href');

        return [
            'id'  => data_get($response, 'id'),
            'url' => $checkoutNowUrl ?? data_get($links->first(), 'href'),
        ];
    }

    /**
     * @param array $payload
     * @param int|float $totalPrice
     * @return array
     * @throws GuzzleException
     * @throws Exception
     */
    public function flutterWave(array $payload, int|float $totalPrice): array
    {
        $headers = [
            'Accept' => 'application/json',
            'Content-Type' => 'application/json',
            'Authorization' => 'Bearer ' . data_get($payload, 'flw_sk')
        ];

        $trxRef = (string)time();

        $currency = Currency::currenciesList()->where('default', 1)->first()?->title;
        $currency = Str::upper($currency ?? data_get($payload, 'currency'));
        /** @var User $user */
        $user     = auth('sanctum')->user();

        $data = [
            'tx_ref'            => $trxRef,
            'amount'            => $totalPrice,
            'currency'          => $currency,
            'payment_options'   => 'card,account,ussd,mobilemoneyghana',
            'redirect_url'      => "$this->host/wallet-success",
            'customer'          => [
                'name'          => "$user->firstname $user?->lastname",
                'phonenumber'   => $user->phone,
                'email'         => $user?->email
            ],
            'customizations'    => [
                'title'         => data_get($payload, 'title', ''),
                'description'   => data_get($payload, 'description', ''),
                'logo'          => data_get($payload, 'logo', ''),
            ]
        ];

        $request = Http::withHeaders($headers)->post('https://api.flutterwave.com/v3/payments', $data);

        $response = $request->json();

        if (data_get($response, 'status') === 'error') {
            throw new Exception(data_get($response, 'message'));
        }

        return [
            'id'  => $trxRef,
            'url' => data_get($response, 'data.link'),
        ];
    }

    /**
     * @param array $payload
     * @param int|float $totalPrice
     * @return array
     * @throws Exception
     */
    public function payTabs(array $payload, int|float $totalPrice): array
    {
        $headers = [
            'Accept' => 'application/json',
            'Content-Type' => 'application/json',
            'Authorization' => 'Bearer ' . data_get($payload, 'server_key')
        ];

        $trxRef = (string)time();

        $currency = Str::upper($order->currency?->title ?? data_get($payload, 'currency'));

        /** @var User $user */
        $user = auth('sanctum')->user();

        $request = Http::withHeaders($headers)->post('https://secure.paytabs.sa/payment/request', [
            'merchant_id'       => '105345',
            'secret_key'        => 'SZJN6JRB6R-JGGWW29DD9-RWKLJNWNGR',
            'site_url'          => config('app.admin_url'),
            'return_url'        => "$this->host/wallet-success",
            'cc_first_name'     => $user->firstname,
            'cc_last_name'      => $user->lastname,
            'cc_phone_number'   => $user->phone,
            'cc_email'          => $user->email,
            'amount'            => $totalPrice,
            'currency'          => $currency,
            'msg_lang'          => $this->language,
        ]);

        $response = $request->json();

        if (data_get($response, 'status') === 'error') {
            throw new Exception(data_get($response, 'message'));
        }

        return [
            'id'  => $trxRef,
            'url' => data_get($response, 'data.link'),
        ];
    }

    public function process(
        int $userId,
        int|string $trxId,
        int|string $id,
        string $url,
        int|float $totalPrice
    ): Model|PaymentProcess
    {
        $wallet = Wallet::withTrashed()
            ->firstOrCreate([
                'user_id' => $userId
            ], [
                'uuid'          => Str::uuid(),
                'currency_id'   => Currency::currenciesList()->where('default', 1)->first()?->rate,
                'price'         => 0,
                'deleted_at'    => null
            ]);

        return PaymentProcess::updateOrCreate([
            'user_id'    => $userId,
            'model_id'   => $wallet->id,
            'model_type' => get_class($wallet)
        ], [
            'id' => $id,
            'data' => [
                'url'       => $url,
                'price'     => $totalPrice,
                'type'      => 'wallet',
                'user_id'   => $userId,
                'trx_id'    => $trxId,
            ]
        ]);
    }

    public function success(): RedirectResponse
    {
        $to = config('app.admin_url');

        return Redirect::to($to);
    }
}
