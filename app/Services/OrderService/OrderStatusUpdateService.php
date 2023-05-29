<?php

namespace App\Services\OrderService;

use App\Helpers\ResponseError;
use App\Jobs\PayReferral;
use App\Models\Language;
use App\Models\Order;
use App\Models\OrderDetail;
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

        try {
            $order = DB::transaction(function () use ($order, $status) {

                if ($status == Order::STATUS_DELIVERED) {

//                    $this->sellerWalletTopUp($order);
//
//                    if (!empty($order->deliveryman)) {
//                        $this->deliverymanWalletTopUp($order);
//                    }

                    $this->adminWalletTopUp($order);

                    PayReferral::dispatchAfterResponse($order->user, 'increment');
                }

//                $cookingExist = Settings::adminSettings()->where('key', 'cooking_exist')->first()?->value;
//
//                if (!$cookingExist && $status == Order::STATUS_ACCEPTED) {
//
//                }

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
                            'note'   => 'For Order #' . $order->id,
                            'status' => WalletHistory::PAID,
                            'user'   => $user
                        ]);

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
            $userIds
        );

        return ['status' => true, 'code' => ResponseError::NO_ERROR, 'data' => $order];
    }

    /**
     * @param Order $order
     * @return void
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
//
//    /**
//     * @param Order $order
//     * @return void
//     */
//    private function sellerWalletTopUp(Order $order): void
//    {
//        $seller = $order->shop->seller;
//
//        if ($seller->wallet) {
//
//            $request = request()->merge([
//                'type'      => 'topup',
//                'price'     => $order->total_price - $order->delivery_fee - $order->commission_fee,
//                'note'      => "For Seller Order #$order->id",
//                'status'    => WalletHistory::PAID,
//                'user'      => $seller,
//            ])->all();
//
//            (new WalletHistoryService)->create($request);
//        }
//    }
//
//    /**
//     * @param Order $order
//     * @return void
//     */
//    private function deliverymanWalletTopUp(Order $order): void
//    {
//        $deliveryman = $order->deliveryMan;
//
//        if ($deliveryman->wallet) {
//
//            $request = request()->merge([
//                'type'      => 'topup',
//                'price'     => $order->delivery_fee,
//                'note'      => "For Deliveryman Order fee #$order->id",
//                'status'    => WalletHistory::PAID,
//                'user'      => $deliveryman,
//            ])->all();
//
//            (new WalletHistoryService)->create($request);
//        }
//
//    }
}
