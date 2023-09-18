<?php

namespace App\Repositories\OrderRepository;

use App\Exports\OrdersReportExport;
use App\Exports\OrdersRevenueReportExport;
use App\Helpers\ResponseError;
use App\Http\Resources\OrderResource;
use App\Models\Bonus;
use App\Models\CategoryTranslation;
use App\Models\Coupon;
use App\Models\Currency;
use App\Models\Language;
use App\Models\Order;
use App\Models\OrderDetail;
use App\Models\PaymentToPartner;
use App\Models\Product;
use App\Models\ProductTranslation;
use App\Models\Settings;
use App\Models\Shop;
use App\Models\Stock;
use App\Models\Transaction;
use App\Models\User;
use App\Repositories\CoreRepository;
use App\Repositories\Interfaces\OrderRepoInterface;
use App\Repositories\ReportRepository\ChartRepository;
use App\Services\Yandex\YandexService;
use App\Traits\SetCurrency;
use Illuminate\Contracts\Pagination\Paginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Facades\Excel;
use Throwable;

class OrderRepository extends CoreRepository implements OrderRepoInterface
{
	use SetCurrency;

	/**
	 * @return string
	 */
	protected function getModelClass(): string
	{
		return Order::class;
	}

	/**
	 * @param array $filter
	 * @return mixed
	 */
	public function ordersList(array $filter = []): mixed
	{
		return $this->model()->with([
			'transaction.paymentSystem',
			'orderDetails.stock',
			'deliveryMan',
			'table',
		])
			->updatedDate($this->updatedDate)
			->filter($filter)
			->get();
	}

    /**
     * This is only for users route
     * @param array $filter
     * @param bool $isUser
     * @return Paginator
     */
	public function ordersPaginate(array $filter = [], bool $isUser = false): Paginator
	{
		/** @var Order $order */
		$order  = $this->model();
		$locale = data_get(Language::languagesList()->where('default', 1)->first(), 'locale');

		return $order
			->withCount('orderDetails')
			->with([
                'shop:id,location,tax,delivery_price,background_img,logo_img',
                'shop.translation'      => fn($q) => $q->where('locale', $this->language)->orWhere('locale', $locale),
                'currency'              => fn($q) => $q->select('id', 'title', 'symbol'),
                'user:id,firstname,lastname,uuid,img,phone',
                'transaction.paymentSystem',
                'table',
            ])
			->filter($filter)
			->updatedDate($this->updatedDate)
            ->when(
                !$isUser,
                fn($q) => $q->whereHas('transactions', fn($q) => $q->where('status', Transaction::STATUS_PAID))
            )
			->orderBy(data_get($filter, 'column', 'id'), data_get($filter, 'sort', 'desc'))
			->paginate(data_get($filter, 'perPage', 10));
	}

