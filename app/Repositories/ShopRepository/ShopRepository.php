<?php

namespace App\Repositories\ShopRepository;

use App\Http\Resources\CategoryResource;
use App\Http\Resources\ProductResource;
use App\Models\Category;
use App\Models\Language;
use App\Models\Order;
use App\Models\Product;
use App\Models\Settings;
use App\Models\Shop;
use App\Models\ShopTag;
use App\Models\Stock;
use App\Repositories\CoreRepository;
use App\Repositories\Interfaces\ShopRepoInterface;
use DB;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Schema;
use Throwable;

class ShopRepository extends CoreRepository implements ShopRepoInterface
{
    protected function getModelClass(): string
    {
        return Shop::class;
    }

    /**
     * Get all Shops from table
     */
    public function shopsList(array $filter = []): array|Collection|\Illuminate\Support\Collection
    {
        /** @var Shop $shop */

        $shop = $this->model();

        return $shop
            ->updatedDate($this->updatedDate)
            ->filter($filter)
            ->with([
                'translation'   => fn($q) => $q->where('locale', $this->language),
                'seller'        => fn($q) => $q->select('id', 'firstname', 'lastname', 'uuid'),
                'seller.roles',
            ])
            ->whereHas('translation', fn($q) => $q->where('locale', $this->language))
            ->orderByDesc('id')
            ->limit(data_get($filter, 'perPage', 1))
            ->offset(data_get($filter, 'page', 0))
            ->get();
    }

    /**
     * Get one Shop by UUID
     * @param array $filter
     * @return LengthAwarePaginator
     */
    public function shopsPaginate(array $filter): LengthAwarePaginator
    {
        /** @var Shop $shop */
        $shop = $this->model();

        return $shop
            ->filter($filter)
            ->updatedDate($this->updatedDate)
            ->with([
                'translation' => function ($query) use ($filter) {

                    $query->when(data_get($filter, 'not_lang'),
                        fn($q, $notLang) => $q->where('locale', '!=', data_get($filter, 'not_lang')),
                        fn($q) => $q->where('locale', '=', $this->language),
                    );

                },
                'bonus' => fn($q) => $q->where('expired_at', '>', now())->where('status', true)
                    ->select([
                        'bonusable_type',
                        'bonusable_id',
                        'bonus_quantity',
                        'bonus_stock_id',
                        'expired_at',
                        'value',
                        'type',
                    ]),
                'bonus.stock.countable:id,uuid',
                'bonus.stock.countable.translation' => fn($q) => $q->where('locale', $this->language)
                    ->select('id', 'locale', 'title', 'product_id'),
                'closedDates',
                'workingDays' => fn($q) => $q->when(data_get($filter, 'work_24_7'),
                    fn($b) => $b->where('from', '01-00')->where('to', '>=', '23-00')
                ),
                'discounts' => fn($q) => $q
                    ->where('end', '>=', now())
                    ->where('active', 1)
                    ->select('id', 'shop_id', 'end', 'active'),
            ])
            ->whereHas('translation', function ($query) use ($filter) {

                $query->when(data_get($filter, 'not_lang'),
                    fn($q, $notLang) => $q->where('locale', '!=', data_get($filter, 'not_lang')),
                    fn($q) => $q->where('locale', '=', $this->language),
                );

            })
            ->when(data_get($filter, 'prices'), function (Builder $q, $prices) {

                $to   = data_get($prices, 0, 1) / $this->currency;
                $from = data_get($prices, 1, 1) / $this->currency;

                $q->whereHas('products.stocks', fn($q) => $q->where([
                    ['price', '>=', $to],
                    ['price', '<=', $to >= $from ? Stock::max('price') : $from],
                ]));

            })
            ->withCount('reviews')
            ->when(data_get($filter, 'rating'), function (Builder $q, $rating) {

                $rtg = [
                    0 => data_get($rating, 0, 0),
                    1 => data_get($rating, 1, 5),
                ];

                $q
                    ->withAvg([
                        'reviews' => fn(Builder $b) => $b->whereBetween('rating', $rtg)
                    ], 'rating')
					->having('reviews_avg_rating', '>=', $rtg[0])
					->having('reviews_avg_rating', '<=', $rtg[1]);

            }, fn($q) => $q->withAvg('reviews', 'rating'))
            ->when(data_get($filter, 'order_by'), function (Builder $query, $orderBy) {

                switch ($orderBy) {
                    case 'new':
                        $query->orderBy('created_at', 'desc');
                        break;
                    case 'old':
                        $query->orderBy('created_at');
                        break;
                    case 'best_sale':
                        $query->withCount('orders')->orderBy('orders_count', 'desc');
                        break;
                    case 'low_sale':
                        $query->withCount('orders')->orderBy('orders_count');
                        break;
                    case 'high_rating':
                        $query->orderBy('reviews_avg_rating', 'desc');
                        break;
                    case 'low_rating':
                        $query->orderBy('reviews_avg_rating');
                        break;
                    case 'trust_you':
                        $ids = implode(', ', array_keys(Cache::get('shop-recommended-ids', [])));
                        if (!empty($ids)) {
                            $query->orderByRaw(DB::raw("FIELD(id, $ids) ASC"));
                        }
                        break;
                }

            }, fn($q) => data_get($filter, 'address') ? $q->orderBy(
                data_get($filter, 'column', 'id'),
                data_get($filter, 'sort', 'desc')
            ) : $q)
            ->paginate(data_get($filter, 'perPage', 10));
    }

