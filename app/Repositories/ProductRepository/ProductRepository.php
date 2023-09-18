<?php

namespace App\Repositories\ProductRepository;

use App\Exports\ProductReportExport;
use App\Exports\StockExport;
use App\Exports\StockReportExport;
use App\Helpers\ResponseError;
use App\Http\Resources\ProductReportResource;
use App\Jobs\ExportJob;
use App\Models\Language;
use App\Models\Order;
use App\Models\Product;
use App\Models\Stock;
use App\Models\UserActivity;
use App\Repositories\CoreRepository;
use App\Repositories\Interfaces\ProductRepoInterface;
use App\Repositories\ReportRepository\ChartRepository;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;
use Jenssegers\Agent\Agent;
use Maatwebsite\Excel\Facades\Excel;
use PhpOffice\PhpSpreadsheet\Exception;
use Throwable;

class ProductRepository extends CoreRepository implements ProductRepoInterface
{
    protected function getModelClass(): string
    {
        return Product::class;
    }

    public function productsPaginate(array $filter): LengthAwarePaginator
    {
        /** @var Product $product */
        $product = $this->model();
        $locale  = data_get(Language::languagesList()->where('default', 1)->first(), 'locale');

        $stocks = [
            'stock.stockExtras.group.translation' => fn($q) => $q->where('locale', $this->language)->orWhere('locale', $locale),
        ];

        if (!data_get($filter, 'addon')) {
            $stocks = [
                'stocks.addons.addon' => fn($query) => $query->with([
                    'stock'         => fn($q) => $q->where('addon', true)->where('quantity', '>', 0),
                    'translation'   => fn($q) => $q->where('locale', $this->language)->orWhere('locale', $locale),
                ])
                    ->whereHas('stock', fn($q) => $q->where('addon', true)->where('quantity', '>', 0))
                    ->whereHas('translation', fn($q) => $q->where('locale', $this->language)->orWhere('locale', $locale))
                    ->where('active', true)
                    ->where('status', data_get($filter, 'addon_status', Product::PUBLISHED)),
                'stocks.bonus' => fn($q) => $q->where('expired_at', '>', now())->where('status', true),
                'stocks.bonus.stock',
                'stocks.bonus.stock.stockExtras',
                'stocks.bonus.stock.countable:id,uuid,tax,status,shop_id,active,img,min_qty,max_qty,interval',
                'stocks.bonus.stock.countable.translation' => fn($q) => $q
                    ->where('locale', $this->language)
                    ->orWhere('locale', $locale)
                    ->select('id', 'product_id', 'title', 'locale'),
                'stocks.stockExtras.group.translation' => fn($q) => $q
                    ->where('locale', $this->language)
                    ->orWhere('locale', $locale),
            ];
        }

        return $product
            ->filter($filter)
            ->with($stocks + [
                    'discounts' => fn($q) => $q->where('start', '<=', today())->where('end', '>=', today())->where('active', 1),
                    'translation' => fn($q) => $q->where('locale', $this->language)->orWhere('locale', $locale),
                    'shop' => fn($q) => $q
                        ->select('id', 'status')
                        ->when(data_get($filter, 'shop_status'), function ($q, $status) {
                            $q->where('status', '=', $status);
                        }),
                    'shop.translation' => fn($q) => $q->where('locale', $this->language)
                        ->orWhere('locale', $locale)
                        ->select('id', 'locale', 'title', 'shop_id'),
                    'unit.translation' => fn($q) => $q->where('locale', $this->language)
                        ->orWhere('locale', $locale)
                        ->select('id', 'locale', 'title', 'unit_id'),
                    'reviews',
                    'translations',
                    'category' => fn($q) => $q->select('id', 'uuid'),
                    'category.translation' => fn($q) => $q->where('locale', $this->language)
                        ->orWhere('locale', $locale)
                        ->select('id', 'category_id', 'locale', 'title'),
                ])
            ->when(data_get($filter, 'rest'), function ($q) use ($locale) {
                $q->whereHas('translation', fn($q) => $q->where('locale', $this->language)->orWhere('locale', $locale));
            })
            ->paginate(data_get($filter, 'perPage', 10));
    }

