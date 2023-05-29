<?php

namespace App\Repositories\Booking\ShopBookingClosedDateRepository;

use App\Models\Booking\BookingShop;
use App\Models\Booking\ShopBookingClosedDate;
use App\Repositories\CoreRepository;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

class ShopBookingClosedDateRepository extends CoreRepository
{
    protected function getModelClass(): string
    {
        return ShopBookingClosedDate::class;
    }

    /**
     * @param array $filter
     * @return LengthAwarePaginator
     */
    public function paginate(array $filter = []): LengthAwarePaginator
    {
        return BookingShop::with([
            'bookingClosedDates' => fn($q) => $q->select(['id', 'date', 'shop_id'])
        ])
            ->whereHas('bookingClosedDates')
            ->select('id', 'uuid', 'logo_img')
            ->paginate(data_get($filter, 'perPage', 10));
    }

    /**
     * @param int $shopId
     * @return Collection
     */
    public function show(int $shopId): Collection
    {
        return ShopBookingClosedDate::whereShopId($shopId)->select(['id', 'shop_id', 'date'])->get();
    }
}
