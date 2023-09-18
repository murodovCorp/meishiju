<?php

namespace App\Services\OrderService;

use App\Models\Cart;
use App\Models\Order;
use App\Models\OrderDetail;
use App\Models\Product;
use App\Models\Stock;
use App\Models\UserCart;
use App\Services\CartService\CartService;
use App\Services\CoreService;

class OrderDetailService extends CoreService
{
    /**
     * @return string
     */
    protected function getModelClass(): string
    {
        return OrderDetail::class;
    }

    public function create(Order $order, array $collection): Order
    {
        foreach ($order->orderDetails as $orderDetail) {

            $orderDetail?->stock?->increment('quantity', $orderDetail?->quantity);

            $orderDetail?->forceDelete();

        }

        return $this->update($order, $collection);
    }

    public function update($order, $collection) {

        foreach ($collection as $item) {

            /** @var Stock $stock */
            $stock = Stock::with([
                'countable:id,status,shop_id,active,min_qty,max_qty,tax,img,interval',
                'countable.discounts' => fn($q) => $q
                    ->where('start', '<=', today())
                    ->where('end', '>=', today())
                    ->where('active', 1)
            ])
                ->find(data_get($item, 'stock_id'));

            if (!$stock?->countable?->active || $stock?->countable?->status != Product::PUBLISHED) {
                continue;
            }

            $actualQuantity = $this->actualQuantity($stock, data_get($item, 'quantity', 0));

            if (empty($actualQuantity) || $actualQuantity <= 0) {
                continue;
            }

            data_set($item, 'quantity', $actualQuantity);

            $order->orderDetails()->updateOrCreate(
                [
                    'stock_id' => $stock->id,
                ],
                $this->setItemParams($item, $stock)
            );

            $stock->decrement('quantity', $actualQuantity);

			foreach (data_get($item, 'addons', []) as $addon) {
				/** @var Stock $addonStock */
				$addonStock = Stock::with([
					'countable:id,status,shop_id,active,min_qty,max_qty,tax,img,interval',
					'countable.discounts' => fn($q) => $q
						->where('start', '<=', today())
						->where('end', '>=', today())
						->where('active', 1)
				])
					->find(data_get($addon, 'stock_id'));

				if (!$addonStock) {
					continue;
				}

				$actualQuantity = $this->actualQuantity($addonStock, data_get($addon, 'quantity', 0));

				if (empty($actualQuantity) || $actualQuantity <= 0) {
					continue;
				}

				$addon['quantity'] = $actualQuantity;

				$parent = OrderDetail::where([
					['stock_id', data_get($addon, 'parent_id')],
					['order_id', $order->id]
				])->first();

				$addon['parent_id'] = $parent?->id;

				$order->orderDetails()->updateOrCreate(
					[
						'stock_id'  => $addonStock->id,
						'parent_id' => $parent?->id,
					],
					$this->setItemParams($addon, $addonStock)
				);

				$addonStock->decrement('quantity', $actualQuantity);
			}

        }

        return $order;
    }

    public function createOrderUser(Order $order, int $cartId, array $notes = []): Order
    {
        /** @var Cart $cart */
		$cart = clone Cart::with([
			'userCarts.cartDetails:id,user_cart_id,stock_id,price,discount,quantity',
			'userCarts.cartDetails.stock.bonus' => fn($q) => $q->where('expired_at', '>', now()),
			'shop',
			'shop.bonus' => fn($q) => $q->where('expired_at', '>', now()),
		])
			->select('id', 'total_price', 'shop_id')
			->find($cartId);

        (new CartService)->calculateTotalPrice($cart);

        $cart = clone Cart::with([
            'shop',
            'userCarts.cartDetails' => fn($q) => $q->whereNull('parent_id'),
            'userCarts.cartDetails.stock.countable',
            'userCarts.cartDetails.children.stock.countable',
        ])->find($cart->id);

        if (empty($cart?->userCarts)) {
            return $order;
        }

        foreach ($cart->userCarts as $userCart) {

			$cartDetails = $userCart->cartDetails;

            if (empty($cartDetails)) {
                $userCart->delete();
                continue;
            }

			foreach ($cartDetails as $cartDetail) {

				/** @var UserCart $userCart */
				$stock = $cartDetail->stock;

                $cartDetail->setAttribute('note', data_get($notes, $stock->id, ''));

				/** @var OrderDetail $parent */
				$parent = $order->orderDetails()->create($this->setItemParams($cartDetail, $stock));

                $stock->decrement('quantity', $cartDetail->quantity);

				foreach ($cartDetail->children as $addon) {

					$stock = $addon->stock;

					$addon->setAttribute('parent_id', $parent?->id);

					$addon->setAttribute('note', data_get($notes, $stock->id, ''));
					$order->orderDetails()->create($this->setItemParams($addon, $stock));

					$stock->decrement('quantity', $addon->quantity);
				}

            }

        }

        $cart->delete();

        return $order;

    }

    private function setItemParams($item, ?Stock $stock): array
    {

        $quantity = data_get($item, 'quantity', 0);

        if (data_get($item, 'bonus')) {

            data_set($item, 'origin_price', 0);
            data_set($item, 'total_price', 0);
            data_set($item, 'tax', 0);
            data_set($item, 'discount', 0);

        } else {

            $originPrice = $stock?->price * $quantity;

            $discount    = $stock?->actual_discount * $quantity;

            $tax         = $stock?->tax_price * $quantity;

            $totalPrice  = $originPrice - $discount + $tax;

            data_set($item, 'origin_price', $originPrice);
            data_set($item, 'total_price', max($totalPrice,0));
            data_set($item, 'tax', $tax);
            data_set($item, 'discount', $discount);
        }

        return [
            'note'          => data_get($item, 'note', 0),
            'origin_price'  => data_get($item, 'origin_price', 0),
            'tax'           => data_get($item, 'tax', 0),
            'discount'      => data_get($item, 'discount', 0),
            'total_price'   => data_get($item, 'total_price', 0),
            'stock_id'      => data_get($item, 'stock_id'),
            'parent_id'     => data_get($item, 'parent_id'),
            'quantity'      => $quantity,
            'bonus'         => data_get($item, 'bonus', false)
        ];
    }

    /**
     * @param Stock|null $stock
     * @param $quantity
     * @return mixed
     */
    private function actualQuantity(?Stock $stock, $quantity): mixed
    {

        $countable = $stock?->countable;

        if ($quantity < ($countable?->min_qty ?? 0)) {

            $quantity = $countable?->min_qty;

        } else if($quantity > ($countable?->max_qty ?? 0)) {

            $quantity = $countable?->max_qty;

        }

        return $quantity > $stock->quantity ? max($stock->quantity, 0) : $quantity;
    }

}