    public function selectPaginate(array $filter): LengthAwarePaginator
    {
        /** @var Product $product */
        $product = $this->model();
        $locale  = data_get(Language::languagesList()->where('default', 1)->first(), 'locale');

        return $product
            ->filter($filter)
            ->with([
                'translation' => fn($q) => $q
                    ->select(['id', 'locale', 'title', 'product_id'])
                    ->where('locale', $this->language)
                    ->orWhere('locale', $locale),
                'unit.translation' => fn($q) => $q->where('locale', $this->language)
                    ->orWhere('locale', $locale)
                    ->select('id', 'locale', 'title', 'unit_id'),
            ])
            ->select([
                'id',
                'shop_id',
                'status',
                'active',
                'addon',
                'vegetarian',
            ])
            ->whereHas('stocks', fn($q) => $q->where('quantity', '>', 0))
            ->whereHas('translation', fn($q) => $q
                ->select(['id', 'locale', 'title', 'product_id'])
                ->where('locale', $this->language)
                ->orWhere('locale', $locale)
            )
            ->paginate(data_get($filter, 'perPage', 10));
    }

    public function productDetails(int $id)
    {
        $locale  = data_get(Language::languagesList()->where('default', 1)->first(), 'locale');

        return $this->model()
            ->whereHas('translation', fn($q) => $q->where('locale', $this->language))
            ->withAvg('reviews', 'rating')
            ->with([
                'stocks.addons',
                'stocks.addons.addon.stock',
                'stocks.addons.addon.translation' => fn($q) => $q
                    ->where('locale', $this->language)
                    ->orWhere('locale', $locale),
                'galleries' => fn($q) => $q->select('id', 'type', 'loadable_id', 'path', 'title'),
                'stocks.stockExtras.group.translation' => fn($q) => $q
                    ->where('locale', $this->language)
                    ->orWhere('locale', $locale),
                'discounts' => fn($q) => $q
                    ->where('start', '<=', today())
                    ->where('end', '>=', today())
                    ->where('active', 1),
                'translation' => fn($q) => $q->where('locale', $this->language)
                    ->orWhere('locale', $locale)
                    ->select('id', 'product_id', 'locale', 'title'),
                'category.translation' => fn($q) => $q->where('locale', $this->language)
                    ->orWhere('locale', $locale)
                    ->select('id', 'category_id', 'locale', 'title'),
                'brand' => fn($q) => $q->select('id', 'uuid', 'title'),
                'unit.translation' => fn($q) => $q->where('locale', $this->language)->orWhere('locale', $locale),
                'extras.translation' => fn($q) => $q->where('locale', $this->language)->orWhere('locale', $locale),
                'tags.translation' => fn($q) => $q->select('id', 'category_id', 'locale', 'title')
                    ->where('locale', $this->language)
                    ->orWhere('locale', $locale),
            ])
            ->find($id);
    }

    public function productByUUID(string $uuid): ?Product
    {
        /** @var Product $product */
        $product = $this->model();
        $locale  = data_get(Language::languagesList()->where('default', 1)->first(), 'locale');

        return $product
            ->withAvg('reviews', 'rating')
            ->withCount('reviews')
            ->with([
                'galleries' => fn($q) => $q->select('id', 'type', 'loadable_id', 'path', 'title'),
                'properties' => fn($q) => $q->where('locale', $this->language),
                'stocks.stockExtras.group.translation' => fn($q) => $q->where('locale', $this->language)
                    ->orWhere('locale', $locale),
                'stocks.addons.addon' => fn($q) => $q->where('active', true)
                    ->where('addon', true)
                    ->where('status', Product::PUBLISHED),
                'stocks.addons.addon.stock',
                'stocks.addons.addon.translation' => fn($q) => $q->where('locale', $this->language)
                    ->orWhere('locale', $locale),
                'discounts' => fn($q) => $q->where('start', '<=', today())->where('end', '>=', today())
                    ->where('active', 1),
                'shop.translation' => fn($q) => $q->where('locale', $this->language)->orWhere('locale', $locale),
                'category' => fn($q) => $q->select('id', 'uuid'),
                'category.translation' => fn($q) => $q->where('locale', $this->language)
                    ->orWhere('locale', $locale)
                    ->select('id', 'category_id', 'locale', 'title'),
                'brand' => fn($q) => $q->select('id', 'uuid', 'title'),
                'unit.translation' => fn($q) => $q->where('locale', $this->language)->orWhere('locale', $locale),
                'reviews.galleries',
                'reviews.user',
                'translation' => fn($q) => $q->where('locale', $this->language)->orWhere('locale', $locale),
                'tags.translation' => fn($q) => $q->select('id', 'category_id', 'locale', 'title')
                    ->where('locale', $this->language)->orWhere('locale', $locale),
            ])
            ->firstWhere('uuid', $uuid);
    }