    /**
     * Get one Shop by UUID
     * @param array $filter
     * @return LengthAwarePaginator
     */
    public function selectPaginate(array $filter): LengthAwarePaginator
    {
        /** @var Shop $shop */
        $shop = $this->model();

        return $shop
            ->filter($filter)
            ->updatedDate($this->updatedDate)
            ->with([
                'translation' => fn($q) => $q->where('locale', $this->language)
                    ->select('id', 'locale', 'title', 'shop_id'),
            ])
            ->whereHas('translation', fn($q) => $q->where('locale', $this->language))
            ->select([
                'id',
            ])
            ->paginate(data_get($filter, 'perPage', 10));
    }

    /**
     * @param string $uuid
     * @return Model|Builder|null
     */
    public function shopDetails(string $uuid): Model|Builder|null
    {
        /** @var Shop $shop */
        $shop = $this->model();
        $locale = data_get(Language::languagesList()->where('default', 1)->first(), 'locale');

        return $shop->with([
            'translation' => fn($q) => $q->where('locale', $this->language)->orWhere('locale', $locale),
            'workingDays',
            'closedDates',
            'bonus' => fn($q) => $q->where('expired_at', '>', now())->where('status', true)
                ->select([
                    'bonusable_type',
                    'bonusable_id',
                    'bonus_quantity',
                    'bonus_stock_id',
                    'expired_at',
                    'value',
                    'type',
                ]),
            'bonus.stock.countable' => fn($q) => $q->select('id', 'uuid'),
            'bonus.stock.countable.translation' => fn($q) => $q->where('locale', $this->language)
                ->select('id', 'locale', 'title', 'product_id'),
            'discounts' => fn($q) => $q->where('end', '>=', now())->where('active', 1)
                ->select('id', 'shop_id', 'end', 'active'),
            'shopPayments:id,payment_id,shop_id,status,client_id,secret_id',
            'shopPayments.payment:id,tag,input,sandbox,active',
            'categories.translation' => fn($q) => $q->where('locale', $this->language)->orWhere('locale', $locale),
        ])
//            ->whereHas('translation', fn($q) => $q->where('locale', $this->language))
            ->withAvg('reviews', 'rating')
            ->withCount('reviews')
            ->where(fn($q) => $q->where('uuid', $uuid)->orWhere('id', $uuid))
            ->first();
    }

    /**
     * @param array $filter
     * @return Collection|array
     */
    public function takes(array $filter): Collection|array
    {
        $locale = data_get(Language::languagesList()->where('default', 1)->first(), 'locale');

        return ShopTag::with([
            'translation' => fn($q) => $q->where('locale', $this->language)->orWhere('locale', $locale),
        ])
            ->when(data_get($filter, 'category_id'), function ($query, $categoryId) {
                $query->whereHas('assignShopTags', function ($q) use ($categoryId) {
                    $q->whereHas('shop', function ($q) use ($categoryId) {
                        $q
                            ->select('id')
                            ->whereHas('categories', function ($q) use ($categoryId) {
                                $q->select('category_id', 'categories.shop_id')->where('category_id', $categoryId);
                            });
                    });
                });
            })
            ->get();
    }

    /**
     * @return mixed
     */
    public function productsAvgPrices(): mixed
    {
        return Cache::remember('products-avg-prices', 86400, function () {

            $min = Stock::where('price', '>=', 0)
				->where('quantity', '>', 0)
                ->whereHas('countable', fn($q) => $q->where('status', Product::PUBLISHED)->where('active', true))
                ->min('price');

            $max = Stock::where('price', '>=', 0)
				->where('quantity', '>', 0)
                ->whereHas('countable', fn($q) => $q->where('status', Product::PUBLISHED)->where('active', true))
                ->max('price');

            return [
                'min' => $min * $this->currency,
                'max' => ($min === $max ? $max + 1 : $max) * $this->currency,
            ];
        });
    }

