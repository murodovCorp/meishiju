<?php

namespace App\Services\ParcelOrderService;

use App\Helpers\NotificationHelper;
use App\Helpers\ResponseError;
use App\Helpers\Utility;
use App\Models\ParcelOrder;
use App\Models\ParcelOrderSetting;
use App\Models\PushNotification;
use App\Models\Currency;
use App\Models\User;
use App\Services\CoreService;
use App\Traits\Notification;
use DB;
use Exception;
use Throwable;

class ParcelOrderService extends CoreService
{
    use Notification;

    protected function getModelClass(): string
    {
        return ParcelOrder::class;
    }

    /**
     * @param array $data
     * @return array
     */
    public function create(array $data): array
    {
        try {
            $parcelOrder = DB::transaction(function () use ($data) {

                /** @var ParcelOrder $parcelOrder */
                $parcelOrder = $this->model()->create($this->setOrderParams($data));

                if (data_get($data, 'images.0')) {
                    $parcelOrder->update(['img' => data_get($data, 'images.0')]);
                    $parcelOrder->uploads(data_get($data, 'images'));
                }

                return $parcelOrder;
            });

            return [
                'status'    => true,
                'message'   => ResponseError::NO_ERROR,
                'data'      => $parcelOrder->fresh([
                    'user',
                    'deliveryman.deliveryManSetting',
                    'currency' => fn($q) => $q->select('id', 'title', 'symbol'),
                    'galleries',
                ])
            ];
        } catch (Throwable $e) {
            $this->error($e);

            if ($e->getCode() === ResponseError::ERROR_433) {
                return [
                    'status'    => false,
                    'message'   => $e->getMessage(),
                    'code'      => $e->getCode()
                ];
            }

            return [
                'status'    => false,
                'message'   => __('errors.' . ResponseError::ERROR_501, locale: $this->language),
                'code'      => $e->getMessage()
            ];
        }
    }

    /**
     * @param ParcelOrder $parcelOrder
     * @param array $data
     * @return array
     */
    public function update(ParcelOrder $parcelOrder, array $data): array
    {
        try {
            $parcelOrder = DB::transaction(function () use ($data, $parcelOrder) {

                $parcelOrder->update($this->setOrderParams($data));

                if (data_get($data, 'images.0')) {

                    $parcelOrder->galleries()->delete();
                    $parcelOrder->update(['img' => data_get($data, 'images.0')]);
                    $parcelOrder->uploads(data_get($data, 'images'));

                }

                return $parcelOrder;
            });

            return [
                'status' => true,
                'message' => ResponseError::NO_ERROR,
                'data' => $parcelOrder->fresh([
                    'user',
                    'deliveryman.deliveryManSetting',
                    'currency' => fn($c) => $c->select('id', 'title', 'symbol'),
                    'transaction.paymentSystem',
                    'galleries',
                ])
            ];

        } catch (Throwable $e) {
            $this->error($e);

            if ($e->getCode() === ResponseError::ERROR_433) {
                return [
                    'status'    => false,
                    'message'   => $e->getMessage(),
                    'code'      => $e->getCode()
                ];
            }

            return [
                'status'    => false,
                'message'   => __('errors.' . ResponseError::ERROR_502, locale: $this->language),
                'code'      => ResponseError::ERROR_502
            ];
        }
    }

    /**
     * @param int|null $id
     * @param int $deliveryman
     * @return array
     */
    public function updateDeliveryMan(?int $id, int $deliveryman): array
    {
        try {
            /** @var ParcelOrder $parcelOrder */
            $parcelOrder = ParcelOrder::find($id);

            if (!$parcelOrder) {
                return [
                    'status'  => false,
                    'code'    => ResponseError::ERROR_404,
                    'message' => __('errors.' . ResponseError::ERROR_404, locale: $this->language)
                ];
            }

            /** @var User $user */
            $user = User::with('deliveryManSetting')->find($deliveryman);

            if (!$user || !$user->hasRole('deliveryman')) {
                return [
                    'status'  => false,
                    'code'    => ResponseError::ERROR_211,
                    'message' => __('errors.' . ResponseError::ERROR_211, locale: $this->language)
                ];
            }

            $parcelOrder->update([
                'deliveryman_id' => $user->id,
            ]);

            $this->sendNotification(
                is_array($user->firebase_token) ? $user->firebase_token : [$user->firebase_token],
                __('errors.' . ResponseError::NEW_ORDER, ['id' => $parcelOrder->id], $this->language),
                $parcelOrder->id,
                (new NotificationHelper)->deliveryManParcelOrder($parcelOrder, PushNotification::NEW_PARCEL_ORDER),
                [$user->id]
            );

            return [
                'status'    => true,
                'message'   => ResponseError::NO_ERROR,
                'data'      => $parcelOrder,
                'user'      => $user
            ];
        } catch (Throwable $e) {
            $this->error($e);
            return [
                'status'  => false,
                'code'    => ResponseError::ERROR_501,
                'message' => __('errors.' . ResponseError::ERROR_501, locale: $this->language)
            ];
        }
    }

