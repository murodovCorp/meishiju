<?php

namespace App\Services\PaymentService;

use App\Helpers\Helper;
use App\Helpers\ResponseError;
use App\Models\Currency;
use App\Models\Order;
use App\Models\Payment;
use Psr\Http\Message\ResponseInterface;
use Yansongda\Pay\Pay;

class AliPayServiceV2 extends BaseService
{

    public function preparePay(array $data): array
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

        $data['order_number'] = Helper::generateNumber("HE", 20);
        $data['pay_amount']   = 0.01;
        $data['title']        = "按訂單付款";

        $order->createTransaction([
            'payment_trx_id'        => $data['order_number'],
            'price'                 => $totalPrice,
            'user_id'               => $order->user_id,
            'payment_sys_id'        => Payment::where('tag', 'alipay')->first()?->id,
            'note'                  => $order->id,
            'perform_time'          => now(),
            'status_description'    => 'Transaction for order #' . $order->id
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

        return Pay::alipay($config)->app($order);
    }

}
