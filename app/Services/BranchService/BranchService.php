<?php

namespace App\Services\BranchService;

use App\Helpers\ResponseError;
use App\Models\Branch;
use App\Services\CoreService;
use App\Traits\SetTranslations;
use DB;
use Throwable;

class BranchService extends CoreService
{
    use SetTranslations;

    protected function getModelClass(): string
    {
        return Branch::class;
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

                /** @var Branch $model */
                $model = $this->model()->create($data);
                $this->setTranslations($model, $data);

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
     * @param Branch $model
     * @param array $data
     * @return array
     */
    public function update(Branch $model, array $data): array
    {
        try {
            $model = DB::transaction(function () use($model, $data) {

                $model->update($data);
                $this->setTranslations($model, $data);

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
