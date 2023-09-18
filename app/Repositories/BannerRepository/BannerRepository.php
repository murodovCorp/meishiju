<?php

namespace App\Repositories\BannerRepository;

use App\Models\Banner;
use App\Models\Language;
use App\Models\ShopAdsPackage;
use App\Models\Transaction;
use App\Repositories\CoreRepository;

class BannerRepository extends CoreRepository
{
    protected function getModelClass(): string
    {
        return Banner::class;
    }

    /**
     * @param array $filter
     * @return mixed
     */
    public function bannersPaginate(array $filter): mixed
    {
        $locale = data_get(Language::languagesList()->where('default', 1)->first(), 'locale');

        return $this->model()
            ->whereDoesntHave('shopAdsPackage')
            ->withCount('likes')
            ->with([
                'translation' => fn($q) => $q
                    ->when(data_get($filter, 'search'), function ($query, $search) {
                        $query->where('title', 'LIKE', "%$search%");
                    })
                    ->where(fn($q) => $q->where('locale', $this->language)->orWhere('locale', $locale))
                    ->select('id', 'banner_id', 'locale', 'title')
            ])
            ->when(data_get($filter, 'search'), function ($query, $search) use ($locale) {
                $query->whereHas('translations', function ($q) use ($search, $locale) {
                    $q
                        ->where('title', 'LIKE', "%$search%")
                        ->where(fn($q) => $q->where('locale', $this->language)->orWhere('locale', $locale))
                        ->select('id', 'banner_id', 'locale', 'title');
                });
            })
            ->when(data_get($filter, 'active'), function ($q, $active) {
                $q->where('active', $active);
            })
            ->when(data_get($filter, 'type'), function ($q, $type) {
                $q->where('type', $type);
            })
            ->when(data_get($filter, 'shop_ids'), function ($q, $shopIds) {
                $q->whereHas('shops', fn($q) => $q->whereIn('id', $shopIds));
            })
            ->when(isset($filter['deleted_at']), fn($q) => $q->onlyTrashed())
            ->select([
                'id',
                'url',
                'type',
                'img',
                'active',
                'created_at',
                'updated_at',
                'deleted_at',
                'clickable',
            ])
            ->orderBy(data_get($filter, 'column', 'id'), data_get($filter, 'sort', 'desc'))
            ->paginate(data_get($filter, 'perPage', 10));
    }

    /**
     * @param int $id
     * @return mixed
     */
    public function bannerDetails(int $id): mixed
    {
        return $this->model()
            ->withCount('likes')
            ->withCount('shops')
            ->with([
                'galleries',
                'shops:id,uuid,user_id,status,logo_img,open,delivery_time',
                'shops' => fn($q) => $q->withAvg('reviews', 'rating')->withCount('reviews')->paginate(request('perPage', 2)),
                'shops.translation' => fn($q) => $q->where('locale', $this->language),
                'translation' => fn($q) => $q->where('locale', $this->language),
                'translations',
            ])
            ->find($id);
    }

    /**
     * @param array $filter
     * @return array
     */
    public function adsPaginate(array $filter): array
    {
        $locale = data_get(Language::languagesList()->where('default', 1)->first(), 'locale');

        $adsPackages = ShopAdsPackage::with([
            'banner.translation' => fn($q) => $q
                ->when(data_get($filter, 'search'), function ($query, $search) {
                    $query->where('title', 'LIKE', "%$search%");
                })
                ->where(fn($q) => $q->where('locale', $this->language)->orWhere('locale', $locale))
                ->select('id', 'banner_id', 'locale', 'title')
        ])
            ->when(data_get($filter, 'search'), function ($query, $search) use ($locale) {
                $query->whereHas('banner.translation', function ($q) use ($search, $locale) {
                    $q
                        ->where('title', 'LIKE', "%$search%")
                        ->where(fn($q) => $q->where('locale', $this->language)->orWhere('locale', $locale))
                        ->select('id', 'banner_id', 'locale', 'title');
                });
            })
            ->where('active', true)
            ->where('status', ShopAdsPackage::APPROVED)
            ->whereNotNull('expired_at')
            ->whereHas('adsPackage')
            ->whereHas('banner',      fn($q) => $q->where('active', true))
            ->whereHas('transaction', fn($q) => $q->where('status', Transaction::STATUS_PAID))
            ->orderBy('position_page')
            ->paginate(data_get($filter, 'perPage', 10));

        $result = [];

        foreach ($adsPackages as $adsPackage) {

            /** @var ShopAdsPackage $adsPackage */

            if (data_get($result, $adsPackage->banner_id)) {
                continue;
            }

            if ($adsPackage->expired_at < date('Y-m-d H:i:s')) {
                $adsPackage->delete();
                continue;
            }

            if (!$adsPackage->banner?->translation?->title) {
                continue;
            }

            if (!$adsPackage->shop?->translation?->title) {
                continue;
            }

            $result[$adsPackage->banner_id] = $adsPackage->banner;
        }

        return [
            'data' => array_values($result),
            'meta' => [
                'current_page'  => $adsPackages->currentPage(),
                'last_page'     => $adsPackages->lastPage(),
                'path'          => $adsPackages->path(),
                'per_page'      => $adsPackages->perPage(),
                'to'            => $adsPackages->perPage(),
                'total'         => $adsPackages->total(),
            ]
        ];
    }

    /**
     * @param int $id
     * @return array
     */
    public function adsShow(int $id): array
    {
        $locale = data_get(Language::languagesList()->where('default', 1)->first(), 'locale');

        $adsPackages = ShopAdsPackage::with([
            'banner.translation' => fn($q) => $q->where('locale', $this->language)->orWhere('locale', $locale),
        ])
            ->where('banner_id', $id)
            ->where('active', true)
            ->where('status', ShopAdsPackage::APPROVED)
            ->whereNotNull('expired_at')
            ->whereHas('adsPackage')
            ->whereHas('banner',      fn($q) => $q->where('active', true))
            ->whereHas('transaction', fn($q) => $q->where('status', Transaction::STATUS_PAID))
            ->orderBy('position_page')
            ->get();

        $result = [];
        $banner = null;

        foreach ($adsPackages as $adsPackage) {

            /** @var ShopAdsPackage $adsPackage */
            if ($adsPackage->expired_at < date('Y-m-d H:i:s')) {
                $adsPackage->delete();
                continue;
            }

            if (!$adsPackage->banner?->translation?->title) {
                continue;
            }

            if (!$adsPackage->shop?->translation?->title) {
                continue;
            }

            $result[] = $adsPackage->shop;
            $banner   = $adsPackage->banner;
        }

        return [
            'banner' => $banner,
            'shops'  => $result,
        ];
    }

}
