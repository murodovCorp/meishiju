<?php

namespace App\Repositories\CategoryRepository;

use App\Exports\CategoryReportExport;
use App\Models\Category;
use App\Models\Currency;
use App\Models\Language;
use App\Models\Product;
use App\Models\Stock;
use App\Repositories\CoreRepository;
use App\Repositories\Interfaces\CategoryRepoInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Facades\Excel;
use Psr\SimpleCache\InvalidArgumentException;
use Throwable;

class CategoryRepository extends CoreRepository implements CategoryRepoInterface
{
    protected function getModelClass(): string
    {
        return Category::class;
    }

    /**
     * Get Parent, only categories where parent_id == 0
     */
    public function categories(array $filter = []): LengthAwarePaginator
    {
        /** @var Category $category */
        $category = $this->model();

        $locale = data_get(Language::languagesList()->where('default', 1)->first(), 'locale');

        return $category
            ->updatedDate($this->updatedDate)
            ->filter($filter)
            ->with([
                'translations',
                'translation' => fn($q) => $q->select('id', 'locale', 'title', 'category_id')
                    ->where('locale', $this->language)->orWhere('locale', $locale),
                'shop.translation' => fn($q) => $q->select('id', 'locale', 'title', 'shop_id')
                    ->where(function ($query) use($locale) {
						$query->where('locale', $this->language)->orWhere('locale', $locale);
					}),
            ])
            ->orderBy(data_get($filter, 'column', 'id'), data_get($filter, 'sort', 'desc'))
            ->paginate(data_get($filter, 'perPage', 10));
    }

    /**
     * Get Parent, only categories where parent_id == 0
     */
    public function parentCategories(array $filter = []): LengthAwarePaginator
    {
        /** @var Category $category */
        $category = $this->model();
		$locale = data_get(Language::languagesList()->where('default', 1)->first(), 'locale');

        return $category
            ->updatedDate($this->updatedDate)
            ->filter($filter)
			->withThreeChildren($filter + ['lang' => $this->language])
			->with([
				'translations',
				'shop.translation' => fn($q) => $q->select('id', 'locale', 'title', 'shop_id')
					->where('locale', $this->language)->orWhere('locale', $locale),
			])
            ->where(fn($q) => $q->where('parent_id', null)->orWhere('parent_id', 0))
            ->orderBy(data_get($filter, 'column', 'id'), data_get($filter, 'sort', 'desc'))
            ->paginate(data_get($filter, 'perPage', 10));
    }

    /**
     * @param int $id
     * @return Category|null
     */
    public function childrenCategory(int $id): ?Category
    {
        /** @var Category $category */
        $category = $this->model();

        return $category
            ->withSecondChildren(['lang' => $this->language])
            ->find($id);
    }

    /**
     * @param array $filter
     * @return LengthAwarePaginator
     */
    public function shopCategory(array $filter = []): LengthAwarePaginator
    {
        /** @var Category $category */
        $category = $this->model();
        $locale = data_get(Language::languagesList()->where('default', 1)->first(), 'locale');

        return $category
            ->filter($filter)
            ->with([
				'shop.translation' => fn($q) => $q->select('id', 'locale', 'title', 'shop_id')
					->where('locale', $this->language)->orWhere('locale', $locale),

                'translation' => fn($q) => $q->where('locale', $this->language)->orWhere('locale', $locale)
            ])
            ->orderBy(data_get($filter, 'column', 'id'), data_get($filter, 'sort', 'desc'))
            ->paginate(data_get($filter, 'perPage', 10));
    }

    /**
     * @param array $filter
     * @return LengthAwarePaginator
     */
    public function shopCategoryPaginate(array $filter = []): LengthAwarePaginator
    {
        /** @var Category $category */
        $category = $this->model();
        $locale = data_get(Language::languagesList()->where('default', 1)->first(), 'locale');

        return $category
            ->filter($filter)
            ->with([
				'shop.translation' => fn($q) => $q->select('id', 'locale', 'title', 'shop_id')
					->where('locale', $this->language)->orWhere('locale', $locale),

                'translation' => fn($q) => $q->where('locale', $this->language)->orWhere('locale', $locale)
            ])
            ->orderBy(data_get($filter, 'column', 'id'), data_get($filter, 'sort', 'desc'))
            ->paginate(data_get($filter, 'perPage', 10));
    }

