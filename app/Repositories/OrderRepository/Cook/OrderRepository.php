<?php

namespace App\Repositories\OrderRepository\Cook;

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
     * @return Order|null
     */
    public function show(?int $id): ?Order
    {
        /** @var Order $order */
        $order = $this->model();

        return $order
            ->withTrashed()
            ->with([
                'user',
                'review',
                'coupon',
                'waiter:id,firstname,lastname,img,phone,email',
                'cook:id,firstname,lastname,img,phone,email',
                'shop:id,location,tax,price,price_per_km,background_img,logo_img,uuid,phone',
                'shop.translation' => fn($q) => $q->where('locale', $this->language),
                'transaction.paymentSystem' => function ($q) {
                    $q->select('id', 'tag', 'active');
                },
                'orderDetails.stock.stockExtras.group.translation' => function ($q) {
                    $q->select('id', 'extra_group_id', 'locale', 'title')->where('locale', $this->language);
                },
                'orderDetails.stock.countable.translation' => function ($q) {
                    $q->select('id', 'product_id', 'locale', 'title')->where('locale', $this->language);
                },
                'currency' => function ($q) {
                    $q->select('id', 'title', 'symbol');
                }])
            ->find($id);
    }
}