	/**
	 * @param array $filter
	 * @return array
	 */
	public function orderStocksCalculate(array $filter): array
	{
		$locale     = data_get(Language::languagesList()->where('default', 1)->first(), 'locale');
		$products   = collect(data_get($filter, 'products', []));

		$result = [];

		foreach ($products as $key => $data) {

			/** @var Stock|null $stock */
			$stock = Stock::with([
				'countable' => fn($q) => $q->select([
					'id',
					'uuid',
					'active',
					'status',
					'shop_id',
					'unit_id',
					'keywords',
					'img',
					'qr_code',
					'tax',
					'min_qty',
					'max_qty',
					'interval',
				]),
				'countable.unit.translation' => fn($q) => $q->where('locale', $this->language)->orWhere('locale', $locale),
				'countable.translation' => fn($q) => $q->select('id', 'product_id', 'locale', 'title')
					->where('locale', $this->language)->orWhere('locale', $locale),
				'stockExtras.group.translation' => fn($q) => $q->where('locale', $this->language)
					->orWhere('locale', $locale),
			])
				->whereHas('countable', fn($q) => $q->where('active', 1)->where('status', Product::PUBLISHED))
				->find(data_get($data, 'stock_id'));

			if (
				!$stock
				|| $stock->countable?->shop_id !== (int)data_get($filter, 'shop_id')
				|| !$stock->countable?->active
				|| $stock->countable?->status != Product::PUBLISHED
			) {
				continue;
			}

			$quantity     = (int)$this->actualQuantity($stock, data_get($data, 'quantity')) ?? 0;
			$price        = ($stock->price * $this->currency() * $quantity);
			$discount     = ($stock->actual_discount * $this->currency() * $quantity);
			$tax          = ($stock->tax_price * $this->currency() * $quantity);
			$totalPrice   = max(($price - $discount + $tax), 0);

			$result[$key] = [
				'id'                	=> $stock->id,
				'price'             	=> $price + $tax,
				'quantity'          	=> $quantity,
				'countable_quantity'	=> $quantity,
				'tax'               	=> $tax,
				'discount'          	=> $discount,
				'total_price'       	=> $totalPrice,
				'stock'             	=> $stock->toArray(),
				'addons'            	=> []
			];

			/** @var Bonus $bonus */
			$bonus = Bonus::with([
				'stock',
				'stock.countable' => fn($q) => $q->select([
					'id',
					'uuid',
					'active',
					'status',
					'shop_id',
					'unit_id',
					'keywords',
					'img',
					'qr_code',
					'tax',
					'min_qty',
					'max_qty',
					'interval',
				])->where('shop_id', data_get($filter, 'shop_id')),
				'stock.countable.unit.translation' => fn($q) => $q
					->where('locale', $this->language)
					->orWhere('locale', $locale),
				'stock.countable.translation' => fn($q) => $q->select('id', 'product_id', 'locale', 'title')
					->where('locale', $this->language)->orWhere('locale', $locale),
				'stock.stockExtras.group.translation' => fn($q) => $q
					->where('locale', $this->language)
					->orWhere('locale', $locale),
			])->where([
				['bonusable_type', Stock::class],
				['bonusable_id', $stock->id],
				['expired_at', '>=', now()],
				['status', 1],
			])
				->whereHas(
					'stock',
					fn($q) => $q
						->where('quantity', '>', 0)
						->whereHas('countable', fn($q) => $q->where('active', 1)->where('status', Product::PUBLISHED))
				)
				->first();

			if ($bonus?->stock?->countable?->shop_id === (int)data_get($filter, 'shop_id')) {

				$bonusQuantity = (int)($bonus->type === Bonus::TYPE_COUNT ?
					$bonus->bonus_quantity * floor($quantity / $bonus->value) :
					$bonus->bonus_quantity);

				$bonusQuantity = $this->actualQuantity($bonus->stock, $bonusQuantity);

				if (empty($bonusQuantity) || ($bonus->type === Bonus::TYPE_COUNT && $quantity < $bonus->value)) {
					continue;
				}

				$result[] = [
					'id'          => $bonus->stock->id,
					'price'       => 0,
					'quantity'    => $bonusQuantity,
					'tax'         => 0,
					'discount'    => 0,
					'total_price' => 0,
					'bonus'       => true,
					'stock'       => $bonus->stock->loadMissing([
						'countable' => fn($q) => $q->select([
							'id',
							'uuid',
							'shop_id',
							'unit_id',
							'keywords',
							'img',
							'qr_code',
							'tax',
							'min_qty',
							'max_qty',
							'interval',
						]),
						'countable.unit.translation' => fn($q) => $q
							->where('locale', $this->language)
							->orWhere('locale', $locale),
						'countable.translation' => fn($q) => $q
							->select('id', 'product_id', 'locale', 'title')
							->where('locale', $this->language)
							->orWhere('locale', $locale),
						'stockExtras',
						'stockExtras.group.translation' => fn($q) => $q
							->where('locale', $this->language)
							->orWhere('locale', $locale),
					]),
				];
			}

			/** @var Stock[] $stockAddons */
			$stockAddons = Stock::with([
				'countable' => fn($q) => $q->select([
					'id',
					'uuid',
					'active',
					'status',
					'shop_id',
					'unit_id',
					'keywords',
					'img',
					'qr_code',
					'tax',
					'min_qty',
					'max_qty',
					'interval',
				]),
				'countable.unit.translation' => fn($q) => $q
					->where('locale', $this->language)
					->orWhere('locale', $locale),
				'countable.translation' => fn($q) => $q
					->select('id', 'product_id', 'locale', 'title')
					->where('locale', $this->language)
					->orWhere('locale', $locale),
				'stockExtras.group.translation' => fn($q) => $q
					->where('locale', $this->language)
					->orWhere('locale', $locale),
			])
				->whereHas('countable', fn($q) => $q->where('active', 1)->where('status', Product::PUBLISHED))
				->find(collect(data_get($data, 'addons'))->pluck('stock_id')->toArray());

			foreach ($stockAddons as $stockAddon) {

				$addedAddon = collect(data_get($data, 'addons'))->where('stock_id', $stockAddon->id)->first();

				if (empty($addedAddon)) {
					continue;
				}

				$addonQuantity   = (int)$this->actualQuantity($stockAddon, data_get($addedAddon, 'quantity')) ?? 0;
				$addonDiscount   = ($stockAddon->actual_discount  * $this->currency() * $addonQuantity);
				$addonPrice      = ($stockAddon->price  * $this->currency() * $addonQuantity);
				$addonTax        = ($stockAddon->tax_price  * $this->currency() * $addonQuantity);
				$addonTotalPrice = ($addonPrice - $addonDiscount + $addonTax);

				$result[$key]['price']          += $addonPrice + $addonTax;
				$result[$key]['quantity']       += $addonQuantity;
				$result[$key]['tax']            += $addonTax;
				$result[$key]['discount']       += $addonDiscount;
				$result[$key]['total_price']    += $addonTotalPrice;
				$result[$key]['addons'][]        = $stockAddon
					->setAttribute('price', $addonPrice)
					->setAttribute('quantity', $addonQuantity)
					->setAttribute('total_price', $addonTotalPrice)
					->setAttribute('discount', $addonDiscount)
					->setAttribute('tax', $addonTax)
					->toArray();

			}

		}

		$result = collect(array_values($result));

		$totalPrice = $result->sum('total_price');

		$shopTax     = 0;
		$km          = 0;
		$deliveryFee = 0;

        $shop = Shop::with([
            'translation' => fn($q) => $q->where('locale', $this->language)
        ])->find((int)data_get($filter, 'shop_id'));

        if (!empty($shop)) {
            /** @var Shop $shop */
            $shopTax = max((($totalPrice / $this->currency()) / 100 * $shop->tax) * $this->currency(), 0);
        }

        if (data_get($filter, 'type') === Order::DELIVERY) {
            $yandexService   = new YandexService;
            $checkPrice      = $yandexService->checkPrice($result->toArray(), $shop->location, data_get($filter, 'address'));

            if (data_get($checkPrice, 'code') !== 200) {
                return [
                    'status'  => false,
                    'code'    => ResponseError::ERROR_409,
                    'http'    => 409,
                    'message' => data_get($checkPrice, 'data.message')
                ];
            }

            $rub = Currency::currenciesList()
                ->where('title', data_get($checkPrice, 'data.currency_rules.code'))
                ->first();

            $deliveryFee = data_get($checkPrice, 'data.price') / 100 * ((int)Settings::adminSettings()->where('key', 'yandex_fee')->first()?->value ?? 1);
            $deliveryFee = (data_get($checkPrice, 'data.price') + $deliveryFee) * $rub?->rate;
            $deliveryFee += ($deliveryFee / ($shop->delivery_price ?: 1) * 100 * $this->currency());

            $km          = data_get($checkPrice, 'data.distance_meters') / 1000;
        }

		$coupon = Coupon::checkCoupon(data_get($filter, 'coupon'), $shop->id)->first();

		$couponPrice = 0;

		if ($coupon) {

			$couponPrice = $this->checkCoupon($coupon, $totalPrice);

			$totalPrice -= ($couponPrice * $this->currency());

		}

		$serviceFee = (double)Settings::adminSettings()->where('key', 'service_fee')->first()?->value ?? 0;

		return [
			'status' => true,
			'code'   => ResponseError::NO_ERROR,
			'data'   => [
				'stocks'            => $result,
				'total_tax'         => $shopTax,
				'price'             => $result->sum('price'),
				'total_shop_tax'    => $shopTax,
				'total_price'       => max($totalPrice + $deliveryFee + $shopTax + $serviceFee, 0),
				'total_discount'    => $result->sum('discount'),
				'delivery_fee'      => $deliveryFee,
				'rate'              => $this->currency(),
				'coupon_price'      => $couponPrice,
				'shop'              => $shop,
				'km'                => $km,
				'coupon'            => $coupon,
				'service_fee'       => $serviceFee,
			]
		];
	}