    /**
     * Get Parent, only categories where parent_id == 0
     */
    public function selectPaginate(array $filter = []): LengthAwarePaginator
    {
        /** @var Category $category */
        $category = $this->model();

        return $category
            ->filter($filter)
            ->withThreeChildren($filter)
            ->select(['id', 'parent_id', 'keywords', 'type', 'active', 'input'])
            ->orderBy(data_get($filter, 'column', 'id'), data_get($filter, 'sort', 'desc'))
            ->paginate(data_get($filter, 'perPage', 10));
    }

    /**
     * Get Parent, only categories where parent_id == 0
     */
    public function mySelectPaginate(array $filter = []): LengthAwarePaginator
    {
        /** @var Category $category */
        $category = $this->model();

		$shopId = data_get($filter, 'shop_id');

		unset($filter['shop_id']);

        return $category
			->whereHas('shopCategory', fn($q) => $q->where('shop_id', $shopId))
            ->filter($filter)
            ->withParent($filter)
            ->select(['id', 'parent_id', 'keywords', 'type', 'active', 'input'])
            ->orderBy(data_get($filter, 'column', 'id'), data_get($filter, 'sort', 'desc'))
            ->paginate(data_get($filter, 'perPage', 10));
    }

    /**
     * Get categories with pagination
     */
    public function categoriesPaginate(array $filter = []): LengthAwarePaginator
    {
        /** @var Category $category */
        $category = $this->model();

        return $category
            ->filter($filter)
            ->updatedDate($this->updatedDate)
            ->withSecondChildren(['lang' => $this->language])
            ->orderBy(data_get($filter, 'column', 'id'), data_get($filter, 'sort', 'desc'))
            ->paginate(data_get($filter, 'perPage', 10));
    }

    /**
     * Get all categories list
     */
    public function categoriesList(array $filter = []): array|Collection|\Illuminate\Support\Collection
    {
        /** @var Category $category */
        $category = $this->model();

        return $category
            ->updatedDate($this->updatedDate)
            ->withParent(['lang' => $this->language])
            ->orderBy(data_get($filter, 'column', 'id'), data_get($filter, 'sort', 'desc'))
            ->get();
    }

    /**
     * Get one category by Identification number
     */
    public function categoryDetails(int $id): Category|null
    {
        /** @var Category $category */
        $category = $this->model();

        return $category
            ->withParent(['lang' => $this->language])
            ->find($id);
    }


    /**
     * Get one category by slug
     * @param $uuid
     * @return Category|null
     */
    public function categoryByUuid($uuid): ?Category
    {
        /** @var Category $category */
        $category = $this->model();

        return $category
            ->where('uuid', $uuid)
            ->withThreeChildren(['lang' => $this->language])
            ->withCount(['products', 'stocks'])
            ->first();
    }


    /**
     * @param array $filter
     * @return LengthAwarePaginator
     */
    public function categoriesSearch(array $filter = []): LengthAwarePaginator
    {
        /** @var Category $category */
        $category = $this->model();
        $locale = data_get(Language::languagesList()->where('default', 1)->first(), 'locale');

        return $category
            ->filter($filter)
            ->with([
				'shop.translation' => fn($q) => $q->select('id', 'locale', 'title', 'shop_id')
					->where('locale', $this->language)->orWhere('locale', $locale),

                'translation' => fn($q) => $q->select('id', 'locale', 'title', 'category_id')
                    ->where('locale', $this->language)->orWhere('locale', $locale)
            ])
            ->latest()
            ->paginate(data_get($filter, 'perPage', 10));
    }

    /**
     * @param int $id
     * @param int $shopId
     * @return Builder|array|Collection|Model|null
     */
    public function shopCategoryById(int $id, int $shopId): Builder|array|Collection|Model|null
    {
        /** @var Category $category */
        $category = $this->model();

        return $category
            ->with('translations')
            ->whereHas('shopCategory', fn($q) => $q->where('shop_id', $shopId))
            ->orderByDesc('id')
            ->find($id);
    }

