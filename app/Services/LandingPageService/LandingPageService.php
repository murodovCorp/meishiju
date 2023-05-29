<?php

namespace App\Services\LandingPageService;

use App\Helpers\ResponseError;
use App\Models\LandingPage;
use App\Services\CoreService;
use DB;
use Throwable;

class LandingPageService extends CoreService
{
    /**
     * @return string
     */
    protected function getModelClass(): string
    {
        return LandingPage::class;
    }

    /**
     * @param array $data
     * @return array
     */
    public function create(array $data): array
    {
        try {
            $model = DB::transaction(function () use ($data) {
                /** @var LandingPage $model */
                $model = $this->model()->withTrashed()->updateOrCreate(['type' => $data['type']], $data);

                if (data_get($data, 'images.0')) {
                    $model->uploads(data_get($data, 'images'));
                }

                return $model;
            });

            return ['status' => true, 'code' => ResponseError::NO_ERROR, 'data' => $model];

        } catch (Throwable $e) {
            $this->error($e);
        }

        return [
            'status'    => false,
            'code'      => ResponseError::ERROR_501,
            'message'   => __('errors.' . ResponseError::ERROR_501, locale: $this->language)
        ];
    }

    public function update(string $type, array $data): array
    {
        try {
            $landingPage = DB::transaction(function () use ($type, $data) {

                $landingPage = LandingPage::where('type', $type)->first();
                $landingPage->update($data);

                if (data_get($data, 'images.0')) {
                    $landingPage->galleries()->delete();
                    $landingPage->uploads(data_get($data, 'images'));
                }

                return $landingPage;
            });

            return ['status' => true, 'code' => ResponseError::NO_ERROR, 'data' => $landingPage];

        } catch (Throwable $e) {
            $this->error($e);
            return [
                'status'  => false,
                'code'    => ResponseError::ERROR_502,
                'message' => __('errors.' . ResponseError::ERROR_502, locale: $this->language)
            ];
        }
    }

    public function destroy(?array $ids = [], ?int $shopId = null) {

        /** @var LandingPage $models */
        $models = $this->model();

        foreach ($models->whereIn('id', is_array($ids) ? $ids : [])->get() as $model) {

            /** @var LandingPage $model */
            $model->galleries()->delete();
            $model->delete();

        }

    }
}