	/**
	 * @param int $id
	 * @param int|null $shopId
	 * @return Order|null
	 */
	public function orderById(int $id, int $shopId = null): ?Order
	{
		$locale = data_get(Language::languagesList()->where('default', 1)->first(), 'locale');

		return $this->model()
			->with([
				'user' => fn($u) => $u->withCount(['orders' => fn($u) => $u->where('status', Order::STATUS_DELIVERED)])
					->withSum(['orders' => fn($u) => $u->where('status', Order::STATUS_DELIVERED)], 'total_price'),
				'review',
				'pointHistories',
				'currency' => fn($c) => $c->select('id', 'title', 'symbol'),
				'deliveryMan' => fn($d) => $d->withAvg('assignReviews', 'rating'),
				'deliveryMan.deliveryManSetting',
				'coupon',
				'shop:id,location,tax,delivery_price,background_img,logo_img,uuid,phone',
				'shop.translation' => fn($st) => $st->where('locale', $this->language)->orWhere('locale', $locale),
				'orderDetails' => fn($od) => $od->whereNull('parent_id'),
				'orderDetails.stock.countable.unit.translation' => fn($q) => $q
					->where('locale', $this->language)
					->orWhere('locale', $locale),
				'orderDetails.stock.countable.translation' => fn($ct) => $ct
					->where('locale', $this->language)
					->orWhere('locale', $locale),
				'orderDetails.stock.stockExtras.group.translation' => function ($cgt) use($locale) {
					$cgt->select('id', 'extra_group_id', 'locale', 'title')
						->where('locale', $this->language)
						->orWhere('locale', $locale);
				},

				'orderDetails.children.stock.stockExtras.group.translation' => function ($cgt) use($locale) {
					$cgt->select('id', 'extra_group_id', 'locale', 'title')
						->where('locale', $this->language)
						->orWhere('locale', $locale);
				},
				'orderDetails.children.stock.countable.unit.translation' => fn($q) => $q
					->where('locale', $this->language)
					->orWhere('locale', $locale),
				'orderDetails.children.stock.countable.translation' => fn($ct) => $ct
					->where('locale', $this->language)
					->orWhere('locale', $locale),
				'orderRefunds',
				'transaction.paymentSystem',
				'galleries',
				'myAddress',
				'table',
			])
			->when($shopId, fn($q) => $q->where('shop_id', $shopId))
			->find($id);
	}

