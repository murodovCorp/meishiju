<?php

namespace App\Services\AdsPackageService;

use App\Helpers\ResponseError;
use App\Models\AdsPackage;
use App\Models\ShopAdsPackage;
use App\Services\CoreService;
use DB;
use Exception;
use Throwable;

final class ShopAdsPackageService extends CoreService
{
    protected function getModelClass(): string
    {
        return ShopAdsPackage::class;
    }

    /**
     * @param array $data
     * @return array
     */
    public function create(array $data): array
    {
        try {
            /** @var ShopAdsPackage $model */
            $ads = AdsPackage::select(['id', 'banner_id'])->find(data_get($data, 'ads_package_id'));

            $data['banner_id'] = $ads->banner_id;

            $exists = ShopAdsPackage::where([
                ['expired_at', '>=', now()],
                ['shop_id', data_get($data, 'shop_id')],
                ['status', ShopAdsPackage::APPROVED],
            ])
                ->exists();

            if ($exists) {
                throw new Exception(__('errors.'. ResponseError::ERROR_116, locale: $this->language));
            }

            $model = $this->model()->create($data);

            return ['status' => true, 'code' => ResponseError::NO_ERROR, 'data' => $model];
        } catch (Exception $e) {
            $this->error($e);
            return ['status' => false, 'code' => ResponseError::ERROR_501, 'message' => $e->getMessage()];
        }
    }

    /**
     * @param ShopAdsPackage $shopAdsPackage
     * @param array $data
     * @return array
     */
    public function update(ShopAdsPackage $shopAdsPackage, array $data): array
    {
        try {
            DB::transaction(function () use ($shopAdsPackage, $data) {

                $exists = ShopAdsPackage::where([
                    ['expired_at', '>=', now()],
                    ['shop_id', $shopAdsPackage->shop_id],
                    ['id', '!=', $shopAdsPackage->id],
                    ['status', ShopAdsPackage::APPROVED],
                ])
                    ->exists();

                if ($exists) {
                    throw new Exception(__('errors.'. ResponseError::ERROR_116, locale: $this->language));
                }

                $shopAdsPackage->update($data);
                $this->afterSave($shopAdsPackage);
            });

            return ['status' => true, 'code' => ResponseError::NO_ERROR, 'data' => $shopAdsPackage];
        } catch (Throwable $e) {
            $this->error($e);
            return ['status' => false, 'code' => ResponseError::ERROR_501, 'message' => $e->getMessage()];
        }
    }

    /**
     * @param array|null $ids
     * @param int|null $shopId
     * @return array|int[]
     */
    public function delete(?array $ids = [], ?int $shopId = null): array
    {
        $shopAdsPackages = ShopAdsPackage::whereIn('id', is_array($ids) ? $ids : [])
            ->when($shopId, fn($q) => $q->where('shop_id', $shopId))
            ->get();

        foreach ($shopAdsPackages as $shopAdsPackage) {

            /** @var ShopAdsPackage $shopAdsPackage */
            $banner = $shopAdsPackage->load(['banner'])?->banner;
            $banner?->shops()->detach([$shopAdsPackage->shop_id]);
            $shopAdsPackage->delete();

        }

        return [
            'status' => true,
            'code' => ResponseError::NO_ERROR,
        ];
    }

    /**
     * @param ShopAdsPackage $shopAdsPackage
     * @return void
     * @throws Exception
     */
    public function afterSave(ShopAdsPackage $shopAdsPackage): void
    {
        $banner = $shopAdsPackage->load(['banner'])?->banner;

        if ($shopAdsPackage->status !== ShopAdsPackage::APPROVED) {
            $banner?->shops()->detach([$shopAdsPackage->shop_id]);
            return;
        }

        $adsPackage = $shopAdsPackage->adsPackage;

        $shopAdsPackage->update([
            'expired_at' => date('Y-m-d H:i:s', strtotime("+$adsPackage->time $adsPackage->time_type")),
        ]);

        $banner?->shops()->attach($shopAdsPackage->shop_id);
    }

}