    public function productsByIDs(array $filter = [])
    {
        $locale  = data_get(Language::languagesList()->where('default', 1)->first(), 'locale');

        return $this->model()
            ->filter($filter)
            ->with([
                'unit.translation' => fn($q) => $q->where('locale', $this->language)
                    ->orWhere('locale', $locale)
                    ->select('id', 'locale', 'title', 'unit_id'),
                'stocks.addons',
                'stocks.addons.addon.stock',
                'stocks.addons.addon.translation' => fn($q) => $q->where('locale', $this->language)
                    ->orWhere('locale', $locale),
                'stocks.stockExtras.group.translation' => fn($q) => $q->where('locale', $this->language)
                    ->orWhere('locale', $locale),
                'discounts' => fn($q) => $q->where('start', '<=', today())->where('end', '>=', today())
                    ->where('active', 1),
                'translation' => fn($q) => $q->where('locale', $this->language)->orWhere('locale', $locale)
                    ->select('id', 'product_id', 'locale', 'title'),
                'tags.translation' => fn($q) => $q->select('id', 'category_id', 'locale', 'title')
                    ->where('locale', $this->language)->orWhere('locale', $locale),
            ])
            ->whereHas('shop', function ($item) {
                $item->whereNull('deleted_at');
            })
            ->find(data_get($filter, 'products', []));
    }

    /**
     * @param array $filter
     * @return mixed
     */
    public function productsSearch(array $filter = []): mixed
    {
        $locale  = data_get(Language::languagesList()->where('default', 1)->first(), 'locale');

        return $this->model()
            ->filter($filter)
            ->with([
                'stocks' => fn($q) => $q->select([
                    'id',
                    'countable_type',
                    'countable_id',
                    'price',
                    'quantity',
                ]),
                'stocks.stockExtras.group.translation' => fn($q) => $q
                    ->where('locale', $this->language)
                    ->orWhere('locale', $locale),
                'translation' => fn($q) => $q->select([
                    'id',
                    'product_id',
                    'locale',
                    'title',
                ])
                    ->where('locale', $this->language)
                    ->orWhere('locale', $locale),
                'unit.translation' => fn($q) => $q
                    ->where('locale', $this->language)
                    ->orWhere('locale', $locale)
                    ->select('id', 'locale', 'title', 'unit_id'),
                'shop:id,status,uuid,user_id,logo_img,background_img',
            ])
            ->whereHas('shop', fn ($query) => $query->filter(['open' => 1, 'status' => 'approved', 'address' => data_get($filter, 'address', [])]))
            ->whereHas('stocks', fn($q) => $q->where('quantity', '>', 0))
            ->latest()
            ->select([
                'id',
                'img',
                'shop_id',
                'uuid',
                'status',
                'active',
            ])
            ->paginate(data_get($filter, 'perPage', 10));
    }

