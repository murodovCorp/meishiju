<?php

namespace App\Repositories\Booking\BookingRepository;

use App\Models\Booking\Booking;
use App\Models\Language;
use App\Repositories\CoreRepository;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class BookingRepository extends CoreRepository
{
    protected function getModelClass(): string
    {
        return Booking::class;
    }

    public function paginate($filter = []): LengthAwarePaginator
    {
        /** @var Booking $models */
        $models = $this->model();

        $locale = data_get(Language::languagesList()->where('default', 1)->first(), 'locale');

        return $models
            ->filter($filter)
            ->with([
                'shop:id,uuid,logo_img,open,visibility',
                'shop.translation' => fn($q) => $q->where('locale', $this->language)->orWhere('locale', $locale),
            ])
            ->orderBy(data_get($filter, 'column', 'id'), data_get($filter, 'sort', 'desc'))
            ->paginate(data_get($filter, 'perPage', 10));
    }

    public function showByShopId(int $shopId): Builder|Model|null
    {
        $locale = data_get(Language::languagesList()->where('default', 1)->first(), 'locale');

        return Booking::with([
            'shop:id,uuid,logo_img,open,visibility',
            'shop.translation' => fn($q) => $q->where('locale', $this->language)->orWhere('locale', $locale),
        ])
            ->where('shop_id', $shopId)
            ->first();
    }
}
