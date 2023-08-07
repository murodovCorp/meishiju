<?php

namespace App\Http\Controllers\API\v1\Dashboard\Payment;

use App\Helpers\Helper;
use App\Http\Controllers\Controller;
use App\Http\Requests\Payment\StripeRequest;
use App\Services\PaymentService\WechatPayServiceV2;
use App\Traits\ApiResponse;
use App\Traits\OnResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Throwable;
use Yansongda\Pay\Pay;

class WechatPayV2Controller extends Controller
{
    use OnResponse, ApiResponse;

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
            $arr['order_number'] = Helper::generateNumber("HE", 20);;
            $arr['pay_amount'] = 1;
            $arr['title'] = "测试微信付款";
            $arr['openid'] = $request->openId;

            $result = $this->service->pay($arr);

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
     */
    public function notify(Request $request)
    {
        $config = config('pay.wechat.default');
        $wechat = Pay::wechat($config);
        $data = $wechat->callback(); // 是的，验签就这么简单！
        Log::info('微信回调：notify', [$data]);
        return $this->successResponse('success', $data);
    }


    //拿到code
    public function getOpenId(Request $request)
    {
        $appId = getenv('WX_APPID');
        $secret = getenv('WX_SECRET');
        $code = $request->code;
        //获取code,在获取openId
        if (empty($code)) {
            $redirectUri = urlencode(getenv("APP_URL") . "/api/wechat-getOpenId");
            $url = 'https://open.weixin.qq.com/connect/oauth2/authorize?appid=' . $appId . '&redirect_uri=' . $redirectUri . '&response_type=code&scope=snsapi_userinfo&state=STATE#wechat_redirect';
            header("location:$url");
            exit;
        } else {
            $url = 'https://api.weixin.qq.com/sns/oauth2/access_token?appid=' . $appId . '&secret=' . $secret . '&code=' . $code . '&grant_type=authorization_code';
            $data = Helper::requestGet($url);
            return $this->successResponse('success', $data);
        }
    }


}
