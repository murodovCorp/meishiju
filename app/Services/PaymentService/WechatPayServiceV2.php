<?php

namespace App\Services\PaymentService;

use App\Helpers\Helper;
use App\Helpers\ResponseError;
use App\Models\Currency;
use App\Models\Order;
use Log;
use Yansongda\Pay\Pay;
use Yansongda\Supports\Collection;

/**
 * Class WechatPayServiceV2
 * @package App\Services\PaymentService
 */
class WechatPayServiceV2
{

    /**
     * 公众号h5支付
     *
     * @param $data
     * @return array|Collection
     */
    public function pay($data): Collection|array
    {
        $order = Order::find(data_get($data, 'order_id'));

        if (empty($order)) {
            return [
                'status'  => false,
                'code'    => ResponseError::ERROR_404,
                'message' => 'Order not found'
            ];
        }

        $data = [];

        $cny = Currency::where('title', 'CNY')->first();

        $totalPrice = ceil($cny->id == $order->currency_id ? $order->total_price : $order->total_price * ($cny?->rate ?: 1));

        $data['order_number']   = Helper::generateNumber("HE", 20);
        $data['title']          = "测试微信付款";
        $data['out_trade_no']   = time().'';
        $data['pay_amount']     = $totalPrice;
        $data['description']    = "按訂單付款";
        $data['amount']         = [
            'total' => $totalPrice,
        ];

        Log::error('data', $data);
        return Pay::wechat(config('pay.wechat'))->app($data);
    }

}
