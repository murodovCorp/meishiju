<?php

namespace App\Services\MenuService;

use App\Helpers\ResponseError;
use App\Models\Menu;
use App\Services\CoreService;
use App\Traits\SetTranslations;
use DB;
use Throwable;

class MenuService extends CoreService
{
    use SetTranslations;

    protected function getModelClass(): string
    {
        return Menu::class;
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

                /** @var Menu $model */
                $model = $this->model()->create($data);
                $model->products()->sync(data_get($data, 'products'));
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
     * @param string $uuid
     * @param array $data
     * @return array
     */
    public function update(string $uuid, array $data): array
    {
        try {
            /** @var Menu $model */
            $model = $this->model();

            $model = $model->where('uuid', $uuid)->first();

            if (empty($model)) {
                return ['status' => false, 'code' => ResponseError::ERROR_404];
            }

            $model = DB::transaction(function () use($model, $data) {

                $model->update($data);
                $model->products()->sync(data_get($data, 'products'));
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
     * @param int|null $shopId
     * @return array
     */
    public function delete(?array $ids = [], ?int $shopId = null): array
    {
        $models = Menu::whereIn('id', $ids)
            ->when($shopId, fn($q) => $q->where('shop_id', $shopId))
            ->get();

        $errorIds = [];

        foreach ($models as $model) {
            try {
                $model->delete();
            } catch (Throwable $e) {
                $this->error($e);
                $errorIds[] = $model->id;
            }
        }

        if (count($errorIds) === 0) {
            return ['status' => true, 'code' => ResponseError::NO_ERROR];
        }

        return [
            'status'  => false,
            'code'    => ResponseError::ERROR_505,
            'message' => __(
                'errors.' . ResponseError::CANT_DELETE_IDS,
                [
                    'ids' => implode(', ', $errorIds)
                ],
                $this->language
            )
        ];
    }

}