    /**
     * @param array $filter
     * @return LengthAwarePaginator
     */
    public function shopCategoryProduct(array $filter = []): LengthAwarePaginator
    {
        /** @var Category $categories */

        $categories = $this->model();

        $page = data_get($filter, 'page') ?: Paginator::resolveCurrentPage('links');

        $perPage = data_get($filter, 'perPage', 10) ?: $categories->getPerPage();
        $locale = data_get(Language::languagesList()->where('default', 1)->first(), 'locale');

        $categories = $categories
            ->with([
				'shop.translation' => fn($q) => $q->select('id', 'locale', 'title', 'shop_id')
					->where('locale', $this->language)->orWhere('locale', $locale),

                'translation' => fn($q) => $q->select('id', 'locale', 'title', 'category_id')
                    ->where('locale', $this->language)->orWhere('locale', $locale),
                'stocks.countable'
            ])
            ->skip(($page - 1) * $perPage)
            ->take($perPage + 1)
            ->select([
                'id',
                'uuid',
                'shop_id',
                'keywords',
                'type',
                'img',
                'active',
                'input',
            ])
            ->get()
            ->map(function (Category $category) use ($filter) {

                $stocks = $category->stocks
                    ->when(data_get($filter, 'shop_id'), fn($q, $shopId) => $q
                        ->where('countable.shop_id', '=', $shopId)
                        ->where('status', Product::PUBLISHED)
                    )
                    ->take(10);

                if (count($stocks) > 0) {
                    return $category->setRelation('stocks', $stocks);
                }

                return false;
            })->reject(function ($value) {
                return empty($value->stocks);
            });

        return new LengthAwarePaginator(
            $categories,
            $categories->count(),
            $perPage,
            data_get($filter, 'page', 1),
            [
                'path'      => LengthAwarePaginator::resolveCurrentPath(),
                'pageName'  => 'links',
            ]
        );
    }

    /**
     * @param array $filter
     * @return LengthAwarePaginator
     */
    public function shopCategoryNonExistPaginate(array $filter = []): LengthAwarePaginator
    {
        $filter['active'] = 1;

        /** @var Category $category */
        $category = $this->model();
        $locale = data_get(Language::languagesList()->where('default', 1)->first(), 'locale');

        unset($filter['shop_id']);

        return $category
            ->withThreeChildren($filter + ['lang' => $this->language])
            ->filter($filter)
            ->with([
				'shop.translation' => fn($q) => $q->select('id', 'locale', 'title', 'shop_id')
					->where('locale', $this->language)->orWhere('locale', $locale),

                'translation' => fn($q) => $q->select('id', 'locale', 'title', 'category_id')
                    ->where('locale', $this->language)->orWhere('locale', $locale),
            ])
            ->select([
				'id',
				'uuid',
				'shop_id',
				'keywords',
				'type',
				'img',
				'active',
				'input',
            ])
            ->whereDoesntHave('shopCategory', fn($q) => $q->where('shop_id', data_get($filter, 'shop_id')))
            ->orderByDesc('id')
            ->paginate(data_get($filter, 'perPage', 10));
    }