    /**
     * @param array $data
     * @return LengthAwarePaginator
     */
    public function selectStockPaginate(array $data): LengthAwarePaginator
    {
        $locale  = data_get(Language::languagesList()->where('default', 1)->first(), 'locale');

        return Stock::with([
            'stockExtras.group.translation' => fn($q) => $q
                ->where('locale', $this->language)
                ->orWhere('locale', $locale),
            'countable' => fn($q) => $q->select(['id', 'shop_id']),
            'countable.translation' => fn($q) => $q->select('id', 'product_id', 'locale', 'title')
                ->orWhere('locale', $locale)
                ->where('locale', $this->language),
        ])
            ->when(isset($data['addon']), fn($query) => $query->whereAddon($data['addon']),
                fn($query) => $query->whereAddon(0)
            )
            ->whereHas('countable', fn($q) => $q
				->whereHas('translation', fn($q) => $q->where('locale', $this->language)->orWhere('locale', $locale))
				->where('shop_id', data_get($data, 'shop_id') )
                ->when(isset($data['active']), fn($q) => $q->where('active', $data['active']))
                ->when(data_get($data, 'status'), fn($q, $status) => $q->where('status', $status))
                ->when(data_get($data, 'search'), function ($q, $s) {

                    $q->where(function ($query) use ($s) {
                        $query->where('keywords', 'LIKE', "%$s%")
                            ->orWhereHas('translation', function ($q) use ($s) {
                                $q->where('title', 'LIKE', "%$s%")
                                    ->select('id', 'product_id', 'locale', 'title');
                            });
                    });

                })
            )
            ->where('quantity', '>', 0)
            ->paginate(data_get($data, 'perPage', 10));
    }

    /**
     * @param array $filter
     * @return array
     */
    public function reportChart(array $filter): array
    {
        $dateFrom   = date('Y-m-d 00:00:01', strtotime(data_get($filter, 'date_from')));
        $dateTo     = date('Y-m-d 23:59:59', strtotime(data_get($filter, 'date_to', now())));
        $type       = data_get($filter, 'type');
        $chart      = data_get($filter, 'chart');

        $orders = Order::filter($filter)
			->where('status', Order::STATUS_DELIVERED)
			->whereDate('created_at', '>=', $dateFrom)
			->whereDate('created_at', '<=', $dateTo)
            ->select([
                DB::raw("(DATE_FORMAT(created_at, " . ($type == 'year' ? "'%Y" : ($type == 'month' ? "'%Y-%m" : "'%Y-%m-%d")) . "')) as time"),
                DB::raw('count(id) as count'),
                DB::raw('sum(total_price) as price'),
            ])
            ->withSum('orderDetails', 'quantity')
            ->groupBy(['time', 'order_details_sum_quantity'])
            ->get();

        $result = [];

        foreach ($orders as $order) {

            if (data_get($result, data_get($order, 'time'))) {
                $result[data_get($order, 'time')]['count'] += data_get($order, 'count', 0);
                $result[data_get($order, 'time')]['price'] += data_get($order, 'price', 0);
                $result[data_get($order, 'time')]['quantity'] += data_get($order, 'order_details_sum_quantity', 0);
                continue;
            }

            $result[data_get($order, 'time')] = [
                'time'      => data_get($order, 'time'),
                'count'     => data_get($order, 'count', 0),
                'price'     => data_get($order, 'price', 0),
                'quantity'  => data_get($order, 'order_details_sum_quantity', 0),
            ];

        }

        $result = collect(array_values($result));

        return [
            'chart'     => ChartRepository::chart($result, $chart),
            'count'     => $result->sum('count'),
            'price'     => $result->sum('price'),
            'quantity'  => $result->sum('quantity'),
        ];
    }

