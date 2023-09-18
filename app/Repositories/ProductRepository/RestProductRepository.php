<?php

namespace App\Repositories\ProductRepository;

use App\Models\Language;
use App\Models\Product;
use App\Repositories\CoreRepository;
use Illuminate\Support\Facades\Cache;
use DB;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;

class RestProductRepository extends CoreRepository
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

        return $product
            ->filter($filter)
            ->updatedDate($this->updatedDate)
            ->with([
                'stocks' => fn($q) => $q->where('addon', false)->where('quantity', '>', 0),
                'stocks.addons.addon' => fn($query) => $query->with([
                    'stock'         => fn($q) => $q->where('addon', true)->where('quantity', '>', 0),
                    'translation'   => fn($q) => $q->where('locale', $this->language)->orWhere('locale', $locale),
                ])
                    ->whereHas('stock', fn($q) => $q->where('addon', true)->where('quantity', '>', 0))
                    ->whereHas('translation', fn($q) => $q->where('locale', $this->language)->orWhere('locale', $locale))
                    ->where('active', true)
                    ->where('status', data_get($filter, 'addon_status', Product::PUBLISHED)),
                'stocks.bonus' => fn($q) => $q->where('expired_at', '>', now())->where('status', true)->select([
                    'id', 'expired_at', 'bonusable_type', 'bonusable_id',
                    'bonus_quantity', 'value', 'type', 'status'
                ]),
                'stocks.bonus.stock',
                'stocks.bonus.stock.stockExtras',
                'stocks.bonus.stock.countable:id,uuid,tax,status,active,img,min_qty,max_qty,interval',
                'stocks.bonus.stock.countable.translation' => fn($q) => $q
                    ->select('id', 'product_id', 'title', 'locale')
                    ->where('locale', $this->language)
                    ->orWhere('locale', $locale),
                'stocks.stockExtras.group.translation' => fn($q) => $q
                    ->where('locale', $this->language)
                    ->orWhere('locale', $locale),
                'discounts' => fn($q) => $q->where('start', '<=', today())->where('end', '>=', today())->where('active', 1),
                'translation' => fn($q) => $q->where('locale', $this->language)->orWhere('locale', $locale),
                'shop' => fn($q) => $q->select('id', 'status')
                    ->when(data_get($filter, 'shop_status'), function ($q, $status) {
                            $q->where('status', '=', $status);
                        }
                    ),
                'shop.translation' => fn($q) => $q->where('locale', $this->language)
                    ->orWhere('locale', $locale)
                    ->select('id', 'locale', 'title', 'shop_id'),
                'reviews',
                'translations',
                'category' => fn($q) => $q->select('id', 'uuid'),
                'category.translation' => fn($q) => $q->where('locale', $this->language)
                    ->orWhere('locale', $locale)
                    ->select('id', 'category_id', 'locale', 'title'),
                'unit.translation' => fn($q) => $q->where('locale', $this->language)
                    ->orWhere('locale', $locale)
                    ->select('id', 'locale', 'title', 'unit_id'),
            ])
            ->whereHas('translation', fn($query) => $query
                ->where('locale', $this->language)
                ->orWhere('locale', $locale)
            )
            ->when(data_get($filter, 'shop_status'), function ($q, $status) {
                $q->whereHas('shop', function (Builder $query) use ($status) {
                    $query->where('status', '=', $status);
                });
            })
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
                        $query->withCount('orderDetails')->orderBy('order_details_count', 'desc');
                        break;
                    case 'low_sale':
                        $query->withCount('orderDetails')->orderBy('order_details_count');
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

            })
            ->when(data_get($filter, 'column'), function (Builder $query, $column) use ($filter) {
                $query->orderBy(
                    $column,
                    data_get($filter, 'sort', 'desc')
                );
            })
            ->paginate(data_get($filter, 'perPage', 10));
    }

    public function productsMostSold(array $filter = [])
    {
        $locale  = data_get(Language::languagesList()->where('default', 1)->first(), 'locale');

        return $this->model()
            ->filter($filter)
            ->updatedDate($this->updatedDate)
            ->whereHas('translation', function ($q) use ($locale) {
                $q->where('locale', $this->language)->orWhere('locale', $locale);
            })
            ->withAvg('reviews', 'rating')
            ->withCount('orderDetails')
            ->withCount('stocks')
            ->with([
                'stock' => fn($q) => $q->where('quantity', '>', 0),
                'stock.stockExtras.group.translation' => fn($q) => $q
                    ->where('locale', $this->language)
                    ->orWhere('locale', $locale),
                'translation' => fn($q) => $q
                    ->where('locale', $this->language)
                    ->orWhere('locale', $locale),
                'unit.translation' => fn($q) => $q->where('locale', $this->language)
                    ->orWhere('locale', $locale)
                    ->select('id', 'locale', 'title', 'unit_id'),
                ])
            ->whereHas('stock', function ($item){
                $item->where('quantity', '>', 0);
            })
            ->whereHas('shop', function ($item) {
                $item->whereNull('deleted_at');
            })
            ->whereActive(1)
            ->orderBy('order_details_count', 'desc')
            ->paginate(data_get($filter, 'perPage', 10));
    }

    /**
     * @param array $filter
     * @return mixed
     */
    public function productsDiscount(array $filter = []): mixed
    {
        $profitable = data_get($filter, 'profitable') ? '=' : '>=';
        $locale  = data_get(Language::languagesList()->where('default', 1)->first(), 'locale');

        return $this->model()
            ->with([
                'discounts' => fn($q) => $q->where('start', '<=', today())->where('end', '>=', today())->where('active', 1),
                'stocks' => fn($q) => $q->where('quantity', '>', 0),
                'stocks.stockExtras.group.translation' => fn($q) => $q->where('locale', $this->language)
                    ->orWhere('locale', $locale),
                'translation' => fn($q) => $q->where('locale', $this->language)
                    ->orWhere('locale', $locale)
                    ->select('id', 'product_id', 'locale', 'title'),
                'unit.translation' => fn($q) => $q->where('locale', $this->language)
                    ->orWhere('locale', $locale)
                    ->select('id', 'locale', 'title', 'unit_id'),
            ])
            ->withAvg('reviews', 'rating')
            ->whereActive(1)
            ->filter($filter)
            ->updatedDate($this->updatedDate)
            ->whereHas('discounts', function ($item) use ($profitable) {
                $item->where('active', 1)
                    ->whereDate('start', '<=', today())
                    ->whereDate('end', $profitable, today()->format('Y-m-d'));
            })
            ->whereHas('translation', function ($q) use ($locale) {
                $q->where('locale', $this->language)->orWhere('locale', $locale);
            })
            ->whereHas('stocks', function ($item) {
                $item->where('quantity', '>', 0);
            })
            ->whereHas('shop')
            ->paginate(data_get($filter, 'perPage', 10));
    }

    /**
     * @param string $uuid
     * @return Product|null
     */
    public function productByUUID(string $uuid): ?Product
    {
        /** @var Product $product */
        $product = $this->model();
        $locale  = data_get(Language::languagesList()->where('default', 1)->first(), 'locale');

        return $product
            ->whereHas('translation',
                fn($q) => $q->where('locale', $this->language)->orWhere('locale', $locale)
            )
            ->withAvg('reviews', 'rating')
            ->withCount('reviews')
            ->with([
                'stocks' => fn($q) => $q->with([
                    'bonus' => fn($q) => $q
                        ->with([
                            'stock' => fn($q) => $q->where('quantity', '>', 0),
                            'stock.countable.translation' => fn($q) => $q->where('locale', $this->language)
                                ->orWhere('locale', $locale)
                        ])
                        ->where('expired_at', '>', now())->where('status', true),
                ])->where('quantity', '>', 0),
                'stocks.stockExtras.group.translation' => fn($q) => $q->where('locale', $this->language)
                    ->orWhere('locale', $locale),
                'stocks.addons' => fn($q) => $q->whereHas('addon', fn($a) => $a->whereHas('stock', fn($q) => $q->where('quantity', '>', 0))),
                'stocks.addons.addon' => fn($q) => $q
                    ->with([
                        'stock' => fn($q) => $q->where('quantity', '>', 0),
                        'translation' => fn($q) => $q->where('locale', $this->language)->orWhere('locale', $locale)
                    ])
                    ->whereHas('stock', fn($q) => $q->where('quantity', '>', 0))
                    ->select([
                        'id',
                        'uuid',
                        'category_id',
                        'unit_id',
                        'img',
                        'active',
                        'status',
                        'min_qty',
                        'max_qty',
                        'interval',
                    ])
                    ->where('active', true)
                    ->where('addon', true)
                    ->where('status', Product::PUBLISHED),
                'discounts',
                'translation' => fn($q) => $q->where('locale', $this->language)->orWhere('locale', $locale),
                'translations',
                'unit.translation' => fn($q) => $q->where('locale', $this->language)
                    ->orWhere('locale', $locale)
                    ->select('id', 'locale', 'title', 'unit_id'),
            ])
            ->where('active', true)
            ->where('addon', false)
            ->where('status', Product::PUBLISHED)
            ->where('uuid', $uuid)
            ->first();
    }
}