    /**
     * @param array $filter
     * @return array
     */
    public function reportChart(array $filter = []): array
    {
        $cacheFrom = date('Y-m', strtotime(data_get($filter, 'date_from')));
        $cacheTo   = date('Y-m', strtotime(data_get($filter, 'date_to', now())));
        $dateFrom  = date('Y-m-d 00:00:01', strtotime(data_get($filter, 'date_from')));
        $dateTo    = date('Y-m-d 23:59:59', strtotime(data_get($filter, 'date_to', now())));
        $type      = data_get($filter, 'type');
        $search    = data_get($filter, 'search');
        $chartType = data_get($filter, 'chart', 'count');

        try {
            Cache::delete("category-report-chart-$cacheFrom-$cacheTo");
        } catch (InvalidArgumentException) {
        }

        $paginate = Cache::remember("category-report-chart-$cacheFrom-$cacheTo-$search", 86400, function () use ($dateFrom, $dateTo, $search) {

            $language = data_get(Language::languagesList()->where('default', 1)->first(), 'locale');

            $categories = Category::with([
                'translation' => fn($q) => $q->where('locale', $this->language)
                    ->orWhere('locale', $language)
                    ->select('id', 'locale', 'title', 'category_id'),

                'products' => fn($q) => $q->select('id', 'category_id'),

                'products.stocks' => fn($q) => $q->select('id', 'countable_type', 'countable_id'),

                'products.stocks.orderDetails' => fn($q) => $q
                    ->select('id', 'order_id', 'stock_id', 'quantity', 'created_at'),

                'products.stocks.orderDetails.order' => fn($q) => $q->select('id', 'total_price'),

                'children' => fn($q) => $q->select(['id', 'parent_id', 'type']),

                'children.translation' => fn($q) => $q->where('locale', $this->language)
                    ->orWhere('locale', $language)
                    ->select('id', 'locale', 'title', 'category_id'),

                'children.children'=> fn($q) => $q->select(['id', 'parent_id', 'type']),

                'children.children.translation' => fn($q) => $q->where('locale', $this->language)
                    ->orWhere('locale', $language)
                    ->select('id', 'locale', 'title', 'category_id'),

                'children.children.children'=> fn($q) => $q->select(['id', 'parent_id', 'type']),

                'children.children.children.translation' => fn($q) => $q->where('locale', $this->language)
                    ->orWhere('locale', $language)
                    ->select('id', 'locale', 'title', 'category_id'),

                'children.products' => fn($q) => $q->select('id', 'category_id'),

                'children.products.stocks' => fn($q) => $q->select('id', 'countable_type', 'countable_id'),

                'children.products.stocks.orderDetails' => fn($q) => $q
                    ->select('id', 'order_id', 'stock_id', 'quantity', 'created_at'),

                'children.products.stocks.orderDetails.order' => fn($q) => $q->select('id', 'total_price'),

                'children.children.products' => fn($q) => $q->select('id', 'category_id'),

                'children.children.products.stocks' => fn($q) => $q->select('id', 'countable_type', 'countable_id'),

                'children.children.products.stocks.orderDetails' => fn($q) => $q
                    ->select('id', 'order_id', 'stock_id', 'quantity', 'created_at'),

                'children.children.products.stocks.orderDetails.order' => fn($q) => $q->select('id', 'total_price'),

                'children.children.children.products' => fn($q) => $q->select('id', 'category_id'),

                'children.children.children.products.stocks' => fn($q) => $q->select('id', 'countable_type', 'countable_id'),

                'children.children.children.products.stocks.orderDetails' => fn($q) => $q
                    ->select('id', 'order_id', 'stock_id', 'quantity', 'created_at'),

                'children.children.children.products.stocks.orderDetails.order' => fn($q) => $q->select('id', 'total_price'),
            ])
                ->where(function ($q) use ($search, $language) {
                    $q->whereHas('translation', fn($query) => $query
                        ->where(function ($q) use($language) {
                            $q->where('locale', $this->language)->orWhere('locale', $language);
                        })
                        ->where('title', 'like', "%$search%")
                    )->orWhereHas('children.translation', fn($query) => $query
                        ->where(function ($q) use($language) {
                            $q->where('locale', $this->language)->orWhere('locale', $language);
                        })
                        ->where('title', 'like', "%$search%")
                    )->orWhereHas('children.children.translation', fn($query) => $query
                        ->where(function ($q) use($language) {
                            $q->where('locale', $this->language)->orWhere('locale', $language);
                        })
                        ->where('title', 'like', "%$search%")
                    )->orWhereHas('children.children.children.translation', fn($query) => $query
                        ->where(function ($q) use($language) {
                            $q->where('locale', $this->language)->orWhere('locale', $language);
                        })
                        ->where('title', 'like', "%$search%")
                    );
                })
                ->where([
                    ['type', Category::MAIN],
                    ['parent_id', 0],
                ])
                ->where('created_at', '>=', $dateFrom)
                ->where('created_at', '<=', $dateTo)
                ->select([
                    'id',
                    'parent_id',
                    'type',
                    'created_at',
                ])
                ->get();

            $paginate = [];

            foreach ($categories as $category) {

                $key = $category->id;

                if (!data_get($paginate, $key)) {

                    $paginate[$key] = [
                        'id'                => $category->id,
                        'created_at'        => date('Y-m-d', strtotime($category->created_at)),
                        'title'             => data_get($category->translation, 'title'),
                        'quantity'          => 0,
                        'price'             => 0,
                        'products_count'    => 0,
                        'count'             => 0,
                    ];

                }

                $this->reportPaginateData($category, $key, $paginate);

                foreach ($category->children as $children) {

                    $this->reportPaginateData($children, $key, $paginate);

                    foreach ($children->children as $child) {

                        $this->reportPaginateData($child, $key, $paginate);

                    }

                }

            }

            return $paginate;
        });

        $paginate = collect(array_values($paginate))
            ->where('created_at', '>=', $dateFrom)
            ->where('created_at', '<=', $dateTo);

        $chart = [];

        foreach ($paginate as $item) {

            $time = data_get($item, 'created_at');

            if ($type === 'year') {
                $time = date('Y', strtotime($time));
            } else if ($time === 'month') {
                $time = date('Y-m', strtotime($time));
            }

            if (empty($time)) {
                continue;
            }

            if (!data_get($chart, $time)) {

                $chart[$time] = [
                    'time'      => $time,
                    $chartType  => data_get($item, $chartType)
                ];

                continue;
            }

            $value = data_get($chart, "$time.$chartType") + data_get($item, $chartType);

            $chart[$time] = [
                'time'      => $time,
                $chartType  => $value
            ];

        }

        if (data_get($filter, 'export') === 'excel') {
            try {
                $name = 'categories-report-' . Str::random(8);

                Excel::store(new CategoryReportExport($paginate), "export/$name.xlsx",'public');

                return [
                    'path'      => 'public/export',
                    'file_name' => "export/$name.xlsx",
                    'link'      => URL::to("storage/export/$name.xlsx"),
                ];
            } catch (Throwable $e) {
                $this->error($e);
                return [
                    'status' => false,
                    'message' => 'Cant export category. ' . $e->getMessage()
                ];
            }
        }

        return [
            'paginate'              => $paginate,
            'chart'                 => array_values($chart),
            'currency'              => Currency::currenciesList()->where('id', $this->currency)->first(),
            'total_quantity'        => $paginate->sum('quantity'),
            'total_price'           => $paginate->sum('price'),
            'total_count'           => $paginate->sum('count'),
            'total_products_count'  => $paginate->sum('products_count'),
        ];
    }