	/**
	 * @param int $id
	 * @param int|null $shopId
	 * @return Order|null
	 */
	public function orderByTableId(int $id, int $shopId = null): ?Order
	{
		return $this->model()
			->with([
				'pointHistories',
				'currency' => fn($q) => $q->select('id', 'title', 'symbol'),
				'waiter',
				'coupon',
				'shop:id,location,tax,background_img,logo_img,uuid,phone',
				'shop.translation' => fn($q) => $q->where('locale', $this->language),
				'orderDetails' => fn($q) => $q->whereNull('parent_id'),
				'orderDetails.stock.countable.translation' => fn($q) => $q->where('locale', $this->language),
				'orderDetails.children.stock.countable.translation' => fn($q) => $q->where('locale', $this->language),
				'orderDetails.stock.stockExtras.group.translation' => function ($q) {
					$q->select('id', 'extra_group_id', 'locale', 'title')->where('locale', $this->language);
				},
				'orderRefunds',
				'transaction.paymentSystem',
				'galleries',
				'table',
			])
			->when($shopId, fn($q) => $q->where('shop_id', $shopId))
			->where('table_id', $id)
			->where('status', Order::STATUS_NEW)
			->first();
	}

    /**
     * @param int $id
     * @return object|Builder|Model
     */
    public function showDeliveryman(int $id): Builder|Model|null
    {
        return User::with([
            'deliveryManSetting'
        ])
            ->whereHas('roles', fn($q) => $q->where('name', 'deliveryman'))
            ->select([
                'id',
                'firstname',
                'lastname',
                'phone',
            ])
            ->find($id);
    }

	/**
	 * @param Order|null $order
	 * @return OrderResource|null
	 */
	public function reDataOrder(?Order $order): OrderResource|null
	{
        if ($order->status !== Order::STATUS_NEW && !empty($order->yandex)) {
            (new YandexService)->getOrderInfo($order);
            $order = $this->orderById($order->id);
        }

		return !empty($order) ? OrderResource::make($order) : null;
	}

	/**
	 * @param Stock $stock
	 * @param $quantity
	 * @return int|mixed|null
	 */
	private function actualQuantity(Stock $stock, $quantity): mixed
	{
		$countable = $stock->countable;

		if ($quantity < ($countable?->min_qty ?? 0)) {

			$quantity = $countable->min_qty;

		} else if($quantity > ($countable?->max_qty ?? 0)) {

			$quantity = $countable->max_qty;

		}

		return $quantity > $stock->quantity ? max($stock->quantity, 0) : $quantity;
	}

	/**
	 * @param Coupon $coupon
	 * @param $totalPrice
	 * @return float|int|null
	 */
	private function checkCoupon(Coupon $coupon, $totalPrice): float|int|null
	{
		if ($coupon->qty <= 0) {
			return 0;
		}

		return $coupon->type === 'percent' ? ($totalPrice / 100) * $coupon->price : $coupon->price;
	}

	public function ordersReportChart(array $filter): array
	{
		$dateFrom = date('Y-m-d 00:00:01', strtotime(data_get($filter, 'date_from')));
		$dateTo   = date('Y-m-d 23:59:59', strtotime(data_get($filter, 'date_to', now())));
		$shopId   = data_get($filter, 'shop_id');

		$statistic = Order::where([
			['created_at', '>=', $dateFrom],
			['created_at', '<=', $dateTo],
			['status', Order::STATUS_DELIVERED]
		])
			->when($shopId, fn($q, $shopId) => $q->where('shop_id', $shopId))
			->select([
				DB::raw('sum(total_price) as total_price'),
				DB::raw('count(id) as count'),
			])
			->first();

		$quantity = OrderDetail::whereHas('order', fn($q) => $q
			->select('id', 'status', 'created_at', 'shop_id')
			->where('created_at', '>=', $dateFrom)
			->where('created_at', '<=', $dateTo)
			->where('status', Order::STATUS_DELIVERED)
			->when($shopId, fn($q, $shopId) => $q->where('shop_id', $shopId))
		)
			->whereHas('stock', fn($q) => $q->whereNull('deleted_at'))
			->sum('quantity');

		$type = data_get($filter, 'type');

		$keys = ['count', 'price', 'quantity'];

		$key  = in_array(data_get($filter, 'chart'), $keys) ? data_get($filter, 'chart') : 'count';

		$type = match ($type) {
			'year'  => '%Y',
			'week'  => '%w',
			'month' => '%Y-%m',
			default => '%Y-%m-%d',
		};

		$select = match ($key) {
			'count' => 'count(id) as count',
			'price' => 'sum(total_price) as price',
			default => 'sum(quantity) as quantity',
		};

		if ($select === 'sum(quantity) as quantity') {
			$chart = OrderDetail::whereHas('order',
				fn($q) => $q
					->select('id', 'status', 'created_at', 'shop_id')
					->where('created_at', '>=', $dateFrom)
					->where('created_at', '<=', $dateTo)
					->where('status', Order::STATUS_DELIVERED)
					->when(data_get($filter, 'shop_id'), fn($q, $shopId) => $q->where('shop_id', $shopId))
			)
				->select([
					DB::raw("(DATE_FORMAT(created_at, '$type')) as time"),
					DB::raw($select),
				])
				->groupBy('time')
				->get();
		} else {
			$chart = Order::where([
				['created_at', '>=', $dateFrom],
				['created_at', '<=', $dateTo],
				['status', Order::STATUS_DELIVERED]
			])
				->when(data_get($filter, 'shop_id'), fn($q, $shopId) => $q->where('shop_id', $shopId))
				->select([
					DB::raw("(DATE_FORMAT(created_at, '$type')) as time"),
					DB::raw($select),
				])
				->groupBy('time')
				->get();
		}

		return [
			'chart'     => ChartRepository::chart($chart, $key),
			'currency'  => $this->currency,
			'count'     => data_get($statistic, 'count', 0),
			'price'     => data_get($statistic, 'total_price', 0),
			'quantity'  => (int)$quantity ?? 0,
		];

	}