    /**
     * @param array $filter
     * @return LengthAwarePaginator
     */
    public function shopsSearch(array $filter): LengthAwarePaginator
    {
        /** @var Shop $shop */
        $shop = $this->model();

        return $shop
            ->filter($filter)
            ->with([
                'translation'   => fn($q) => $q->where('locale', $this->language),
                'discounts'     => fn($q) => $q->where('end', '>=', now())->where('active', 1)
                    ->select('id', 'shop_id', 'end', 'active'),
            ])
            ->whereHas('translation', fn($q) => $q->where('locale', $this->language))
            ->latest()
            ->select([
                'id',
                'logo_img',
				'background_img',
				'status',
				'open',
            ])
            ->paginate(data_get($filter, 'perPage', 10));
    }

    /**
     * @param array $filter
     * @return mixed
     */
    public function shopsByIDs(array $filter): mixed
    {
        /** @var Shop $shop */
        $shop = $this->model();

        return $shop->with([
            'translation'   => fn($q) => $q->where('locale', $this->language),
            'discounts'     => fn($q) => $q->where('end', '>=', now())->where('active', 1)
                ->select('id', 'shop_id', 'end', 'active'),
            'tags:id,img',
            'tags.translation' => fn($q) => $q->where('locale', $this->language),
        ])
            ->withAvg('reviews', 'rating')
            ->withCount('reviews')
            ->when(data_get($filter, 'status'), fn($q, $status) => $q->where('status', $status))
            ->find(data_get($filter, 'shops', []));
    }

    public function recommended(array $filter): array
    {
//        if (!empty(Cache::get('shops-recommended'))) {
//            return Cache::get('shops-recommended');
//        }

        $recommendedCount = (int)(Settings::adminSettings()->where('key', 'recommended_count')->first()?->value ?? 2);

        $shops = Shop::filter($filter)
            ->with([
                'orders' => fn($q) => $q
                    ->whereHas('orderDetails')
                    ->with([
                        'orderDetails' => fn($od) => $od->with([
                            'stock' => fn($s) => $s->with([
                                'countable' => fn($c) => $c->where('active', true)->where('addon', false)
                                    ->where('status', Product::PUBLISHED)
                            ])
                                ->whereHas('countable', fn($c) => $c->where('active', true)
                                    ->where('addon', false)
                                    ->where('status', Product::PUBLISHED)
                                )
                                ->where('quantity', '>', 0)
                        ])->whereHas('stock', fn($s) => $s->where('quantity', '>', 0)),
                    ])
                    ->whereStatus(Order::STATUS_DELIVERED)
                    ->where('created_at', '>=', date('Y-m-d 00:00:01', strtotime('-30 days'))),
                'translation' => fn($q) => $q
                    ->where('locale', $this->language),
            ])
            ->whereHas('orders', fn($q) => $q
                ->whereStatus(Order::STATUS_DELIVERED)
                ->where('created_at', '>=', date('Y-m-d 00:00:01', strtotime('-30 days'))),
            )
            ->select([
                'id',
                'uuid',
                'background_img',
                'logo_img',
                'open',
                'status',
            ])
            ->where('open', 1)
            ->where('status', 'approved')
            ->get();

        $result = [];
        $cache  = [];

        foreach ($shops as $shop) {

            /** @var Shop $shop */
            $result[$shop->id] = [];
            $result[$shop->id]['id']               = $shop->id;
            $result[$shop->id]['uuid']             = $shop->uuid;
            $result[$shop->id]['background_img']   = $shop->background_img;
            $result[$shop->id]['logo_img']         = $shop->logo_img;
            $result[$shop->id]['translation']      = $shop->translation;
            $result[$shop->id]['open']             = $shop->open;
            $result[$shop->id]['products_count']   = 0;

            foreach ($shop->orders as $order) {

                /** @var Order $order */
                if ($order->orderDetails?->count() === 0) {
                    continue;
                }

                $productsCount = $order->orderDetails
                    ->where('stock.countable.status', Product::PUBLISHED)
                    ->groupBy('stock.countable.id')
                    ->keys()
                    ->toArray();

                if (!empty(data_get($cache, $shop->id))) {
                    $cache[$shop->id] = array_unique(array_merge($productsCount, $cache[$shop->id]));
                } else {
                    $cache[$shop->id] = $productsCount;
                }

            }

            $result[$shop->id]['products_count'] += count(data_get($cache, "$shop->id", []));

            if ((int)data_get($result, "$shop->id.products_count") < $recommendedCount) {
                unset($result[$shop->id]);
            }

        }

        $result = collect($result)->toArray();

		try {
			Cache::put('shop-recommended-ids', $cache, 86400); // 1 day
		} catch (Throwable) {

		}

        return array_values($result);
    }

