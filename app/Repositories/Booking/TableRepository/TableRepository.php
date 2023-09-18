<?php

namespace App\Repositories\Booking\TableRepository;

use App\Models\Booking\Table;
use App\Models\Booking\UserBooking;
use App\Models\Language;
use App\Models\Settings;
use App\Repositories\CoreRepository;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class TableRepository extends CoreRepository
{
    protected function getModelClass(): string
    {
        return Table::class;
    }

    /**
     * @param array $filter
     * @return LengthAwarePaginator
     */
    public function paginate(array $filter = []): LengthAwarePaginator
    {
        /** @var Table $models */
        $models = $this->model();
        $locale = data_get(Language::languagesList()->where('default', 1)->first(), 'locale');

        return $models
            ->filter($filter)
            ->with([
                'shopSection' => fn($query) => $query
                    ->when(data_get($filter, 'shop_id'), function ($query, $shopId) {
                        $query->where('shop_id', $shopId);
                    }),
                'shopSection.translation' => fn($q) => $q->where('locale', $this->language)->orWhere('locale', $locale),
            ])
            ->orderBy(data_get($filter, 'column', 'id'), data_get($filter, 'sort', 'desc'))
            ->paginate(data_get($filter, 'perPage', 10));
    }

    /**
     * @param Table $model
     * @return Table|null
     */
    public function show(Table $model): Table|null
    {
        $locale = data_get(Language::languagesList()->where('default', 1)->first(), 'locale');

        return $model->loadMissing([
            'shopSection.translation'       => fn($q) => $q->where('locale', $this->language)->orWhere('locale', $locale),
            'shopSection.shop.translation'  => fn($q) => $q->where('locale', $this->language)->orWhere('locale', $locale)
        ]);
    }

    /**
     * @param array $filter
     * @return array
     */
    public function disableDates(array $filter = []): array
    {
        $minTime  = Settings::adminSettings()->where('key', 'min_reservation_time')->first()?->value;

		$startDate = data_get($filter, 'date_from', now());

        $dateFrom = date('Y-m-d H:i:01', strtotime($startDate));
		$dateTo   = data_get($filter, 'date_to', $minTime ? "-$minTime hour" : $startDate);

        $dateTo   = date('Y-m-d H:i:59', strtotime($dateTo));

        $model = Table::with([
			'users' => fn($q) => $q->whereIn('status', [UserBooking::NEW, UserBooking::ACCEPTED])
		])
			->whereHas('users', fn($q) => $q->whereIn('status', [UserBooking::NEW, UserBooking::ACCEPTED]))
            ->whereHas('shopSection', function ($q) use ($filter) {
                $q->when(data_get($filter, 'shop_id'), function ($b) use ($filter) {
                    $b->where('shop_id', data_get($filter, 'shop_id'));
                });
            })
            ->where('id', data_get($filter, 'id'))
            ->first();

        $bookedDays = [];

        if (empty($model)) {
            return $bookedDays;
        }

        /** @var Table $model */
        foreach ($model->users as $user) {

            if (
                !empty(data_get($filter, 'date_to'))
                && $dateFrom >= date('Y-m-d H:i:01', strtotime($user->start_date))
                && $dateTo <= date('Y-m-d H:i:59', strtotime($user->start_date))
            ) {
                $bookedDays[] = [
                    'start_date' => $user->start_date,
                    'end_date'   => $user->end_date,
                ];
                continue;
            }

            if (date('Y-m-d H:i', strtotime($user->start_date)) <= $dateFrom) {
                continue;
            }

            $bookedDays[] = [
                'start_date' => $user->start_date,
                'end_date'   => $user->end_date,
            ];

        }

        return $bookedDays;
    }
}