	public function orderReportTransaction(array $filter): array
	{
		$dateFrom = date('Y-m-d 00:00:01', strtotime(data_get($filter, 'date_from', '-30 days')));
		$dateTo   = date('Y-m-d 23:59:59', strtotime(data_get($filter, 'date_to', now())));
		$shopId   = data_get($filter, 'shop_id');

		$orders = Order::with([
			'transaction.paymentSystem',
			'coupon',
			'pointHistories',
			data_get($filter, 'type') === PaymentToPartner::SELLER ? 'shop.seller' : 'deliveryMan',
		])
			->withSum('coupon', 'price')
			->withSum('pointHistories', 'price')
			->where([
				['created_at', '>=', $dateFrom],
				['created_at', '<=', $dateTo],
				['status', Order::STATUS_DELIVERED]
			])
			->when($shopId, fn($q, $shopId) => $q->where('shop_id', $shopId))
			->when(data_get($filter, 'type'), function($q, $type) use ($filter) {

                if ($type === PaymentToPartner::DELIVERYMAN) {
                    $q->whereHas('deliveryMan', function ($q) use ($filter) {
						$q->when(data_get($filter, 'user_id'), fn($q, $userId) => $q->where('id', $userId));
					});
                } else if ($type === PaymentToPartner::SELLER) {
					$q->whereHas('shop', function ($q) use ($filter) {

						$q
							->whereHas('seller')
							->when(data_get($filter, 'user_id'), fn($q, $userId) => $q->where('user_id', $userId));
					});
                }

                $q->whereDoesntHave('paymentToPartner', fn($q) => $q->where('type', $type));

            });

		$sumOrders = $orders->get();

		$tax 			= $sumOrders->sum('tax');
		$coupon			= $sumOrders->sum('coupon_sum_price');
		$pointHistory	= $sumOrders->sum('point_histories_sum_price');
		$commissionFee  = $sumOrders->sum('commission_fee');
		$deliveryFee 	= $sumOrders->sum('delivery_fee');
		$serviceFee 	= $sumOrders->sum('service_fee');
		$totalPrice 	= $sumOrders->sum('total_price');

		$orders = $orders->paginate(data_get($filter, 'perPage', 10));

		return [
			'total_tax'				=> $tax,
			'total_coupon'			=> $coupon,
			'total_point_history'	=> $pointHistory,
			'total_commission_fee'	=> $commissionFee,
			'total_delivery_fee'	=> $deliveryFee,
			'total_service_fee'		=> $serviceFee,
			'total_price'			=> $totalPrice,
			'total_seller_fee' 		=> $totalPrice - $deliveryFee - $serviceFee - $commissionFee - $coupon - $pointHistory,
			'data' 					=> OrderResource::collection($orders),
			'meta'					=> [
				'page'		=> $orders->currentPage(),
				'perPage'	=> $orders->perPage(),
				'total'		=> $orders->total(),
			]
		];
	}