	public function productsRecPaginate(array $filter): AnonymousResourceCollection
	{
		$shopId     = data_get($filter, 'shop_id');
		$locale     = data_get(Language::languagesList()->where('default', 1)->first(), 'locale');
		$recPerPage = data_get($filter, 'recPerPage', 10);

		$recommendedCount = (int)(Settings::adminSettings()->where('key', 'recommended_count')->first()?->value ?? 2);

		$products = Product::filter($filter)
			->with([
				'stock' => fn($q) => $q
					->where('addon', false)
					->where('quantity', '>', 0)
					->whereHas('orderDetails', function ($q) {
						$q->whereHas('order', fn($q) => $q->where('status', Order::STATUS_DELIVERED));
					}, count: $recommendedCount),
				'translation' => fn($q) => $q->where('locale', $this->language)->orWhere('locale', $locale),
				'discounts' => fn($q) => $q
					->where('start', '<=', today())
					->where('end', '>=', today())
					->where('active', 1),
			])
			->withCount([
				'stocks' => fn($q) => $q->where('quantity', '>', 0)
			])
			->where('active', true)
			->where('addon',  false)
			->where('status', Product::PUBLISHED)
			->whereHas('stock', function ($query) use ($recommendedCount) {
				$query
					->where('addon', false)
					->where('quantity', '>', 0)
					->whereHas('orderDetails', function ($q) {
						$q->whereHas('order', fn($q) => $q->where('status', Order::STATUS_DELIVERED));
					}, count: $recommendedCount);
			})
			->whereHas('translation', fn($q) => $q->where('locale', $this->language)->orWhere('locale', $locale))
			->select([
				'id',
				'uuid',
				'category_id',
				'shop_id',
				'img',
				'status',
				'active',
				'addon',
			])
			->where('shop_id', $shopId)
			->paginate($recPerPage);

		return ProductResource::collection($products);
	}

	public function branchProducts(array $filter): AnonymousResourceCollection
	{
		$shopId         = data_get($filter, 'shop_id');
		$locale         = data_get(Language::languagesList()->where('default', 1)->first(), 'locale');
		$productPerPage = data_get($filter, 'productPerPage', 10);

		$categories = $this->firstRememberProduct($filter, $locale, $shopId, $productPerPage);

		return CategoryResource::collection($categories);
	}

