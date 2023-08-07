<?php

namespace App\Services\PaymentService;

use Yansongda\Pay\Pay;

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
     * @return \Yansongda\Supports\Collection
     */
    public function pay($data)
    {
        $order = [
            'out_trade_no' => $data['order_number'],
            'description' => $data['title'],
            'amount' => [
                'total' => $data['pay_amount'],
            ],
            // 'payer' => [
            //     'openid' => $data['openid'],
            // ],
              'scene_info' => [
                    'payer_client_ip' => $_SERVER["REMOTE_ADDR"],
                    'h5_info' => [
                        'type' => 'Wap',
                    ]       
                 ],
            '_config' => 'default',
        ];
        

        // return Pay::wechat(config('pay.wechat'))->wap($order);
        return Pay::wechat(config('pay.wechat'))->wap($order);
    }

    /* public function miniPay($data)
     {
         $config = [];
         // 判断支付来源
         if ($data['pay_source'] == LogPay::PAY_SOURCE_SBL_XCX) {
             // 圣贝拉
             $config = config('pay.sblminipay');
         } elseif ($data['pay_source'] == LogPay::PAY_SOURCE_BBL_XCX) {
             // 小贝拉
             $config = config('pay.xblminipay');
         }
         $order = [
             'out_trade_no' => $data['order_number'],
             'body' => $data['title'],
             'total_fee' => $data['pay_amount'],
             'openid' => $data['openid'],
         ];

         try {
             return Pay::wechat($config)->miniapp($order);
         } catch (\Exception $e) {
             throw new RestfulException($e->getMessage(), 422);
         }

     }*/

}
