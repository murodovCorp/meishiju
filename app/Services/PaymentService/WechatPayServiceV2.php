<?php

namespace App\Services\PaymentService;

use App\Helpers\ResponseError;
use App\Models\Currency;
use App\Models\Order;
use Yansongda\Pay\Pay;
use Yansongda\Supports\Collection;

/**
 * Class WechatPayServiceV2
 * @package App\Services\PaymentService
 */
class WechatPayServiceV2 extends BaseService
{

    /**
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
                'message' => __('errors.' . ResponseError::ORDER_NOT_FOUND, locale: $this->language)
            ];
        }

        $data = [];

        $cny = Currency::where('title', 'CNY')->first();

        $totalPrice = ceil($cny->id == $order->currency_id ? $order->total_price * $order->rate : $order->total_price * $cny->rate);

        $data['out_trade_no']   = time().'';
        $data['description']    = "按訂單付款";
        $data['amount']         = [
            'total' => ceil($totalPrice * 100),
        ];

        return Pay::wechat(config('pay.wechat'))->app($data);
    }

}