	/**
	 * @param array $filter
	 * @param string $locale
	 * @param int|null $shopId
	 * @param int $productPerPage
	 * @return LengthAwarePaginator
	 */
	private function firstRememberProduct(array $filter, string $locale, ?int $shopId, int $productPerPage): LengthAwarePaginator
	{
		$categories = Category::where([
			['status', Category::PUBLISHED],
			['active', true],
		])
			->with([
				'translation' => function ($q) use($locale) {
					$q
						->select('id', 'locale', 'title', 'category_id')
						->where('locale', $this->language)
						->orWhere('locale', $locale);
				},
				'products' => fn($q) => $q
					->with([
						'discounts' => fn($q) => $q
							->where('start', '<=', today())
							->where('end', '>=', today())
							->where('active', 1),
						'translation' => fn($q) => $q->where('locale', $this->language),
						'stock' => fn($q) => $q->with([
							'bonus' => fn($q) => $q->where('expired_at', '>', now())->select([
								'id', 'expired_at', 'bonusable_type', 'bonusable_id',
								'bonus_quantity', 'value', 'type', 'status'
							])
						])
							->select([
								'id', 'countable_type', 'countable_id', 'price', 'quantity', 'addon'
							])
							->where('quantity', '>', 0)
							->where('addon', false),

					])
					->whereHas('stock', fn($q) => $q->where('quantity', '>', 0)->where('addon', false))
					->withCount('stocks')
					->select([
						'id',
						'uuid',
						'category_id',
						'shop_id',
						'img',
						'status',
						'shop_id',
						'active',
						'max_qty',
						'min_qty',
						'addon',
						'vegetarian',
					])
					->where('active', true)
					->where('addon', false)
					->where('status', Product::PUBLISHED)
					->where('shop_id', $shopId)
					->when(data_get($filter, 'order_by'), function (Builder $query, $orderBy) {

						switch ($orderBy) {
							case 'new':
								$query->orderBy('created_at', 'desc');
								break;
							case 'old':
								$query->orderBy('created_at');
								break;
							case 'best_sale':
								$query->withCount('orderDetails')->orderBy('order_details_count', 'desc');
								break;
							case 'low_sale':
								$query->withCount('orderDetails')->orderBy('order_details_count');
								break;
							case 'trust_you':
								$ids = implode(', ', array_keys(Cache::get('shop-recommended-ids', [])));
								if (!empty($ids)) {
									$query->orderByRaw(DB::raw("FIELD(id, $ids) ASC"));
								}
								break;
						}

					},
						fn($q) => $q->orderBy(
							data_get($filter, 'column', 'id'),
							data_get($filter, 'sort', 'desc')
						)
					)
			])
			->whereHas('products', fn($q) => $q
				->select([
					'id',
					'uuid',
					'category_id',
					'shop_id',
					'img',
					'status',
					'shop_id',
					'active',
					'max_qty',
					'min_qty',
					'addon',
					'vegetarian',
				])
				->where('active', true)
				->where('addon', false)
				->where('status', Product::PUBLISHED)
				->where('shop_id', $shopId)
				->whereHas('stock', fn($q) => $q->where('quantity', '>', 0)->where('addon', false))
			)
			->whereHas('translation', fn($q) => $q->where('locale', $this->language)->orWhere('locale', $locale))
			->paginate(data_get($filter, 'perPage', 10));

		foreach ($categories as $key => $category) {

			/** @var Category $category */
			$products = $category->products()
				->with([
					'discounts' => fn($q) => $q
						->where('start', '<=', today())
						->where('end', '>=', today())
						->where('active', 1),
					'translation' => fn($q) => $q->where('locale', $this->language),
					'stock' => fn($q) => $q->with([
						'bonus' => fn($q) => $q->where('expired_at', '>', now())->select([
							'id', 'expired_at', 'bonusable_type', 'bonusable_id',
							'bonus_quantity', 'value', 'type', 'status'
						])
					])
						->select([
							'id', 'countable_type', 'countable_id', 'price', 'quantity', 'addon'
						])
						->where('quantity', '>', 0)
						->where('addon', false),

				])
				->whereHas('stock', fn($q) => $q->where('quantity', '>', 0)->where('addon', false))
				->withCount('stocks')
				->select([
					'id',
					'uuid',
					'category_id',
					'shop_id',
					'img',
					'status',
					'shop_id',
					'active',
					'max_qty',
					'min_qty',
					'addon',
					'vegetarian',
				])
				->when(data_get($filter, 'order_by'), function (Builder $query, $orderBy) {

					switch ($orderBy) {
						case 'new':
							$query->orderBy('created_at', 'desc');
							break;
						case 'old':
							$query->orderBy('created_at');
							break;
						case 'best_sale':
							$query->withCount('orderDetails')->orderBy('order_details_count', 'desc');
							break;
						case 'low_sale':
							$query->withCount('orderDetails')->orderBy('order_details_count');
							break;
						case 'trust_you':
							$ids = implode(', ', array_keys(Cache::get('shop-recommended-ids', [])));
							if (!empty($ids)) {
								$query->orderByRaw(DB::raw("FIELD(id, $ids) ASC"));
							}
							break;
					}

				},
					fn($q) => $q->orderBy(
						data_get($filter, 'column', 'id'),
						data_get($filter, 'sort', 'desc')
					)
				)
				->where('shop_id', $shopId)
				->take($productPerPage)
				->get();

			$productsCount = $products->count();

			if ($productsCount <= 0) {
				unset($categories[$key]);
				continue;
			}

			$category->setRelation('products_count', $productsCount);
			$category->setRelation('products', $products);
		}

		return $categories;
	}