    /**
     * @param int|null $id
     * @return array
     */
    public function attachDeliveryMan(?int $id): array
    {
        try {
            /** @var ParcelOrder $parcelOrder */
            $parcelOrder = ParcelOrder::with('user')->find($id);

            if (!empty($parcelOrder->deliveryman)) {
                return [
                    'status'    => false,
                    'code'      => ResponseError::ERROR_210,
                    'message'   => __('errors.' . ResponseError::ERROR_210, locale: $this->language)
                ];
            }

            $parcelOrder->update([
                'deliveryman_id' => auth('sanctum')->id(),
            ]);

            return ['status' => true, 'message' => ResponseError::NO_ERROR, 'data' => $parcelOrder];
        } catch (Throwable) {
            return [
                'status'  => false,
                'code'    => ResponseError::ERROR_502,
                'message' => __('errors.' . ResponseError::ERROR_502, locale: $this->language)
            ];
        }
    }

    /**
     * @param array $data
     * @return array
     * @throws Exception
     */
    private function setOrderParams(array $data): array
    {
        $defaultCurrencyId = Currency::whereDefault(1)->first('id');

        $currencyId         = data_get($data, 'currency_id', data_get($defaultCurrencyId, 'id'));
        $deliveryFeeRate    = 0;
        $km                 = 0;

        if (data_get($data, 'address_from') && data_get($data, 'address_to')) {

            $type   = ParcelOrderSetting::find(data_get($data, 'type_id'));

            $helper = new Utility;
            $km     = $helper->getDistance(data_get($data, 'address_from'), data_get($data, 'address_to'));

            if ($km > $type->max_range) {
                throw new Exception(
                    __('errors.' . ResponseError::NOT_IN_PARCEL_POLYGON, ['km' => $type->max_range], $this->language),
                    ResponseError::ERROR_433
                );
            }

            $deliveryFee = $helper->getParcelPriceByDistance($type, $km, data_get($data, 'rate', 1));

            $deliveryFeeRate = $deliveryFee / data_get($data, 'rate', 1);
        }

        return [
            'user_id'           => data_get($data, 'user_id', auth('sanctum')->id()),
            'total_price'       => max($deliveryFeeRate, 0),
            'currency_id'       => $currencyId,
            'type_id'           => data_get($data, 'type_id'),
            'rate'              => data_get($data, 'rate'),
            'note'              => data_get($data, 'note'),
            'tax'               => 0,
            'status'            => data_get($data, 'status', 'new'),
            'qr_value'          => data_get($data, 'qr_value'),
            'instruction'       => data_get($data, 'instruction'),
            'description'       => data_get($data, 'description'),
            'notify'            => data_get($data, 'notify', false),
            'option'            => data_get($data, 'option'),

            'address_from'      => data_get($data, 'address_from'),
            'phone_from'        => data_get($data, 'phone_from'),
            'username_from'     => data_get($data, 'username_from'),

            'address_to'        => data_get($data, 'address_to'),
            'phone_to'          => data_get($data, 'phone_to'),
            'username_to'       => data_get($data, 'username_to'),

            'delivery_fee'      => max($deliveryFeeRate, 0),
            'km'                => max($km, 0),
            'deliveryman_id'    => data_get($data, 'deliveryman_id'),
            'delivery_date'     => data_get($data, 'delivery_date'),
            'delivery_time'     => data_get($data, 'delivery_time'),
        ];
    }

    /**
     * @param array|null $ids
     * @param int|null $shopId
     * @return array
     */
    public function destroy(?array $ids = [], ?int $shopId = null): array
    {
        $errors = [];

        foreach (ParcelOrder::find(is_array($ids) ? $ids : []) as $model) {
            try {
                $model->delete();
            } catch (Throwable $e) {
                $errors[] = $model->id;

                $this->error($e);
            }
        }

        return $errors;
    }

    /**
     * @param int $id
     * @param int|null $userId
     * @return array
     */
    public function setCurrent(int $id, ?int $userId = null): array
    {
        $errors = [];

        $parcelOrders = ParcelOrder::when($userId, fn($q) => $q->where('deliveryman', $userId))
            ->where('current', 1)
            ->orWhere('id', $id)
            ->get();

        $getOrder = new ParcelOrder;

        foreach ($parcelOrders as $parcelOrder) {

            try {

                if ($parcelOrder->id === $id) {
                    $getOrder = $parcelOrder;
                }

                $parcelOrder->update([
                    'current' => $parcelOrder->id === $id,
                ]);

            } catch (Throwable $e) {
                $errors[] = $parcelOrder->id;

                $this->error($e);
            }

        }

        return count($errors) === 0 ? [
            'status' => true,
            'code' => ResponseError::NO_ERROR,
            'data' => $getOrder
        ] : [
            'status'  => false,
            'code'    => ResponseError::ERROR_400,
            'message' => __(
                'errors.' . ResponseError::CANT_UPDATE_ORDERS,
                [
                    'ids' => implode(', #', $errors)
                ],
                $this->language
            )
        ];
    }
}
