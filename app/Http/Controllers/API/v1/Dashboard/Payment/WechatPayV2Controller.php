<?php

namespace App\Http\Controllers\API\v1\Dashboard\Payment;

use App\Helpers\Helper;
use App\Helpers\ResponseError;
use App\Http\Controllers\Controller;
use App\Http\Requests\Payment\StripeRequest;
use App\Http\Resources\OrderResource;
use App\Services\PaymentService\WechatPayServiceV2;
use App\Traits\ApiResponse;
use App\Traits\Notification;
use App\Traits\OnResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Throwable;

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
        $result = $this->service->notify($request->all());

        if (!data_get($result, 'status')) {
            return $this->onErrorResponse($result);
        }

        return $this->successResponse(
            __('errors.' . ResponseError::NO_ERROR, locale: $this->language),
            OrderResource::make(data_get($result, 'data'))
        );
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
