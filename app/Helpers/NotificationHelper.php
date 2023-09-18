<?php

namespace App\Helpers;

use App\Models\Language;
use App\Models\Order;
use App\Models\ParcelOrder;
use App\Models\PushNotification;
use App\Models\Settings;
use App\Models\Translation;

class NotificationHelper
{
    use \App\Traits\Notification;

    public function deliveryManOrder(Order $order, string $type = 'deliveryman'): array
    {
        $km = (new Utility)->getDistance(
            $order->shop?->location,
            $order->location,
        );

        $second = Settings::adminSettings()->where('key', 'deliveryman_order_acceptance_time')->first();

        return [
            'second'    => data_get($second, 'value', 30),
            'order'     => [
                'id'        => $order->id,
                'status'    => $order->status,
                'km'        => $km,
                'type'      => $type
            ],
        ];
    }

    public function deliveryManParcelOrder(ParcelOrder $parcelOrder, string $type = 'deliveryman'): array
    {
        $km = (new Utility)->getDistance(
            $parcelOrder->address_from,
            $parcelOrder->address_to,
        );

        $second = Settings::adminSettings()->where('key', 'deliveryman_order_acceptance_time')->first();

        return [
            'second'    => data_get($second, 'value', 30),
            'order'     => [
                'id'        => $parcelOrder->id,
                'status'    => $parcelOrder->status,
                'km'        => $km,
                'type'      => $type
            ],
        ];
    }

    public function autoAcceptNotification(Order $order, string $lang, string $status): void
    {
        /** @var \App\Models\Notification $notification */
        $notification = $order->user?->notifications
            ?->where('type', \App\Models\Notification::ORDER_STATUSES)
            ?->first();

        if (in_array($order->status, ($notification?->payload ?? []))) {
            $userToken = $order->user?->firebase_token;
        }

        $firebaseTokens = array_merge(
            !empty($userToken) && is_array($userToken) ? $userToken : [],
        );

        $userIds = array_merge(
            !empty($userToken) && $order->user?->id ? [$order->user?->id] : [],
        );

        $default = data_get(Language::languagesList()->where('default', 1)->first(), 'locale');

        $tStatus = Translation::where(function ($q) use ($default, $lang) {
            $q->where('locale', $lang)->orWhere('locale', $default);
        })
            ->where('key', $status)
            ->first()?->value;

        $this->sendNotification(
            array_values(array_unique($firebaseTokens)),
            __('errors.' . ResponseError::STATUS_CHANGED, ['status' => !empty($tStatus) ? $tStatus : $status], $lang),
            $order->id,
            [
                'id'     => $order->id,
                'status' => $order->status,
                'type'   => PushNotification::STATUS_CHANGED
            ],
            $userIds,
            __('errors.' . ResponseError::STATUS_CHANGED, ['status' => !empty($tStatus) ? $tStatus : $status], $lang),
        );

    }
}
