<?php

namespace App\Http\Controllers\API\v1\Dashboard\Payment;

use App\Http\Controllers\Controller;
use App\Http\Requests\Payment\StripeRequest;
use App\Models\Transaction;
use App\Services\PaymentService\AliPayServiceV2;
use App\Traits\ApiResponse;
use App\Traits\Notification;
use App\Traits\OnResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Psr\Http\Message\ResponseInterface;
use Throwable;
use Yansongda\Pay\Exception\ContainerException;
use Yansongda\Pay\Exception\InvalidParamsException;
use Yansongda\Pay\Pay;

class AliPayV2Controller extends Controller
{
    use OnResponse, ApiResponse, Notification;

    public function __construct(private AliPayServiceV2 $service)
    {
        parent::__construct();
    }

    /**
     * process transaction.
     *
     * @param StripeRequest $request
     * @return JsonResponse
     */
    public function prepay(StripeRequest $request): JsonResponse
    {
        try {
            $result = $this->service->preparePay($request->all());

            if (!data_get($result, 'status')) {
                return $this->onErrorResponse($result);
            }

            /** @var ResponseInterface $data */
            $data = $result['data'];

            $responseBody = $data->getBody()->getContents();

//            parse_str($responseBody, $json);
//
//            $bizContent = json_decode(urldecode(data_get($json, 'biz_content')), true);
//
//            $json['biz_content'] = $bizContent;

            return $this->successResponse('success', $responseBody);
        } catch (Throwable $e) {

            $this->error($e);

            return $this->onErrorResponse([
                'message' => $e->getMessage() . ' ' . $e->getFile() . ' ' . $e->getLine() . ' ' . $e->getCode(),
            ]);

        }
    }

    public function notify(Request $request): JsonResponse
    {
        Log::error('alipay', $request->all());

        try {
            $config = config('pay.alipay');
            $alipay = Pay::alipay($config);

            $order = [
                'out_trade_no' => $request->input('out_trade_no'),
            ];

            try {

                $result = Pay::alipay()->find($order);

                Log::error('$result', [$result]);

                if (data_get($result, 'trade_status') === 'TRADE_SUCCESS') {

                    $transaction = Transaction::with([
                        'payable',
                    ])
                        ->where('payment_trx_id', $request->input('out_trade_no', '1693303276'))
                        ->first();

                    $transaction?->update([
                        'status' => Transaction::STATUS_PAID,
                    ]);

                }

            } catch (Throwable $e) {
                Log::error($e->getMessage(), [
                    $e->getFile(),
                    $e->getLine(),
                    $e->getCode(),
                ]);
            }

            $data = $alipay->callback();

            Log::info('bosya tr：notify',[$data]);

        } catch (ContainerException|InvalidParamsException $e) {

            $message = $e->getMessage() . ' ' . $e->getFile() . ' ' . $e->getLine() . ' ' . $e->getCode();

            Log::error("bosya: $message", $request->all());

            return $this->onErrorResponse(['message' => $message]);
        }

        return $this->successResponse('success', $data);
    }

    public function paid(Request $request) {

//        $order = Order::find($request->input('order_id'));
//
//        if (empty($order)) {
//            Log::error('empty order:', $request->all());
//            return;
//        }
//
//        $order->transaction?->update([
//            'status' => Transaction::STATUS_PAID,
//        ]);
//
//        $tokens = (new BaseService)->tokens($order);
//
//        $this->sendNotification(
//            data_get($tokens, 'tokens'),
//            "New order was created",
//            $order->id,
//            $order->setAttribute('type', PushNotification::NEW_ORDER)?->only(['id', 'status', 'type']),
//            data_get($tokens, 'ids', [])
//        );
    }

}
