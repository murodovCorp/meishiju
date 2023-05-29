<?php

namespace App\Repositories\Booking\ShopBookingWorkingDayRepository;

use App\Models\Booking\BookingShop;
use App\Models\Booking\ShopBookingWorkingDay;
use App\Repositories\CoreRepository;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

class ShopBookingWorkingDayRepository extends CoreRepository
{
    protected function getModelClass(): string
    {
        return ShopBookingWorkingDay::class;
    }

    /**
     * @param array $filter
     * @return LengthAwarePaginator
     */
    public function paginate(array $filter = []): LengthAwarePaginator
    {
        return BookingShop::with([
            'bookingWorkingDays' => fn($q) => $q->select(['id', 'day', 'from', 'to', 'disabled', 'shop_id'])
        ])
            ->whereHas('bookingWorkingDays')
            ->select('id', 'uuid', 'logo_img')
            ->paginate(data_get($filter, 'perPage', 10));
    }

    /**
     * @param int $shopId
     * @return Collection
     */
    public function show(int $shopId): Collection
    {
        return ShopBookingWorkingDay::select(['id', 'day', 'to', 'from', 'disabled'])
            ->where('shop_id', $shopId)
            ->orderBy('day')
            ->get();
    }
}
