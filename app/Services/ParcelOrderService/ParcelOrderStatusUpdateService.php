<?php

namespace App\Services\ParcelOrderService;

use App\Helpers\ResponseError;
use App\Models\Language;
use App\Models\ParcelOrder;
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

class ParcelOrderStatusUpdateService extends CoreService
{
    use Notification;

    /**
     * @return string
     */
    protected function getModelClass(): string
    {
        return ParcelOrder::class;
    }

    /**
     * @param ParcelOrder $model
     * @param string|null $status
     * @param bool $isDelivery
     * @return array
     */
    public function statusUpdate(ParcelOrder $model, ?string $status, bool $isDelivery = false): array
    {
        if ($model->status == $status) {
            return [
                'status'  => false,
                'code'    => ResponseError::ERROR_252,
                'message' => __('errors.' . ResponseError::ERROR_252, locale: $this->language)
            ];
        }

        try {
            $model = DB::transaction(function () use ($model, $status) {

                if ($status == ParcelOrder::STATUS_DELIVERED) {

                    $this->adminWalletTopUp($model);

                }

                if ($status == ParcelOrder::STATUS_CANCELED) {

                    $user  = $model->user;
                    $trxId = $model->transactions->where('status', Transaction::STATUS_PAID)->first()?->id;

                    if (!$user?->wallet && $trxId) {
                        throw new Exception(__('errors.' . ResponseError::ERROR_108, locale: $this->language));
                    }

                    if ($trxId) {

                        (new WalletHistoryService)->create([
                            'type'   => 'topup',
                            'price'  => $model->total_price,
                            'note'   => 'Parcel order #' . $model->id . ' canceled',
                            'status' => WalletHistory::PAID,
                            'user'   => $user
                        ]);

                    }

                }

                $model->update([
                    'status'  => $status,
                    'current' => in_array($status, [ParcelOrder::STATUS_DELIVERED, ParcelOrder::STATUS_CANCELED]) ? 0 : $model->current,
                ]);

                return $model;
            });
        } catch (Throwable $e) {

            $this->error($e);

            return [
                'status'  => false,
                'code'    => ResponseError::ERROR_501,
                'message' => $e->getMessage()
            ];
        }

        /** @var ParcelOrder $model */

        $model->loadMissing(['deliveryman', 'user']);

        /** @var \App\Models\Notification $notification */
        $notification = $model->user?->notifications
            ?->where('type', \App\Models\Notification::ORDER_STATUSES)
            ?->first();

        if (in_array($model->status, ($notification?->payload ?? []))) {
            $userToken = $model->user?->firebase_token;
        }

        if (!$isDelivery) {
            $deliveryManToken = $model->deliveryman?->firebase_token;
        }

        $firebaseTokens = array_merge(
            !empty($userToken) && is_array($userToken)        ? $userToken        : [],
            !empty($deliveryManToken) && is_array($deliveryManToken) ? $deliveryManToken : [],
        );

        $userIds = array_merge(
            !empty($userToken) && $model->user?->id         ? [$model->user?->id]        : [],
            !empty($deliveryManToken) && $model->deliveryman?->id  ? [$model->deliveryman?->id] : [],
        );

        $default = data_get(Language::languagesList()->where('default', 1)->first(), 'locale');

        $tStatus = Translation::where(function ($q) use ($default) {
            $q->where('locale', $this->language)->orWhere('locale', $default);
        })
            ->where('key', $status)
            ->first()
            ?->value;

        $this->sendNotification(
            array_values(array_unique($firebaseTokens)),
            __('errors.' . ResponseError::STATUS_CHANGED, ['status' => !empty($tStatus) ? $tStatus : $status], $this->language),
            $model->id,
            [
                'id'     => $model->id,
                'status' => $model->status,
                'type'   => PushNotification::STATUS_CHANGED
            ],
            $userIds
        );

        return ['status' => true, 'code' => ResponseError::NO_ERROR, 'data' => $model];
    }

    /**
     * @param ParcelOrder $model
     * @return void
     */
    private function adminWalletTopUp(ParcelOrder $model): void
    {
        /** @var User $admin */
        $admin = User::with('wallet')->whereHas('roles', fn($q) => $q->where('name', 'admin'))->first();

        if (!$admin->wallet) {
            Log::error("admin #$admin?->id doesnt have wallet");
            return;
        }

        $request = request()->merge([
            'type'      => 'topup',
            'price'     => $model->total_price,
            'note'      => "For ParcelOrder #$model->id",
            'status'    => WalletHistory::PAID,
            'user'      => $admin,
        ])->all();

        (new WalletHistoryService)->create($request);
    }

}