    /**
     * @param array $filter
     * @return array
     */
    public function productReportPaginate(array $filter): array
    {
        try {

            $dateFrom   = date('Y-m-d 00:00:01', strtotime(data_get($filter, 'date_from')));
            $dateTo     = date('Y-m-d 23:59:59', strtotime(data_get($filter, 'date_to', now())));
            $default    = data_get(Language::where('default', 1)->first(['locale', 'default']), 'locale');
            $key        = data_get($filter, 'column', 'id');
            $column     = data_get([
                'id',
                'interval',
                'category_id',
                'active',
                'shop_id',
                'deleted_at',
            ], $key, $key);

            $data = Product::withTrashed()
                ->with([

                    'translation' => fn($q) => $q->withTrashed()
                        ->select('id', 'product_id', 'locale', 'title', 'deleted_at')
                        ->where('locale', $this->language)
                        ->orWhere('locale', $default),

                    'category' => fn($q) => $q->withTrashed()->select('id', 'deleted_at'),

                    'category.translation'  => fn($q) => $q->withTrashed()
                        ->where('locale', $this->language)
                        ->orWhere('locale', $default)
                        ->select('id', 'category_id', 'locale', 'title', 'deleted_at'),

                    'stocks' => fn($q) => $q->withTrashed(),

                    'stocks.orderDetails' => fn($q) => $q->select('id', 'order_id', 'stock_id', 'total_price', 'tax', 'quantity'),

                    'stocks.orderDetails.order' => fn($q) => $q->select('id', 'total_price', 'tax', 'shop_id', 'status', 'created_at')
                        ->when(data_get($filter, 'shop_id'), fn($q, $shopId) => $q->where('shop_id', $shopId))
						->whereDate('created_at', '>=', $dateFrom)
						->whereDate('created_at', '<=', $dateTo)
                        ->where('status', Order::STATUS_DELIVERED),

                    'stocks.stockExtras' => fn($q) => $q->withTrashed(),
                    'stocks.stockExtras.group' => fn($q) => $q->withTrashed(),
                    'stocks.stockExtras.group.translation' => fn($q) => $q->withTrashed()
                        ->where('locale', $this->language)
                        ->orWhere('locale', $default),

                ])
                ->when(data_get($filter, 'shop_id'), fn($q, $shopId) => $q->where('shop_id', $shopId))
                ->when(is_array(data_get($filter, 'categories')), function (Builder $query) use($filter) {
                    $query->whereIn('category_id', data_get($filter, 'categories'));
                })
                ->when(is_array(data_get($filter, 'products')), function (Builder $query) use($filter) {
                    $query->whereIn('id', data_get($filter, 'products'));
                })
                ->when(data_get($filter, 'search'), function ($q, $search) {
                    $q->where(function ($query) use ($search) {
                        $query
                            ->where('keywords', 'LIKE', "%$search%")
                            ->orWhere('id', 'LIKE', "%$search%")
                            ->orWhere('uuid', 'LIKE', "%$search%")
                            ->orWhereHas('translation', function ($q) use($search) {
                                $q->where('title', 'LIKE', "%$search%")
                                    ->select('id', 'product_id', 'locale', 'title');
                            });
                    });
                })
                ->whereHas('stocks', function ($query) use ($filter, $dateTo, $dateFrom) {
                    $query
                        ->withTrashed()
                        ->whereHas('orderDetails', function ($q) use ($filter, $dateTo, $dateFrom) {
                            $q
                                ->whereHas('order', function ($o) use ($filter, $dateTo, $dateFrom) {
                                    $o
                                        ->when(data_get($filter, 'shop_id'), fn($q, $shopId) => $q->where('shop_id', $shopId))
										->whereDate('created_at', '>=', $dateFrom)
										->whereDate('created_at', '<=', $dateTo)
                                        ->where('status', Order::STATUS_DELIVERED);
                                });
                        });
                })
                ->select([
					'id',
					'keywords',
					'uuid',
					'category_id',
					'active',
					'shop_id',
					'deleted_at',
                    'interval',
                ])
                ->orderBy($column, data_get($filter, 'sort', 'desc'));

            if (data_get($filter, 'export') === 'excel') {

                $name = 'products-report-' . Str::random(8);

                $result = ProductReportResource::collection($data->get())->toArray(request());

                Excel::store(new ProductReportExport($result), "export/$name.xlsx",'public');

                return [
                    'status' => true,
                    'code'   => ResponseError::NO_ERROR,
                    'data'   => [
                        'path'      => 'public/export',
                        'file_name' => "export/$name.xlsx",
                        'link'      => URL::to("storage/export/$name.xlsx"),
                    ]
                ];

            }

            $data = $data->paginate(data_get($filter, 'perPage', 10));

            $result = [];

            foreach ($data as $item) {

                $result[$item->id] = [
                    'id'            => $item->id,
                    'active'        => $item->active,
                    'category_id'   => $item->category_id,
                    'count'         => 0,
                    'price'         => 0,
                    'quantity'      => 0,
                    'shop_id'       => $item->shop_id,
                    'translation' => [
                        'id'     => $item?->translation?->id,
                        'locale' => $item?->translation?->locale,
                        'title'  => $item?->translation?->title,
                    ],
                    'category' => $item->category,
                    'stocks' => [],
                ];

                foreach ($item->stocks as $stock) {

                    /** @var Stock $stock */

                    $count = $stock->orderDetails
                        ->when(request('shop_id'), fn($q, $shopId) => $q->where('order.shop_id', $shopId))
                        ->where('order.status', Order::STATUS_DELIVERED)
						->where('order.created_at', '>=', $dateFrom)
						->where('order.created_at', '<=', $dateTo)
                        ->groupBy('order_id')
                        ->count();

                    $orderQuantity = $stock->orderDetails
                        ->when(request('shop_id'), fn($q, $shopId) => $q->where('order.shop_id', $shopId))
                        ->where('order.status', Order::STATUS_DELIVERED)
						->where('order.created_at', '>=', $dateFrom)
						->where('order.created_at', '<=', $dateTo)
                        ->sum('quantity');

                    $price = $stock->orderDetails
                        ->when(request('shop_id'), fn($q, $shopId) => $q->where('order.shop_id', $shopId))
                        ->where('order.status', Order::STATUS_DELIVERED)
						->where('order.created_at', '>=', $dateFrom)
						->where('order.created_at', '<=', $dateTo)
                        ->groupBy('order_id')
                        ->reduce(fn($carry, $item) => $carry + $item->groupBy('order_id')->reduce(fn($c, $i) => $c + $i->sum('order.total_price')));

                    $quantity = $stock->quantity ?? 0;

                    $result[$item->id]['stocks'][] = [
                        'count'          => $count,
                        'order_quantity' => $orderQuantity,
                        'price'          => $price,
                        'quantity'       => $quantity,
                        'extras'         => $stock->stockExtras
                    ];

                    $result[$item->id]['count']    += $count;
                    $result[$item->id]['price']    += $price;
                    $result[$item->id]['quantity'] += $orderQuantity;
                }

            }

            return [
                'status' => true,
                'code'   => ResponseError::NO_ERROR,
                'data'   => [
                    'data'   => collect($result)->values(),
                    'meta'   => [
                        'last_page'     => $data->lastPage(),
                        'page'          => $data->currentPage(),
                        'total'         => $data->total(),
                        'more_pages'    => $data->hasMorePages(),
                        'has_pages'     => $data->hasPages(),
                    ]
                ],
            ];

        } catch (Throwable $e) {
            return [
                'status'    => false,
                'code'      => ResponseError::ERROR_400,
                'message'   => $e->getMessage()
            ];
        }
    }