    /**
     * @param Category|null $category
     * @param $key
     * @param $paginate
     * @return void
     */
    private function reportPaginateData(?Category $category, $key, &$paginate): void
    {
        if (empty($category)) {
            return;
        }

        $title          = data_get($paginate, "$key.title", '');

        $categoryTitle  = $category->translation?->title;

        if (!empty($categoryTitle)) {
            data_set($paginate, "$key.title", "$title -> $categoryTitle");
        }

        $stockCount = data_get($paginate, "$key.products_count", 0);

        $products = $category->products->whereNull('deleted_at');

        data_set($paginate, "$key.products_count", $stockCount + $products->count());

        foreach ($products as $product) {

            /** @var Product $product */

            foreach ($product->stocks->whereNull('deleted_at') as $stock) {

                $quantity       = data_get($paginate, "$key.quantity", 0);
                $price          = data_get($paginate, "$key.price", 0);
                $count          = data_get($paginate, "$key.count", 0);

                /** @var Stock $stock */
                $orderDetails   = $stock->orderDetails;

                if ($orderDetails->count() === 0) {
                    continue;
                }

                $firstCreatedAt = data_get($paginate, "$key.created_at");

                $createdAt      = date('Y-m-d', strtotime($orderDetails->min('created_at')));

                if($createdAt < $firstCreatedAt) {
                    data_set($paginatem, "$key.created_at", $createdAt);
                }

                $sumQuantity    = $orderDetails->sum('quantity');
                $sumPrice       = $orderDetails->sum('order.total_price');
                $sumCount       = $orderDetails->groupBy('order_id')->count();

                data_set($paginate, "$key.count", $count + $sumCount);
                data_set($paginate, "$key.price", $price + $sumPrice);
                data_set($paginate, "$key.quantity", $quantity + $sumQuantity);

            }

        }

    }
}