	/**
	 * @param array $filter
	 * @return array|Collection
	 */
	public function ordersReportChartPaginate(array $filter): array|Collection
	{
		$dateFrom = date('Y-m-d 00:00:01', strtotime(data_get($filter, 'date_from')));
		$dateTo   = date('Y-m-d 23:59:59', strtotime(data_get($filter, 'date_to', now())));
		$locale  = data_get(Language::where('default', 1)->first(['locale', 'default']), 'locale');
		$key      = data_get($filter, 'column', 'id');
		$column   = data_get([
			'id',
			'total_price'
		], $key, $key);

		$orders = Order::with([
			'user:id,firstname,lastname,active',

			'orderDetails' => fn($q) => $q->select('id', 'order_id', 'stock_id', 'quantity'),
			'orderDetails.children' => fn($q) => $q->select('id', 'order_id', 'stock_id', 'quantity'),

			'orderDetails.stock' => fn($q) => $q->withTrashed(),
			'orderDetails.stock.countable' => fn($q) => $q->withTrashed(),
			'orderDetails.stock.countable.unit' => fn($q) => $q->withTrashed(),
			'orderDetails.stock.countable.unit.translation' => fn($q) => $q->withTrashed()
				->where('locale', $this->language)
				->orWhere('locale', $locale),

			'orderDetails.children.stock' => fn($q) => $q->withTrashed(),
			'orderDetails.children.stock.countable' => fn($q) => $q->withTrashed(),
			'orderDetails.children.stock.countable.unit.translation' => fn($q) => $q
				->where('locale', $this->language)
				->orWhere('locale', $locale),

			'orderDetails.stock.countable.translation' => fn($q) => $q
				->withTrashed()
				->select('id', 'product_id', 'locale', 'title', 'deleted_at')
				->where('locale', $this->language)
				->orWhere('locale', $locale),

			'orderDetails.children.stock.countable.translation' => fn($q) => $q
				->withTrashed()
				->select('id', 'product_id', 'locale', 'title', 'deleted_at')
				->where('locale', $this->language)
				->orWhere('locale', $locale),
		])
			->where('created_at', '>=', $dateFrom)
			->where('created_at', '<=', $dateTo)
			->where('status', Order::STATUS_DELIVERED)
			->when(data_get($filter, 'shop_id'), fn($q, $shopId) => $q->where('shop_id', $shopId));

		if (data_get($filter, 'export') === 'excel') {

			$name = 'orders-report-products-' . Str::random(8);

			try {
//                ExportJob::dispatch("export/$name.xlsx", $query->get(), OrdersReportExport::class);
				Excel::store(new OrdersReportExport($orders->get()), "export/$name.xlsx",'public');

				return [
					'status'    => true,
					'code'      => ResponseError::NO_ERROR,
					'path'      => 'public/export',
					'file_name' => "export/$name.xlsx",
					'link'      => URL::to("storage/export/$name.xlsx"),
				];
			} catch (Throwable $e) {
				$this->error($e);
				return [
					'status'    => false,
					'code'      => ResponseError::ERROR_501,
					'message'   => $e->getMessage(),
				];
			}
		}

		$orders = $orders->paginate(data_get($filter, 'perPage', 10));

		foreach ($orders as $i => $order) {

			$result = [
				'id'        => $order->id,
				'status'    => $order->status,
				'firstname' => $order->user?->firstname,
				'lastname'  => $order->user?->lastname,
				'active'    => $order->user?->active,
				'quantity'  => 0,
				'price'     => $order->total_price,
				'products'  => []
			];

			foreach ($order->orderDetails as $product) {

				$children = collect($product?->children);

				$result['products'][] = $product->stock?->countable?->translation?->title . ' ' .
					$children?->implode('stock.countable.translation.title', ', ');

				$result['quantity'] += (int)(($product?->quantity ?? 0) + ($children?->sum('quantity') ?? 0));
			}

			data_set($orders, $i, $result);

		}

		$isDesc = data_get($filter, 'sort', 'desc') === 'desc';

		return collect($orders)->sortBy($column, $isDesc ? SORT_DESC : SORT_ASC, $isDesc);
	}

