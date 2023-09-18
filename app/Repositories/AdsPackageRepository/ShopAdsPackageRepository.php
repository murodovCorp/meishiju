<?php

namespace App\Repositories\AdsPackageRepository;

use App\Models\Language;
use App\Models\ShopAdsPackage;
use App\Repositories\CoreRepository;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Schema;

class ShopAdsPackageRepository extends CoreRepository
{
    protected function getModelClass(): string
    {
        return ShopAdsPackage::class;
    }

    /**
     * @param array $filter
     * @return LengthAwarePaginator
     */
    public function paginate(array $filter): LengthAwarePaginator
    {
        $locale = data_get(Language::languagesList()->where('default', 1)->first(), 'locale');

        $column = data_get($filter, 'column', 'id');

        if (!Schema::hasColumn('shop_ads_packages', $column)) {
            $column = 'id';
        }

        return ShopAdsPackage::filter($filter)
            ->with([
                'shop:id',
                'shop.translation' => fn($query) => $query
                    ->select(['id', 'shop_id', 'locale', 'title'])
                    ->where('locale', $this->language)
                    ->orWhere('locale', $locale),
                'adsPackage.translation' => fn($query) => $query->where('locale', $this->language)
                    ->orWhere('locale', $locale),
                'transaction',
            ])
            ->orderBy($column, data_get($filter, 'sort', 'asc'))
            ->paginate(data_get($filter, 'perPage', 10));
    }

    /**
     * @param ShopAdsPackage $model
     * @return ShopAdsPackage
     */
    public function show(ShopAdsPackage $model): ShopAdsPackage
    {
        $locale = data_get(Language::languagesList()->where('default', 1)->first(), 'locale');

        return $model->loadMissing([
            'banner.translation' => fn($query) => $query->where('locale', $this->language)
                ->orWhere('locale', $locale),
            'banner.shops:id',
            'banner.shops.translation' => fn($query) => $query
                ->select(['id', 'shop_id', 'locale', 'title'])
                ->where('locale', $this->language)
                ->orWhere('locale', $locale),
            'adsPackage.translation' => fn($query) => $query->where('locale', $this->language)
                ->orWhere('locale', $locale),
            'shop:id,logo_img',
            'shop.translation' => fn($query) => $query
                ->select(['id', 'shop_id', 'locale', 'title'])
                ->where('locale', $this->language)
                ->orWhere('locale', $locale),
            'transaction',
        ]);
    }

    /**
     * @param ShopAdsPackage $model
     * @return ShopAdsPackage
     */
    public function shopShow(ShopAdsPackage $model): ShopAdsPackage
    {
        $locale = data_get(Language::languagesList()->where('default', 1)->first(), 'locale');

        return $model->loadMissing([
            'banner.translation' => fn($query) => $query->where('locale', $this->language)
                ->orWhere('locale', $locale),
            'adsPackage.translation' => fn($query) => $query->where('locale', $this->language)
                ->orWhere('locale', $locale),
            'shop:id,logo_img',
            'shop.translation' => fn($query) => $query
                ->select(['id', 'shop_id', 'locale', 'title'])
                ->where('locale', $this->language)
                ->orWhere('locale', $locale),
            'transaction',
        ]);
    }

}
