<?php

namespace App\Http\Controllers\API\v1\Rest;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Services\Yandex\YandexService;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use Log;

class YandexController extends Controller
{
    use ApiResponse;

    public function __construct(private YandexService $service)
    {
        parent::__construct();
    }

    /**
     * @param Request $request
     * @return void
     */
    public function webhook(Request $request): void
    {
        /** @var Order $order */
        $order = Order::whereJsonContains('yandex->id', $request->input('claim_id'))->first();

        if (empty($order)) {
            Log::error('empty yandex', $request->all());
            return;
        }

        $yandex = $order->yandex;
        $yandex['status'] = $request->input('status');

        $order->update([
            'yandex' => $yandex
        ]);

        if ($request->input('status') === 'ready_for_approval' && $order->status !== Order::STATUS_CANCELED) {
            (new YandexService)->acceptOrder($order);
        }

        if ($request->input('status') === 'performer_found' && $order->status !== Order::STATUS_CANCELED) {
            (new YandexService)->orderDriverVoiceForwarding($order);
        }

        if ($request->input('status') === 'pickuped' && $order->status !== Order::STATUS_CANCELED) {
            $order->update([
                'status' => Order::STATUS_ON_A_WAY
            ]);
        }

        if ($request->input('status') === 'delivered' && $order->status !== Order::STATUS_CANCELED) {
            $order->update([
                'status' => Order::STATUS_DELIVERED
            ]);
        }

        if (in_array($request->input('status'), array_merge( (new YandexService)->canceledStatuses, (new YandexService)->returnedStatuses))) {
            $order->update([
                'status' => Order::STATUS_CANCELED
            ]);
        }

        Log::error('yandex', $request->all());
    }

}
