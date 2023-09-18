<?php

namespace App\Repositories\Booking\TableRepository;

use App\Models\Booking\ShopSection;
use App\Models\Booking\Table;
use App\Models\Booking\UserBooking;
use App\Repositories\CoreRepository;

class TableReportRepository extends CoreRepository
{
    protected function getModelClass(): string
    {
        return Table::class;
    }

    /**
     * @param array $filter
     * @return array
     */
    public function bookings(array $filter = []): array
    {
        $shopId     = data_get($filter, 'shop_id');
        $sectionId  = data_get($filter, 'section_id');
        $dateFrom   = date('Y-m-d 00:00:01', strtotime(data_get($filter, 'date_from', now())));
        $dateTo     = date('Y-m-d 23:59:59', strtotime(data_get($filter, 'date_to', now())));

        $statistic = [
            'available'     => 0,
            'booked'        => 0,
            'occupied'      => 0,

            'available_ids' => [],
            'booked_ids'    => [],
            'occupied_ids'  => [],

            'all_booked'    => [],
            'all_occupied'  => [],
        ];

        $shopSections = ShopSection::with([
            'tables' => fn($q) => $q->select(['id', 'shop_section_id', 'name']),
            'tables.users' => fn($q) => $q
                ->whereIn('status', [UserBooking::NEW, UserBooking::ACCEPTED])
                ->when($dateFrom,
                    fn($q) => $q->where('start_date', '>=', $dateFrom)->where('start_date', '<=', $dateTo)
                ),
            'tables.users.user:id,firstname,lastname'
        ])
            ->whereHas('tables', fn($q) => $q->select(['id', 'shop_section_id']))
            ->select(['id', 'shop_id'])
            ->when($shopId, fn($q) => $q->where('shop_id', $shopId))
            ->when($sectionId, fn($q) => $q->where('id', $sectionId))
            ->get();

        foreach ($shopSections as $shopSection) {

            /** @var ShopSection $shopSection */
            foreach ($shopSection->tables as $table) {

                $booked     = $table->users()->where('status', UserBooking::NEW)->count();
                $occupied   = $table->users()->where('status', UserBooking::ACCEPTED)->count();

                $statistic['booked']    += $booked;
                $statistic['occupied']  += $occupied;

                if ($occupied > 0) {
                    $statistic['occupied_ids'][] = $table->id;

                }

                if ($booked > 0) {
                    $statistic['booked_ids'][] = $table->id;
                }

                if ($booked === 0) {
                    $statistic['available'] += 1;
                    $statistic['available_ids'][] = $table->id;
                }

                foreach ($table->users as $user) {

                    $group = match ($user->status) {
                        UserBooking::NEW        => 'all_booked',
                        UserBooking::ACCEPTED   => 'all_occupied',
						default					=> null,
                    };

                    if (empty($group)) {
                        continue;
                    }

                    $statistic[$group][] = [
                        'table_id'          => $table->id,
                        'table_name'        => $table->name,
                        'table_start_date'  => $user->start_date,
                        'username'          => "{$user?->user?->firstname} {$user?->user?->lastname}"
                    ];

                }

            }

        }

        return $statistic;
    }
}
