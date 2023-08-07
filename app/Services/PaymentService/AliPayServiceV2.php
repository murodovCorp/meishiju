<?php

namespace App\Services\PaymentService;

use App\Helpers\Helper;
use App\Helpers\ResponseError;
use App\Models\Currency;
use App\Models\Order;
use Psr\Http\Message\ResponseInterface;
use Yansongda\Pay\Pay;

class AliPayServiceV2
{

    public function preparePay(array $data): array
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

        $data['order_number'] = Helper::generateNumber("HE", 20);
        $data['pay_amount']   = $totalPrice;
        $data['title']        = "按訂單付款";

        $order->update([
            'total_price' => $totalPrice,
        ]);

        return [
            'status' => true,
            'code'   => ResponseError::NO_ERROR,
            'data'   => $this->alipay($data),
        ];
    }

    private function alipay($data): ResponseInterface
    {
        $config = config('pay.alipay');

        $order = [
            'out_trade_no'  => $data['order_number'],
            'total_amount'  => $data['pay_amount'],
            'subject'       => rawurlencode($data['title']),
            '_config'       => 'default',
        ];

        return Pay::alipay($config)->wap($order);
    }

}