<?php

namespace App\Services\ParcelOrderSettingService;

use App\Helpers\ResponseError;
use App\Models\ParcelOrderSetting;
use App\Services\CoreService;
use App\Traits\Notification;
use DB;
use Throwable;

class ParcelOrderSettingService extends CoreService
{
    use Notification;

    protected function getModelClass(): string
    {
        return ParcelOrderSetting::class;
    }

    /**
     * @param array $data
     * @return array
     */
    public function create(array $data): array
    {
        try {
            $model = DB::transaction(function () use ($data) {

                /** @var ParcelOrderSetting $model */
                $model = $this->model()->create($data);

                if (data_get($data, 'images.0')) {
                    $model->update(['img' => data_get($data, 'images.0')]);
                    $model->uploads(data_get($data, 'images'));
                }

                if (is_array(data_get($data, 'options.*'))) {
                    $model->parcelOptions()->sync(data_get($data, 'options'));
                }

                return $model;
            });

            return [
                'status'    => true,
                'message'   => ResponseError::NO_ERROR,
                'data'      => $model
            ];
        } catch (Throwable $e) {
            $this->error($e);

            return [
                'status'    => false,
                'message'   => __('errors.' . ResponseError::ERROR_501, locale: $this->language),
                'code'      => $e->getMessage()
            ];
        }
    }

    /**
     * @param ParcelOrderSetting $model
     * @param array $data
     * @return array
     */
    public function update(ParcelOrderSetting $model, array $data): array
    {
        try {
            $model = DB::transaction(function () use ($data, $model) {

                $model->update($data);

                if (data_get($data, 'images.0')) {

                    $model->galleries()->delete();
                    $model->update(['img' => data_get($data, 'images.0')]);
                    $model->uploads(data_get($data, 'images'));

                }

                if (is_array(data_get($data, 'options.*'))) {
                    $model->parcelOptions()->sync(data_get($data, 'options'));
                }

                return $model;
            });

            return [
                'status' => true,
                'message' => ResponseError::NO_ERROR,
                'data' => $model
            ];

        } catch (Throwable $e) {
            $this->error($e);
            return [
                'status'    => false,
                'message'   => __('errors.' . ResponseError::ERROR_502, locale: $this->language),
                'code'      => ResponseError::ERROR_502
            ];
        }
    }

    /**
     * @param array|null $ids
     * @param int|null $shopId
     * @return array
     */
    public function destroy(?array $ids = [], ?int $shopId = null): array
    {
        $errors = [];

        foreach (ParcelOrderSetting::find(is_array($ids) ? $ids : []) as $model) {
            try {
                $model->parcelOptions()->sync([]);
                $model->delete();
            } catch (Throwable $e) {
                $errors[] = $model->id;

                $this->error($e);
            }
        }

        return $errors;
    }

}