    /**
     * @param array $filter
     * @return array|LengthAwarePaginator
     * @throws Exception
     * @throws \PhpOffice\PhpSpreadsheet\Writer\Exception
     */
    public function stockReportPaginate(array $filter): LengthAwarePaginator|array
    {
		$locale = data_get(Language::languagesList()->where('default', 1)->first(), 'locale');

		$query = Product::filter($filter)
            ->with([
                'translation' => fn($q) => $q
					->where('locale', $this->language)
					->orWhere('locale', $locale)
                    ->select('id', 'product_id', 'locale', 'title'),
            ])
			->when(is_array(data_get($filter, 'products')), function (Builder $query) use($filter) {
                $query->whereIn('id', data_get($filter, 'products'));
            })
            ->when(is_array(data_get($filter, 'categories')), function (Builder $query) use($filter) {
                $query->whereIn('category_id', data_get($filter, 'categories'));
            })
			->where('addon', false)
            ->select([
                'id',
                'category_id',
                'status',
                'shop_id',
                'addon',
                'interval',
                'keywords'
            ])
            ->withSum('stocks', 'quantity')
            ->orderBy(data_get($filter, 'column', 'id'), data_get($filter, 'sort', 'desc'));

        if (data_get($filter, 'export') === 'excel') {

            $name = 'stocks-report-' . Str::random(8);

            Excel::store(new StockReportExport($query->get()), "export/$name.xlsx",'public');
//            ExportJob::dispatchAfterResponse($name, $query->get(), StockReportExport::class);

            return [
                'path'      => 'public/export',
                'file_name' => "export/$name.xlsx",
                'link'      => URL::to("storage/export/$name.xlsx"),
            ];
        }

        return $query->paginate(data_get($filter, 'perPage', 10));
    }