	/**
	 * @param array $filter
	 * @return array
	 */
	public function revenueReport(array $filter): array
	{
		$type = data_get($filter, 'type');

		$type = match ($type) {
			'year'  => 'Y',
			'week'  => 'w',
			'month' => 'Y-m',
			default => 'Y-m-d',
		};

		$dateFrom = date('Y-m-d 00:00:01', strtotime(data_get($filter, 'date_from')));
		$dateTo   = date('Y-m-d 23:59:59', strtotime(data_get($filter, 'date_to', now())));
		$column   = [
			'count',
			'tax',
			'delivery_fee',
			'canceled_sum',
			'delivered_sum',
			'total_price',
			'total_quantity',
			'delivered_avg',
			'time',
		];

		if (!in_array(data_get($filter, 'column'), $column)) {
			$filter['column'] = 'id';
		}

		$orders = Order::with([
			'orderDetails' => fn($q) => $q->select('id', 'order_id', 'quantity')
		])
			->withSum([
				'orderDetails' => fn($q) => $q->select('id', 'order_id', 'quantity')
			], 'quantity')
			->where('created_at', '>=', $dateFrom)
			->where('created_at', '<=', $dateTo)
			->whereIn('status', [Order::STATUS_DELIVERED, Order::STATUS_CANCELED])
			->when(data_get($filter, 'shop_id'), fn($q, $shopId) => $q->where('o.shop_id', $shopId))
			->select([
				'created_at',
				'id',
				'total_price',
				'status',
				'created_at',
			])
			->orderBy(data_get($filter, 'column', 'id'), data_get($filter, 'sort', 'desc'))
			->get();

		$result = [];

		foreach ($orders as $order) {

			/** @var Order $order */

			$isDelivered = $order->status === Order::STATUS_DELIVERED;
			$isCanceled  = $order->status === Order::STATUS_CANCELED;
			$date = date($type, strtotime($order->created_at));

			$canceledPrice      = data_get($result, "$date.canceled_sum", 0);
			$deliveredCount     = data_get($result, "$date.total_quantity", 0);
			$deliveredPrice     = data_get($result, "$date.total_price", 0);
			$deliveredQuantity  = data_get($result, "$date.count", 0);
			$deliveredTax       = data_get($result, "$date.tax", 0);
			$deliveredFee       = data_get($result, "$date.delivery_fee", 0);

			$quantity           = $order->orderDetails->sum('quantity');

			$result[$date] = [
				'time'           => $date,
				'canceled_sum'   => $isCanceled  ? $canceledPrice     + $order->total_price   : $canceledPrice,
				'total_quantity' => $isDelivered ? $deliveredCount    + 1                     : $deliveredCount,
				'total_price'    => $isDelivered ? $deliveredPrice    + $order->total_price   : $deliveredPrice,
				'count'          => $isDelivered ? $deliveredQuantity + $quantity             : $deliveredQuantity,
				'tax'            => $isDelivered ? $deliveredTax      + $order->tax           : $deliveredTax,
				'delivery_fee'   => $isDelivered ? $deliveredFee      + $order->delivery_fee  : $deliveredFee,
			];

		}

		if (data_get($filter, 'export') === 'excel') {

			$name = 'report-revenue-' . Str::random(8);

			try {
				Excel::store(new OrdersRevenueReportExport($result), "export/$name.xlsx",'public');

				return [
					'status'    => true,
					'code'      => ResponseError::NO_ERROR,
					'path'      => 'public/export',
					'file_name' => "export/$name.xlsx",
					'link'      => URL::to("storage/export/$name.xlsx"),
				];
			} catch (Throwable $e) {
				$this->error($e);
				return [
					'status'  => false,
					'code'    => ResponseError::ERROR_501,
					'message' => $e->getMessage(),
				];
			}

		}

		$result = collect($result);

		return [
			'paginate' => $result->values(),
		];
	}

