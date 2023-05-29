<?php

namespace App\Services\Booking\ShopBookingWorkingDayService;

use App\Helpers\ResponseError;
use App\Models\Booking\BookingShop;
use App\Models\Booking\ShopBookingWorkingDay;
use App\Services\CoreService;
use Throwable;

class ShopBookingWorkingDayService extends CoreService
{
    protected function getModelClass(): string
    {
        return ShopBookingWorkingDay::class;
    }

    /**
     * @param array $data
     * @return array
     */
    public function create(array $data): array
    {
        try {

            foreach (data_get($data, 'dates', []) as $date) {

                $date['shop_id'] = data_get($data, 'shop_id');
                $date['deleted_at'] = null;

                ShopBookingWorkingDay::withTrashed()->updateOrCreate([
                    ['shop_id', data_get($data, 'shop_id')],
                    ['day',     data_get($date, 'day')]
                ], $date);

            }

            return [
                'status'  => true,
                'message' => ResponseError::NO_ERROR,
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

    public function update(int $shopId, array $data): array
    {
        try {

            BookingShop::find($shopId)->bookingWorkingDays()->forceDelete();

            foreach (data_get($data, 'dates', []) as $date) {

                ShopBookingWorkingDay::create($date + ['shop_id' => $shopId]);

            }

            return [
                'status'  => true,
                'message' => ResponseError::NO_ERROR,
            ];

        } catch (Throwable $e) {

            $this->error($e);

            return [
                'status'  => false,
                'code'    => ResponseError::ERROR_502,
                'message' => __('errors.' . ResponseError::ERROR_502, locale: $this->language)
            ];
        }
    }

    public function delete(?array $ids = [], ?int $shopId = null) {

        $models = ShopBookingWorkingDay::when($shopId, function($q, $shopId) use ($ids) {
            $q->where('shop_id', $shopId)->find($ids);
        });

        foreach ($models as $model) {
            $model->delete();
        }

    }
}