    /**
     * @param array $filter
     * @return array|LengthAwarePaginator
     */
    public function extrasReportPaginate(array $filter): LengthAwarePaginator|array
    {

        if (data_get($filter, 'export') === 'excel') {

            $name = 'stocks-report-' . Str::random(8);

            ExportJob::dispatch("export/$name.xlsx", collect([]), StockExport::class);

            return [
                'path'      => 'public/export',
                'file_name' => "export/$name.xlsx",
                'link'      => URL::to("storage/export/$name.xlsx"),
            ];
        }


        $query = Stock::with([
            'countable:id,uuid,active,category_id,shop_id,interval',
            'countable.translation' => fn($q) => $q->where('locale', $this->language)
                ->select('id', 'product_id', 'locale', 'title'),
            'stockExtras.group.translation' => fn($q) => $q->where('locale', $this->language),
            'orderDetails:stock_id,order_id,id,quantity',
            'orderDetails.order:id,total_price',
        ])
            ->whereHas('countable', function (Builder $query) use ($filter) {

                if (data_get($filter, 'products')) {
                    $query->whereIn('id', data_get($filter, 'products'));
                }

                if (data_get($filter, 'categories')) {
                    $query->whereIn('category_id', data_get($filter, 'categories'));
                }

                if (!empty(data_get($filter, 'shop_id')) && is_int(data_get($filter, 'shop_id'))) {
                    $query->where('shop_id', data_get($filter, 'shop_id'));
                }

            })
            ->whereHas('stockExtras')
            ->select([
                'id',
                'countable_type',
                'countable_id',
                'price',
                'quantity',
            ])
            ->whereHas('orderDetails', fn($q) => $q->withSum('order', 'total_price'))
            ->withSum('orderDetails', 'quantity')
            ->orderBy(data_get($filter, 'column', 'id'), data_get($filter, 'sort', 'desc'));

        return $query->paginate(data_get($filter, 'perPage', 10));
    }

    /**
     * @param array $filter
     * @return LengthAwarePaginator
     */
    public function history(array $filter): LengthAwarePaginator
    {
        $agent = new Agent;

        $where = [
            'model_type' => Product::class,
            'device'     => $agent->device(),
            'ip'         => request()->ip(),
        ];

        if (data_get($filter, 'user_id')) {

            $where = [
                'model_type' => Product::class,
                'device'     => $agent->device(),
                'user_id'    => data_get($filter, 'user_id'),
            ];

        }

        return UserActivity::where($where)
            ->orderBy(data_get($filter, 'column', 'id'), data_get($filter, 'sort', 'desc'))
            ->paginate(data_get($filter, 'perPage', 10));
    }

    /**
     * @param array $filter
     * @return LengthAwarePaginator
     */
    public function mostPopulars(array $filter): LengthAwarePaginator
    {
        $locale     = data_get(Language::languagesList()->where('default', 1)->first(), 'locale');

        $filter['model_type'] = Product::class;

        if (data_get($filter, 'date_from')) {
            $filter['date_from']  = date('Y-m-d 00:00:01', strtotime(data_get($filter, 'date_from')));
        }

        if (data_get($filter, 'date_to')) {
            $filter['date_to']  = date('Y-m-d 00:00:01', strtotime(data_get($filter, 'date_to')));
        }

        return UserActivity::filter($filter)
            ->with([
                'model.translation' => fn($q) => $q->where('locale', $this->language)->orWhere('locale', $locale)
            ])
            ->select([
                'model_type',
                'model_id',
                DB::raw('count(model_id) as count'),
            ])
            ->groupBy('model_id', 'model_type')
            ->orderBy('count', 'desc')
            ->paginate(data_get($filter, 'perPage', 10));
    }

}

