<?php

namespace App\Services\OrderService;

use App\Helpers\ResponseError;
use App\Jobs\PayReferral;
use App\Models\Language;
use App\Models\Order;
use App\Models\OrderDetail;
use App\Models\Point;
use App\Models\PointHistory;
use App\Models\PushNotification;
use App\Models\Transaction;
use App\Models\Translation;
use App\Models\User;
use App\Models\WalletHistory;
use App\Services\CoreService;
use App\Services\WalletHistoryService\WalletHistoryService;
use App\Traits\Notification;
use DB;
use Exception;
use Log;
use Throwable;

class OrderStatusUpdateService extends CoreService
{
    use Notification;

    /**
     * @return string
     */
    protected function getModelClass(): string
    {
        return Order::class;
    }

    /**
     * @param Order $order
     * @param string|null $status
     * @param bool $isDelivery
     * @return array
     */
    public function statusUpdate(Order $order, ?string $status, bool $isDelivery = false): array
    {
        if ($order->status == $status) {
            return [
                'status'  => false,
                'code'    => ResponseError::ERROR_252,
                'message' => __('errors.' . ResponseError::ERROR_252, locale: $this->language)
            ];
        }

		$order = $order->fresh([
			'user',
			'pointHistories',
			'orderRefunds',
			'orderDetails',
			'transaction.paymentSystem',
		]);

        try {
            $order = DB::transaction(function () use ($order, $status) {

                if ($status == Order::STATUS_DELIVERED) {

                    $default = data_get(Language::languagesList()->where('default', 1)->first(), 'locale');

                    $tStatus = Translation::where(function ($q) use ($default) {
                        $q->where('locale', $this->language)->orWhere('locale', $default);
                    })
                        ->where('key', $status)
                        ->first()?->value;

                    $this->adminWalletTopUp($order);

					if ($order?->transaction?->paymentSystem?->tag == 'cash') {
						$order->transaction->update([
							'status' => Transaction::STATUS_PAID,
						]);
					}

					$order = $order->loadMissing([
						'coupon',
						'pointHistories',
					]);

                    $point = Point::getActualPoint($order->total_price, $order->shop_id);

                    if (!empty($point)) {
                        $token  = $order->user?->firebase_token;
                        $token = is_array($token) ? $token : [$token];

                        $this->sendNotification(
                            $token,
                            __('errors.' . ResponseError::ADD_CASHBACK, ['status' => !empty($tStatus) ? $tStatus : $status], $this->language),
                            $order->id,
                            [
                                'id'     => $order->id,
                                'status' => $order->status,
                                'type'   => PushNotification::ADD_CASHBACK
                            ],
                            [$order->user_id]
                        );

                        $order->pointHistories()->create([
                            'user_id'   => $order->user_id,
                            'price'     => $point,
                            'note'      => 'cashback',
                        ]);

                        $order->user?->wallet?->increment('price', $point);
                    }

                    PayReferral::dispatchAfterResponse($order->user, 'increment');
                }

                if ($status == Order::STATUS_CANCELED && $order->orderRefunds?->count() === 0) {

                    $user  = $order->user;
                    $trxId = $order->transactions->where('status', Transaction::STATUS_PAID)->first()?->id;

                    if (!$user?->wallet && $trxId) {
                        throw new Exception(__('errors.' . ResponseError::ERROR_108, locale: $this->language));
                    }

                    if ($trxId) {

                        (new WalletHistoryService)->create([
                            'type'   => 'topup',
                            'price'  => $order->total_price,
                            'note'   => 'Canceled Order #' . $order->id,
                            'status' => WalletHistory::PAID,
                            'user'   => $user
                        ]);

                    }

					$order->transaction?->update([
						'status' => Transaction::STATUS_CANCELED,
					]);

                    if ($order->pointHistories?->count() > 0) {
                        foreach ($order->pointHistories as $pointHistory) {
                            /** @var PointHistory $pointHistory */
                            $order->user?->wallet?->decrement('price', $pointHistory->price);
                            $pointHistory->delete();
                        }
                    }

                    if ($order->status === Order::STATUS_DELIVERED) {
                        PayReferral::dispatchAfterResponse($order->user, 'decrement');
                    }

                    $order->orderDetails->map(function (OrderDetail $orderDetail) {
                        $orderDetail->stock()->increment('quantity', $orderDetail->quantity);
                    });

                }

                $order->update([
                    'status'  => $status,
                    'current' => in_array($status, [Order::STATUS_DELIVERED, Order::STATUS_CANCELED]) ? 0 : $order->current,
                ]);

                return $order;
            });
        } catch (Throwable $e) {

            $this->error($e);

            return [
                'status'  => false,
                'code'    => ResponseError::ERROR_501,
                'message' => $e->getMessage()
            ];
        }

        /** @var Order $order */

        $order->loadMissing(['shop.seller', 'deliveryMan', 'user']);

        /** @var \App\Models\Notification $notification */
        $notification = $order->user?->notifications
            ?->where('type', \App\Models\Notification::ORDER_STATUSES)
            ?->first();

        if (in_array($order->status, ($notification?->payload ?? []))) {
            $userToken = $order->user?->firebase_token;
        }

        if (!$isDelivery) {
            $deliveryManToken = $order->deliveryMan?->firebase_token;
        }

        if (in_array($status, [Order::STATUS_ON_A_WAY, Order::STATUS_DELIVERED, Order::STATUS_CANCELED])) {
            $sellerToken = $order->shop?->seller?->firebase_token;
        }

        $firebaseTokens = array_merge(
            !empty($userToken) && is_array($userToken)        ? $userToken        : [],
            !empty($deliveryManToken) && is_array($deliveryManToken) ? $deliveryManToken : [],
            !empty($sellerToken) && is_array($sellerToken)           ? $sellerToken      : [],
        );

        $userIds = array_merge(
            !empty($userToken) && $order->user?->id         ? [$order->user?->id]          : [],
            !empty($deliveryManToken) && $order->deliveryMan?->id  ? [$order->deliveryMan?->id]   : [],
            !empty($sellerToken) && $order->shop?->seller?->id     ? [$order->shop?->seller?->id] : []
        );

        $default = data_get(Language::languagesList()->where('default', 1)->first(), 'locale');

        $tStatus = Translation::where(function ($q) use ($default) {
            $q->where('locale', $this->language)->orWhere('locale', $default);
        })
            ->where('key', $status)
            ->first()?->value;

        $this->sendNotification(
            array_values(array_unique($firebaseTokens)),
            __('errors.' . ResponseError::STATUS_CHANGED, ['status' => !empty($tStatus) ? $tStatus : $status], $this->language),
            $order->id,
            [
                'id'     => $order->id,
                'status' => $order->status,
                'type'   => PushNotification::STATUS_CHANGED
            ],
            $userIds,
            __('errors.' . ResponseError::STATUS_CHANGED, ['status' => !empty($tStatus) ? $tStatus : $status], $this->language),
        );

        return ['status' => true, 'code' => ResponseError::NO_ERROR, 'data' => $order];
    }

	/**
	 * @param Order $order
	 * @return void
	 * @throws Throwable
	 */
    private function adminWalletTopUp(Order $order): void
    {
        /** @var User $admin */
        $admin = User::with('wallet')->whereHas('roles', fn($q) => $q->where('name', 'admin'))->first();

        if (!$admin->wallet) {
            Log::error("admin #$admin?->id doesnt have wallet");
            return;
        }

        $request = request()->merge([
            'type'      => 'topup',
            'price'     => $order->total_price,
            'note'      => "For Seller Order #$order->id",
            'status'    => WalletHistory::PAID,
            'user'      => $admin,
        ])->all();

        (new WalletHistoryService)->create($request);
    }

}
