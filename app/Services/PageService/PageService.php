<?php

namespace App\Services\PageService;

use App\Helpers\ResponseError;
use App\Models\Page;
use App\Services\CoreService;
use App\Traits\SetTranslations;
use DB;
use Throwable;

class PageService extends CoreService
{
    use SetTranslations;

    protected function getModelClass(): string
    {
        return Page::class;
    }

    /**
     * Create a new Shop model.
     * @param array $data
     * @return array
     */
    public function create(array $data): array
    {
        try {
            $data['img']    = data_get($data, 'images.0');
            $data['bg_img'] = data_get($data, 'images.1');

            $model = DB::transaction(function () use($data) {

                /** @var Page $model */
                $model = $this->model()->updateOrCreate([
                    'type' => data_get($data, 'type')
                ], $data + ['deleted_at' => null]);

                $this->setTranslations($model, $data);

                if (data_get($data, 'images.0')) {
                    $model->uploads(data_get($data, 'images'));
                }

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
     * @param Page $model
     * @param array $data
     * @return array
     */
    public function update(Page $model, array $data): array
    {
        try {
            $data['img']    = data_get($data, 'images.0');
            $data['bg_img'] = data_get($data, 'images.1');

            $model = DB::transaction(function () use($model, $data) {

                $model->update($data);

                if (data_get($data, 'images.0')) {
                    $model->galleries()->delete();
                    $model->uploads(data_get($data, 'images'));
                }

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
        return $this->remove($ids, 'type');
    }

}
