<?php

namespace App\Repositories\ParcelOrderSettingRepository;

use App\Models\Language;
use App\Models\ParcelOrderSetting;
use App\Repositories\CoreRepository;
use Illuminate\Contracts\Pagination\Paginator;
use Schema;

class ParcelOrderSettingRepository extends CoreRepository
{
    /**
     * @return string
     */
    protected function getModelClass(): string
    {
        return ParcelOrderSetting::class;
    }

    /**
     * @param array $filter
     * @return Paginator
     */
    public function restPaginate(array $filter = []): Paginator
    {
        /** @var ParcelOrderSetting $model */
        $model  = $this->model();
        $column = data_get($filter, 'column', 'id');
        $locale = data_get(Language::languagesList()->where('default', 1)->first(), 'locale');

        if (!Schema::hasColumn('parcel_order_settings', $column)) {
            $column = 'id';
        }

        return $model
            ->filter($filter)
            ->with([
                'parcelOptions.translation' => fn($q) => $q->where('locale', $this->language)->orWhere('locale', $locale)
            ])
            ->orderBy($column, data_get($filter, 'sort', 'desc'))
            ->paginate(data_get($filter, 'perPage', 10));
    }

    /**
     * @param array $filter
     * @return Paginator
     */
    public function paginate(array $filter = []): Paginator
    {
        /** @var ParcelOrderSetting $model */
        $model  = $this->model();
        $column = data_get($filter, 'column', 'id');

        if (!Schema::hasColumn('parcel_order_settings', $column)) {
            $column = 'id';
        }

        return $model
            ->filter($filter)
            ->orderBy($column, data_get($filter, 'sort', 'desc'))
            ->paginate(data_get($filter, 'perPage', 10));
    }

    /**
     * @param ParcelOrderSetting $parcelOrderSetting
     * @return ParcelOrderSetting
     */
    public function show(ParcelOrderSetting $parcelOrderSetting): ParcelOrderSetting
    {
        $locale = data_get(Language::languagesList()->where('default', 1)->first(), 'locale');

        return $parcelOrderSetting
            ->loadMissing([
                'parcelOptions.translation' => fn($q) => $q->where('locale', $this->language)->orWhere('locale', $locale)
            ]);
    }

    /**
     * @param int $id
     * @return ParcelOrderSetting|null
     */
    public function showById(int $id): ?ParcelOrderSetting
    {
        $parcelOrderSetting = ParcelOrderSetting::find($id);

        $locale = data_get(Language::languagesList()->where('default', 1)->first(), 'locale');

        return $parcelOrderSetting
            ?->loadMissing([
                'parcelOptions.translation' => fn($q) => $q->where('locale', $this->language)->orWhere('locale', $locale)
            ]);
    }

}