	/**
     * @param array $filter
     * @return array
     */
    public function products(array $filter): array
    {
		/** @var Category $category */

		$locale  	 = data_get(Language::languagesList()->where('default', 1)->first(), 'locale');
		$shopId      = data_get($filter, 'shop_id');
		$category    = Category::with(['children:id,parent_id'])->find(data_get($filter, 'category_id'));
		$categoryIds = $category?->children?->pluck('id')?->merge([$category?->id])?->toArray();
		$recommendedCount = (int)(Settings::adminSettings()->where('key', 'recommended_count')->first()?->value ?? 2);

        $recommended = Product::with([
            'stock' => fn($q) => $q
                ->with([
                    'bonus' => fn($q) => $q->where('expired_at', '>', now())->where('status', true)->select([
                        'id', 'expired_at', 'bonusable_type', 'bonusable_id',
                        'bonus_quantity', 'value', 'type', 'status'
                    ]),
                ])
                ->select([
                    'id',
                    'countable_type',
                    'countable_id',
                    'price',
                    'quantity',
                    'addon',
                ])
                ->where('addon', false)
                ->where('quantity', '>', 0),
            'translation' => fn($q) => $q->where('locale', $this->language),
            'discounts' => fn($q) => $q
                ->where('start', '<=', today())
                ->where('end', '>=', today())
                ->where('active', 1),
        ])
            ->where('active', true)
            ->where('addon', false)
            ->where('status', Product::PUBLISHED)
            ->where('shop_id', $shopId)
            ->when($categoryIds, fn($q) => $q->whereIn('category_id', $categoryIds))
			->when(data_get($filter, 'brand_id'), fn($q, $brandId) => $q->where('brand_id', $brandId))
			->when(data_get($filter, 'brand_ids.*'), fn($q) => $q->whereIn('brand_id', $filter['brand_ids']))
            ->whereHas('stock', function ($query) use ($recommendedCount) {
				$query
					->where('addon', false)
					->where('quantity', '>', 0)
					->whereHas('orderDetails', function ($q) {
						$q->whereHas('order', fn($q) => $q->where('status', Order::STATUS_DELIVERED));
					}, count: $recommendedCount);
			})
            ->whereHas('translation', fn($q) => $q->where('locale', $this->language)->orWhere('locale', $locale))
            ->select([
                'id',
                'uuid',
                'category_id',
                'shop_id',
                'img',
                'status',
                'active',
                'addon',
                'min_qty',
				'max_qty',
				'interval',
			])
            ->get();

		$column = data_get($filter, 'column', 'id');

		if (!Schema::hasColumn('categories', $column)) {
			$column = 'id';
		}

        $categories = Category::where([
            ['active', true],
            ['status', Category::PUBLISHED],
        ])
			->when($categoryIds,
				fn($q) => $q->whereIn('id', $categoryIds)->when($category->type === Category::MAIN, fn($q) => $q->where('type', Category::MAIN)),
				fn($q, $id) => $q->where('type', Category::MAIN),
			)
            ->with([
				'translation' => fn($q) => $q->where('locale', $this->language)
					->orWhere('locale', $locale)
					->select('id', 'locale', 'title', 'category_id'),

				'children.translation' => fn($q) => $q->where('locale', $this->language)
					->orWhere('locale', $locale)
					->select('id', 'locale', 'title', 'category_id'),

				'products' => fn($q) => $q
					->with([
						'discounts' => fn($q) => $q
							->where('start', '<=', today())
							->where('end', '>=', today())
							->where('active', 1),
						'translation' => fn($q) => $q->where('locale', $this->language),
						'stock' => fn($q) => $q
							->with([
								'bonus' => fn($q) => $q
									->where('expired_at', '>', now())
									->where('status', true)
									->select([
										'id', 'expired_at', 'bonusable_type', 'bonusable_id',
										'bonus_quantity', 'value', 'type', 'status'
									])
							])
							->select([
								'id', 'countable_type', 'countable_id',
								'price', 'quantity', 'addon'
							])
							->where('quantity', '>', 0)
							->where('addon', false),
					])
					->whereHas('stock', fn($q) => $q->where('quantity', '>', 0)->where('addon', false))
					->whereHas('translation',
						fn($q) => $q->where('locale', $this->language)->orWhere('locale', $locale)
					)
					->where('active', true)
					->where('addon', false)
					->where('status', Product::PUBLISHED)
					->where('shop_id', $shopId)
					->when($categoryIds, fn($q) => $q->whereIn('category_id', $categoryIds))
					->when(data_get($filter, 'brand_id'), fn($q, $brandId) => $q->where('brand_id', $brandId))
					->when(data_get($filter, 'brand_ids.*'), fn($q) => $q->whereIn('brand_id', $filter['brand_ids']))
					->select([
						'id',
						'uuid',
						'shop_id',
						'brand_id',
						'category_id',
						'img',
						'status',
						'active',
						'min_qty',
						'max_qty',
						'interval',
						'addon',
					]),
				'children.products' => fn($q) => $q
					->with([
						'discounts' => fn($q) => $q
							->where('start', '<=', today())
							->where('end', '>=', today())
							->where('active', 1),
						'translation' => fn($q) => $q->where('locale', $this->language),
						'stock' => fn($q) => $q
							->with([
								'bonus' => fn($q) => $q
									->where('expired_at', '>', now())
									->where('status', true)
									->select([
										'id', 'expired_at', 'bonusable_type', 'bonusable_id',
										'bonus_quantity', 'value', 'type', 'status'
									])
							])
							->select([
								'id', 'countable_type', 'countable_id',
								'price', 'quantity', 'addon'
							])
							->where('quantity', '>', 0)
							->where('addon', false),
					])
					->whereHas('stock', fn($q) => $q->where('quantity', '>', 0)->where('addon', false))
					->whereHas('translation',
						fn($q) => $q->where('locale', $this->language)->orWhere('locale', $locale)
					)
					->where('active', true)
					->where('addon', false)
					->where('status', Product::PUBLISHED)
					->where('shop_id', $shopId)
					->when($categoryIds, fn($q) => $q->whereIn('category_id', $categoryIds))
					->when(data_get($filter, 'brand_id'), fn($q, $brandId) => $q->where('brand_id', $brandId))
					->when(data_get($filter, 'brand_ids.*'), fn($q) => $q->whereIn('brand_id', $filter['brand_ids']))
					->select([
						'id',
						'uuid',
						'shop_id',
						'brand_id',
						'category_id',
						'img',
						'status',
						'active',
						'min_qty',
						'max_qty',
						'interval',
						'addon',
					])

			])
            ->where(function ($q) use ($shopId, $categoryIds, $filter, $locale) {
				$q
					->whereHas('products', fn($q) => $q
						->whereHas('stock', fn($q) => $q->where('quantity', '>', 0)->where('addon', false))
						->whereHas('translation',
							fn($q) => $q->where('locale', $this->language)->orWhere('locale', $locale)
						)
						->where('active', true)
						->where('addon', false)
						->where('status', Product::PUBLISHED)
						->where('shop_id', $shopId)
						->when($categoryIds, fn($q) => $q->whereIn('category_id', $categoryIds))
						->when(data_get($filter, 'brand_id'), fn($q, $brandId) => $q->where('brand_id', $brandId))
						->when(data_get($filter, 'brand_ids.*'), fn($q) => $q->whereIn('brand_id', $filter['brand_ids']))
					)
					->orWhereHas('children', fn($q) => $q
						->whereHas('products', fn($q) => $q
							->whereHas('stock', fn($q) => $q->where('quantity', '>', 0)->where('addon', false))
							->whereHas('translation',
								fn($q) => $q->where('locale', $this->language)->orWhere('locale', $locale)
							)
							->where('active', true)
							->where('addon', false)
							->where('status', Product::PUBLISHED)
							->where('shop_id', $shopId)
							->when($categoryIds, fn($q) => $q->whereIn('category_id', $categoryIds))
							->when(data_get($filter, 'brand_id'), fn($q, $brandId) => $q->where('brand_id', $brandId))
							->when(data_get($filter, 'brand_ids.*'), fn($q) => $q->whereIn('brand_id', $filter['brand_ids']))
						)
					);
			})
			->whereHas('translation', fn($q) => $q->where('locale', $this->language)->orWhere('locale', $locale))
            ->select([
                'id',
                'uuid',
                'img',
                'active',
                'status',
                'input',
                'type',
                'shop_id',
            ])
            ->orderBy($column, data_get($filter, 'sort', 'desc'))
            ->paginate(data_get($filter, 'perPage', 10));

        return [
            'recommended' => ProductResource::collection($recommended),
            'all'         => CategoryResource::collection($categories)
        ];
    }

