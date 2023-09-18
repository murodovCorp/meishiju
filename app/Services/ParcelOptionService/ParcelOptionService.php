<?php

namespace App\Services\ParcelOptionService;

use App\Helpers\ResponseError;
use App\Models\ParcelOption;
use App\Services\CoreService;
use App\Traits\Notification;
use App\Traits\SetTranslations;
use DB;
use Throwable;

class ParcelOptionService extends CoreService
{
    use Notification, SetTranslations;

    protected function getModelClass(): string
    {
        return ParcelOption::class;
    }

    /**
     * @param array $data
     * @return array
     */
    public function create(array $data): array
    {
        try {
            $model = DB::transaction(function () use ($data) {

                /** @var ParcelOption $model */
                $model = $this->model()->create($data);

                $this->setTranslations($model, $data, false);

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
     * @param ParcelOption $model
     * @param array $data
     * @return array
     */
    public function update(ParcelOption $model, array $data): array
    {
        try {
            $model = DB::transaction(function () use ($data, $model) {

                $model->update($data);

                $this->setTranslations($model, $data, false);

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

        foreach (ParcelOption::find(is_array($ids) ? $ids : []) as $model) {
            try {
                $model->translations()->delete();
                $model->delete();
            } catch (Throwable $e) {
                $errors[] = $model->id;

                $this->error($e);
            }
        }

        return $errors;
    }

}
