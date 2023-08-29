<?php

namespace App\Http\Controllers\API\v1\Dashboard\Payment;

use App\Helpers\Helper;
use App\Helpers\ResponseError;
use App\Http\Controllers\Controller;
use App\Http\Requests\Payment\StripeRequest;
use App\Models\Order;
use App\Models\Transaction;
use App\Services\PaymentService\WechatPayServiceV2;
use App\Traits\ApiResponse;
use App\Traits\Notification;
use App\Traits\OnResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Throwable;
use Yansongda\Pay\Pay;

class WechatPayV2Controller extends Controller
{
    use OnResponse, ApiResponse, Notification;

    public function __construct(private WechatPayServiceV2 $service)
    {
        parent::__construct();
    }

    /**
     * process transaction.
     *
     * @param StripeRequest $request
     * @return JsonResponse
     */
    public function prepay(Request $request): JsonResponse
    {
        try {
            $result = $this->service->pay($request->all());

            if (data_get($result, 'status')) {
                return $this->onErrorResponse($result);
            }

            return $this->successResponse('success', $result);
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
            Log::error('wechat', $request->all());
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

            $config = config('pay.wechat.default');

            $result = Pay::wechat($config)->find(['out_trade_no' => $id]);

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

    /**
     * @param Request $request
     * @return JsonResponse|void
     */
    public function getOpenId(Request $request)
    {
        $appId  = getenv('WX_APPID');
        $secret = getenv('WX_SECRET');
        $code   = $request->input('code');

        if (empty($code)) {

            $redirectUri = urlencode(getenv("APP_URL") . "/api/wechat-getOpenId");
            $url = 'https://open.weixin.qq.com/connect/oauth2/authorize?appid=' . $appId . '&redirect_uri=' . $redirectUri . '&response_type=code&scope=snsapi_userinfo&state=STATE#wechat_redirect';
            header("location:$url");
            exit;

        }

        $url = 'https://api.weixin.qq.com/sns/oauth2/access_token?appid=' . $appId . '&secret=' . $secret . '&code=' . $code . '&grant_type=authorization_code';
        $data = Helper::requestGet($url);

        return $this->successResponse('success', $data);
    }

}