    /**
     * @param array $filter
     * @return LengthAwarePaginator
     */
    public function categories(array $filter): LengthAwarePaginator
    {
		$filter['type'] = 'main';

		$locale = data_get(Language::languagesList()->where('default', 1)->first(), 'locale');

		return Category::filter($filter)
			->withSecondChildren(['lang' => $this->language])
            ->whereHas('translation', fn($q) => $q->where('locale', $this->language)->orWhere('locale', $locale))
            ->whereHas('products', fn($q) => $q->where('shop_id', data_get($filter, 'shop_id')))
            ->orderBy('id')
            ->paginate(data_get($filter, 'perPage', 10));
    }

    /**
     * @param array $filter
     * @return LengthAwarePaginator
     */
    public function productsPaginate(array $filter): LengthAwarePaginator
    {
        $shopId = data_get($filter, 'shop_id');

		/** @var Category|null $category */
		$category = Category::with(['children:id,parent_id'])->find(data_get($filter, 'category_id'));

		$categoryIds = $category?->children?->pluck('id')?->merge([$category?->id])?->toArray();

		$column = data_get($filter, 'column', 'id');

		if (!Schema::hasColumn('products', $column) && !in_array($column, ['price_asc', 'price_desc'])) {
			$column = 'id';
		}

        return Product::with([
            'translation' => fn($q) => $q->where('locale', $this->language),
            'stocks' => fn($q) => $q
				->select('id', 'countable_type', 'countable_id', 'price', 'quantity', 'addon')
                ->with([
                    'bonus' => fn($q) => $q->where('expired_at', '>', now())->where('status', true)->select([
                        'id', 'expired_at', 'bonusable_type', 'bonusable_id',
                        'bonus_quantity', 'value', 'type', 'status'
                    ])
                ])
				->where('addon', false)
				->where('quantity', '>', 0),
            'discounts' => fn($q) => $q
                ->where('start', '<=', today())
                ->where('end', '>=', today())
                ->where('active', 1),
        ])
			->select([
				'id',
				'uuid',
				'category_id',
				'brand_id',
				'unit_id',
				'shop_id',
				'keywords',
				'tax',
				'active',
				'img',
				'min_qty',
				'max_qty',
				'interval',
				'addon',
			])
			->whereHas('translation', fn($q) => $q->where('locale', $this->language))
			->whereHas('stocks', fn($q) => $q->where('quantity', '>', 0))
			->when(data_get($filter, 'brand_id'), fn($q, $brandId) => $q->where('brand_id', $brandId))
			->when(data_get($filter, 'brand_ids.*'), fn($q) => $q->whereIn('brand_id', $filter['brand_ids']))
            ->where([
                ['shop_id', $shopId],
                ['status',  Product::PUBLISHED],
                ['active',  true],
                ['addon',  false],
            ])
            ->when(is_array($categoryIds) && count($categoryIds) > 0, fn($q) => $q->whereIn('category_id', $categoryIds))
			->when(in_array($column, ['price_asc', 'price_desc']), function ($q) use ($column) {

				if ($column === 'price_desc') {
					return $q
						->withMax('stocks', 'price')
						->orderBy('stocks_max_price', 'desc');
				}

				return $q
					->withMin('stocks', 'price')
					->orderBy('stocks_min_price', 'asc');

			}, fn($q) => $q->orderBy($column, data_get($filter, 'sort', 'desc')))
            ->paginate(data_get($filter, 'perPage', 10));
    }

