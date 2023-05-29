<?php

namespace App\Services\CareerService;

use App\Helpers\ResponseError;
use App\Models\Career;
use App\Services\CoreService;
use App\Traits\SetTranslations;
use DB;
use Throwable;

class CareerService extends CoreService
{
    use SetTranslations;

    protected function getModelClass(): string
    {
        return Career::class;
    }

    /**
     * Create a new Shop model.
     * @param array $data
     * @return array
     */
    public function create(array $data): array
    {
        try {
            $model = DB::transaction(function () use($data) {

                /** @var Career $model */
                $model = $this->model()->create($data);
                $this->setTranslations($model, $data, hasAddress: true);

                return $model;
            });

            return [
                'status' => true,
                'code'   => ResponseError::NO_ERROR,
                'data'   => $model
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
     * Update specified Shop model.
     * @param Career $model
     * @param array $data
     * @return array
     */
    public function update(Career $model, array $data): array
    {
        try {
            $model = DB::transaction(function () use($model, $data) {

                $model->update($data);
                $this->setTranslations($model, $data, hasAddress: true);

                return $model;
            });

            return [
                'status' => true,
                'code'   => ResponseError::NO_ERROR,
                'data'   => $model
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

    /**
     * Delete model.
     * @param array|null $ids
     * @return array
     */
    public function delete(?array $ids = []): array
    {
        return $this->remove($ids);
    }

}
