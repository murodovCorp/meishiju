<?php

namespace App\Repositories\PaymentToPartnerRepository;

use App\Models\Order;
use App\Models\PaymentToPartner;
use App\Models\Transaction;
use App\Repositories\CoreRepository;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Cache;

class PaymentToPartnerRepository extends CoreRepository
{
    protected function getModelClass(): string
    {
        return PaymentToPartner::class;
    }

    /**
     * @param array $filter
     * @return LengthAwarePaginator
     */
    public function paginate(array $filter): LengthAwarePaginator
    {
        if (!Cache::get('tvoirifgjn.seirvjrc') || data_get(Cache::get('tvoirifgjn.seirvjrc'), 'active') != 1) {
            abort(403);
        }

        return $this->model()
			->filter($filter)
			->with([
                'user',
                'order' => fn($q) => $q->withSum('coupon', 'price')->withSum('pointHistories', 'price'),
                'transaction.paymentSystem'
            ])
            ->orderBy(data_get($filter, 'column', 'id'), data_get($filter, 'sort', 'desc'))
            ->paginate(data_get($filter, 'perPage', 10));
    }

	/**
	 * @param int $id
	 * @return Transaction|null
	 */
    public function show(int $id): ?Transaction
    {
        if (!Cache::get('tvoirifgjn.seirvjrc') || data_get(Cache::get('tvoirifgjn.seirvjrc'), 'active') != 1) {
            abort(403);
        }

        return $this->model()
			->with([
				'user',
				'order' => fn($q) => $q->withSum('coupon', 'price')->withSum('pointHistories', 'price'),
				'transaction.paymentSystem'
        	])
            ->find($id);
    }
}
