<?php

namespace App\Http\Controllers\API\v1\Dashboard\Payment;

use App\Helpers\ResponseError;
use App\Http\Controllers\Controller;
use App\Http\Requests\Payment\StripeRequest;
use App\Models\Order;
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

    /**
     * @param Request $request
     * @return JsonResponse
     */
    public function notify(Request $request): JsonResponse
    {
        try {
            Log::error('alipay', $request->all());
        } catch (Throwable) {}

        try {

            $id = $request->input('out_trade_no');

            if (empty($id)) {

                /** @var Order|null $order */
                $order = Order::with(['transaction'])->find($request->input('order_id'));

                $id = $order?->transaction?->payment_trx_id;

            }

            if (empty($order)) {
                $order = Order::with([
                    'transaction' => fn($q) => $q->where('payment_trx_id', $id)->whereNotNull('payment_trx_id')
                ])
                    ->whereHas('transactions', fn($q) => $q->where('payment_trx_id', $id)->whereNotNull('payment_trx_id'))
                    ->first();
            }

            $config = config('pay.alipay.default');

            $result = Pay::alipay($config)->find(['out_trade_no' => $id]);

            try {
                Log::error('$result', [$result]);
            } catch (Throwable){}

            if (
                data_get($result, 'trade_status') === 'TRADE_SUCCESS' ||
                data_get($result, 'trade_state') === 'SUCCESS'
            ) {

                $order->transaction?->update([
                    'status' => Transaction::STATUS_PAID,
                ]);

            } else if (data_get($result, 'trade_status') === 'NOTPAY') {

                $order->transaction?->update([
                    'status' => Transaction::STATUS_CANCELED,
                ]);

            }

            return $this->successResponse(__('errors.' . ResponseError::NO_ERROR, locale: $this->language), $order);
        } catch (Throwable $e) {

            $message = $e->getMessage() . ' ' . $e->getFile() . ' ' . $e->getLine() . ' ' . $e->getCode();

            Log::error("wechat: $message", $request->all());

            return $this->errorResponse(ResponseError::ERROR_400, __('errors.' . ResponseError::ERROR_400, locale: $this->language), 400);
        }

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
