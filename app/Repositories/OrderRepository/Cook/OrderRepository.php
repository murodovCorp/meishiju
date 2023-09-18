<?php

namespace App\Repositories\OrderRepository\Cook;

use App\Models\Language;
use App\Models\Order;
use App\Repositories\CoreRepository;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class OrderRepository extends CoreRepository
{
    protected function getModelClass(): string
    {
        return Order::class;
    }

    /**
     * @param array $data
     * @return LengthAwarePaginator
     */
    public function paginate(array $data = []): LengthAwarePaginator
    {
        return $this->model()
            ->filter($data)
            ->withCount('orderDetails')
            ->with([
                'cook:id,firstname,lastname,img,phone,email',
                'currency' => fn($q) => $q->select('id', 'title', 'symbol'),
                'transaction.paymentSystem',
                'shop.translation' => fn($q) => $q->where('locale', $this->language),
            ])
            ->orderBy(data_get($data, 'column', 'id'), data_get($data, 'sort', 'desc'))
            ->paginate(data_get($data, 'perPage', 10));
    }

    /**
     * @param int|null $id
     */
    public function show(?int $id)
    {
        /** @var Order $order */
        $order  = $this->model();
		$locale = data_get(Language::languagesList()->where('default', 1)->first(), 'locale');

        return $order
            ->with([
                'user',
                'review',
                'coupon',
                'waiter:id,firstname,lastname,img,phone,email',
                'cook:id,firstname,lastname,img,phone,email',
                'shop:id,location,tax,delivery_price,background_img,logo_img,uuid,phone',
                'shop.translation' => fn($q) => $q->where('locale', $this->language)->orWhere('locale', $locale),
                'transaction.paymentSystem' => function ($q) {
                    $q->select('id', 'tag', 'active');
                },
                'orderDetails.stock.stockExtras.group.translation' => function ($q) use($locale) {
                    $q
						->select('id', 'extra_group_id', 'locale', 'title')
						->where('locale', $this->language)
						->orWhere('locale', $locale);
                },
                'orderDetails.stock.countable.translation' => function ($q) {
                    $q->select('id', 'product_id', 'locale', 'title')->where('locale', $this->language);
                },
				'orderDetails.stock.countable.unit.translation' => fn($q) => $q
					->where('locale', $this->language)
					->orWhere('locale', $locale),
                'currency' => function ($q) {
                    $q->select('id', 'title', 'symbol');
                }])
            ->find($id);
    }
}