    /**
     * @param array $filter
     * @return LengthAwarePaginator
     */
    public function productsRecommendedPaginate(array $filter): LengthAwarePaginator
    {
        $shopId = data_get($filter, 'shop_id');

        $ids = !empty($shopId) ? data_get(Cache::get('shop-recommended-ids'), $shopId, []) : [];

        return Product::with([
            'stocks' => fn($q) => $q->select('id', 'countable_type', 'countable_id', 'price', 'quantity', 'addon')
                ->where('quantity', '>', 0),
            'translation' => fn($q) => $q->where('locale', $this->language),
            'stocks.addons.addon' => fn($query) => $query->select([
                'id',
                'uuid',
                'tax',
                'status',
                'active',
                'img',
				'min_qty',
				'max_qty',
				'interval',
            ])->when(data_get($filter, 'addon_status'), fn($q, $status) =>
                $q->where('active', true)->where('status', '=', $status)
            ),
            'stocks.addons.addon.stock',
            'stocks.addons.addon.translation' => fn($q) => $q->where('locale', $this->language),
            'stocks.bonus' => fn($q) => $q->where('expired_at', '>', now())->where('status', true)->select([
                'id', 'expired_at', 'bonusable_type', 'bonusable_id',
                'bonus_quantity', 'value', 'type', 'status'
            ]),
            'stocks.bonus.stock',
            'stocks.bonus.stock.countable:id,uuid,tax,status,active,img,min_qty,max_qty,interval',
            'stocks.bonus.stock.countable.translation' => fn($q) => $q->select('id', 'product_id', 'title', 'locale'),
            'stocks.stockExtras.group.translation' => fn($q) => $q->where('locale', $this->language),
            'discounts' => fn($q) => $q->where('start', '<=', today())->where('end', '>=', today())->where('active', 1),
        ])
            ->whereHas('stocks', fn($q) => $q->where('quantity', '>', 0))
            ->select([
                'id',
                'uuid',
                'category_id',
                'img',
                'status',
                'active',
                'addon',
                'shop_id',
				'min_qty',
				'max_qty',
				'interval',
            ])
            ->where([
                ['shop_id', $shopId],
                ['status',  Product::PUBLISHED],
                ['active',  true],
                ['addon',  false],
            ])
            ->whereHas('translation', fn($q) => $q->where('locale', $this->language))
            ->whereIn('id', $ids)
            ->paginate(data_get($filter, 'perPage', 10));
    }
}
