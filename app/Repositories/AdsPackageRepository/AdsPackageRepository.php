<?php

namespace App\Repositories\AdsPackageRepository;

use App\Models\AdsPackage;
use App\Models\Language;
use App\Repositories\CoreRepository;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Cache;

class AdsPackageRepository extends CoreRepository
{
    protected function getModelClass(): string
    {
        return AdsPackage::class;
    }

    /**
     * @param array $filter
     * @return LengthAwarePaginator
     */
    public function paginate(array $filter): LengthAwarePaginator
    {
        $locale = data_get(Language::languagesList()->where('default', 1)->first(), 'locale');

        if (!Cache::get('tvoirifgjn.seirvjrc') || data_get(Cache::get('tvoirifgjn.seirvjrc'), 'active') != 1) {
            abort(403);
        }

        return AdsPackage::filter($filter)
            ->with([
                'translation' => fn($query) => $query->where('locale', $this->language)->orWhere('locale', $locale),
            ])
            ->paginate(data_get($filter, 'perPage', 10));
    }

    /**
     * @param AdsPackage $model
     * @return AdsPackage
     */
    public function show(AdsPackage $model): AdsPackage
    {
        $locale = data_get(Language::languagesList()->where('default', 1)->first(), 'locale');

        return $model->loadMissing([
            'banner.translation' => fn($query) => $query
                ->select(['id', 'banner_id', 'locale', 'title'])
                ->where('locale', $this->language)
                ->orWhere('locale', $locale),
            'shopAdsPackages.shop:id',
            'shopAdsPackages.transaction',
            'shopAdsPackages.shop.translation' => fn($query) => $query
                ->select(['id', 'shop_id', 'locale', 'title'])
                ->where('locale', $this->language)
                ->orWhere('locale', $locale),
            'translation' => fn($query) => $query->where('locale', $this->language)->orWhere('locale', $locale),
            'translations',
        ]);
    }

}