	/**
	 * @param array $filter
	 * @return array
	 */
	public function overviewCarts(array $filter): array
	{
		$dateFrom = date('Y-m-d 00:00:01', strtotime(data_get($filter, 'date_from')));
		$dateTo   = date('Y-m-d 23:59:59', strtotime(data_get($filter, 'date_to', now())));
		$type = data_get($filter, 'type');
		$column = [
			'count',
			'tax',
			'delivery_fee',
			'canceled_sum',
			'delivered_sum',
			'delivered_avg',
			'time',
		];

		if (!in_array(data_get($filter, 'column'), $column)) {
			$filter['column'] = 'time';
		}

		$chart = DB::table('orders')
			->where('orders.created_at', '>=', $dateFrom)
			->where('orders.created_at', '<=', $dateTo)
			->whereIn('orders.status', [Order::STATUS_DELIVERED, Order::STATUS_CANCELED])
			->when(data_get($filter, 'shop_id'), fn($q, $shopId) => $q->where('orders.shop_id', $shopId))
			->selectRaw("
                sum(if(orders.status = 'delivered', 1, 0)) as count,
                sum(if(orders.status = 'delivered', orders.tax, 0)) as tax,
                sum(if(orders.status = 'delivered', orders.delivery_fee, 0)) as delivery_fee,
                sum(if(orders.status = 'canceled',  orders.total_price, 0)) as canceled_sum,
                sum(if(orders.status = 'delivered', orders.total_price, 0)) as delivered_sum,
                avg(if(orders.status = 'delivered', orders.total_price, 0)) as delivered_avg,
                (DATE_FORMAT(orders.created_at, " . ($type == 'year' ? "'%Y" : ($type == 'month' ? "'%Y-%m" : "'%Y-%m-%d")) . "')) as time
            ")
			->groupBy('time')
			->orderBy(data_get($filter, 'column', 'id'), data_get($filter, 'sort', 'desc'))
			->get();

		return [
			'chart_price'  	=> ChartRepository::chart($chart, 'delivered_sum'),
			'chart_count'  	=> ChartRepository::chart($chart, 'count'),
			'count'        	=> $chart->sum('count'),
			'tax'          	=> $chart->sum('tax'),
			'delivery_fee' 	=> $chart->sum('delivery_fee'),
			'canceled_sum' 	=> $chart->sum('canceled_sum'),
			'delivered_sum'	=> $chart->sum('delivered_sum'),
			'delivered_avg'	=> $chart->sum('delivered_avg'),
		];

	}

	/**
	 * @param array $filter
	 * @return array
	 */
	public function overviewProducts(array $filter): array
	{
		$dateFrom = date('Y-m-d 00:00:01', strtotime(data_get($filter, 'date_from')));
		$dateTo   = date('Y-m-d 23:59:59', strtotime(data_get($filter, 'date_to', now())));
		$default  = data_get(Language::where('default', 1)->first(['locale', 'default']), 'locale');
		$key      = data_get($filter, 'column', 'count');
		$shopId   = data_get($filter, 'shop_id');

		$column	  = data_get([
			'id',
			'count',
			'total_price',
			'quantity',
		], $key, $key);

		if ($column == 'id') {
			$column = 'p.id';
		}

		$orderDetails = DB::table('products as p')
			->crossJoin('stocks as s', 'p.id', '=', 's.countable_id')
			->crossJoin('order_details as od', 's.id', '=', 'od.stock_id')
			->crossJoin('orders as o', function ($builder) use ($shopId, $dateFrom, $dateTo) {
				$builder->on('od.order_id', '=', 'o.id')
					->when($shopId, fn($q) => $q->where('o.shop_id', $shopId))
					->where('o.created_at', '>=', $dateFrom)
					->where('o.created_at', '<=', $dateTo)
					->whereIn('o.status', [Order::STATUS_DELIVERED, Order::STATUS_CANCELED]);
			})
			->where('s.countable_type', '=', Product::class)
			->select([
				DB::raw("sum(od.quantity) as quantity"),
				DB::raw("sum(od.total_price) as total_price"),
				DB::raw("count(od.id) as count"),
				DB::raw("p.id as id"),
			])
			->groupBy(['id'])
			->having('count', '>', '0')
			->orHaving('total_price', '>', '0')
			->orHaving('quantity', '>', '0')
			->orderBy($column, data_get($filter, 'sort', 'desc'))
			->paginate(data_get($filter, 'perPage', 10));

		$result = collect($orderDetails->items())->transform(function ($item) use ($default) {

			$translation = ProductTranslation::withTrashed()->where('product_id', data_get($item, 'id'))
				->where(fn($q) => $q->where('locale', $this->language)->orWhere('locale', $default))
				->select('title')
				->first();

			$item->title = data_get($translation, 'title', 'EMPTY');

			return $item;
		});

		return [
			'data' => $result,
			'meta' => [
				'last_page'  => $orderDetails->lastPage(),
				'page'       => $orderDetails->currentPage(),
				'total'      => $orderDetails->total(),
				'more_pages' => $orderDetails->hasMorePages(),
				'has_pages'  => $orderDetails->hasPages(),
			]
		];
	}

	/**
	 * @param array $filter
	 * @return array
	 */
	public function overviewCategories(array $filter): array
	{
		$dateFrom = date('Y-m-d 00:00:01', strtotime(data_get($filter, 'date_from')));
		$dateTo   = date('Y-m-d 23:59:59', strtotime(data_get($filter, 'date_to', now())));
		$default  = data_get(Language::where('default', 1)->first(['locale', 'default']), 'locale');
		$key      = data_get($filter, 'column', 'count');
		$shopId   = data_get($filter, 'shop_id');

		$column   = data_get([
			'id',
			'count',
			'total_price',
			'quantity',
		], $key, $key);

		if ($column == 'id') {
			$column = 'c.id';
		}

		$orderDetails = DB::table('products as p')
			->crossJoin('stocks as s', 'p.id', '=', 's.countable_id')
			->crossJoin('order_details as od', 's.id', '=', 'od.stock_id')
			->crossJoin('orders as o', function ($builder) use ($shopId, $dateFrom, $dateTo) {
				$builder->on('od.order_id', '=', 'o.id')
					->when($shopId, fn($q) => $q->where('o.shop_id', $shopId))
					->where('o.created_at', '>=', $dateFrom)
					->where('o.created_at', '<=', $dateTo)
					->whereIn('o.status', [Order::STATUS_DELIVERED, Order::STATUS_CANCELED]);
			})
			->where('s.countable_type', '=', Product::class)
			->select([
				DB::raw("sum(od.quantity) as quantity"),
				DB::raw("sum(od.total_price) as total_price"),
				DB::raw("count(od.id) as count"),
				DB::raw("p.category_id as id"),
			])
			->groupBy(['id'])
			->having('count', '>', '0')
			->orHaving('total_price', '>', '0')
			->orHaving('quantity', '>', '0')
			->orderBy($column, data_get($filter, 'sort', 'desc'))
			->paginate(data_get($filter, 'perPage', 10));

		$result = collect($orderDetails->items())->transform(function ($item) use ($default) {

			$translation = CategoryTranslation::withTrashed()->where('category_id', data_get($item, 'id'))
				->where(fn($q) => $q->where('locale', $this->language)->orWhere('locale', $default))
				->select('title')
				->first();

			$item->title = data_get($translation, 'title', 'EMPTY');

			return $item;
		});

		return [
			'data' => $result,
			'meta' => [
				'last_page'  => $orderDetails->lastPage(),
				'page'       => $orderDetails->currentPage(),
				'total'      => $orderDetails->total(),
				'more_pages' => $orderDetails->hasMorePages(),
				'has_pages'  => $orderDetails->hasPages(),
			]
		];
	}

}
