<?php

namespace App\Models;

use App\Traits\Loadable;
use App\Traits\MetaTagable;
use Database\Factories\CategoryFactory;
use Eloquent;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;

/**
 * App\Models\Category
 *
 * @property int $id
 * @property string $uuid
 * @property string|null $keywords
 * @property int|null $parent_id
 * @property int $type
 * @property string|null $img
 * @property int $active
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property Carbon|null $deleted_at
 * @property-read Collection|self[] $children
 * @property-read int|null $children_count
 * @property-read Collection|Gallery[] $galleries
 * @property-read int|null $galleries_count
 * @property-read Category|null $parent
 * @property-read Collection|Product[] $products
 * @property-read Collection|Stock[] $stocks
 * @property-read int|null $products_count
 * @property-read int|null $stocks_count
 * @property-read CategoryTranslation|null $translation
 * @property-read Collection|CategoryTranslation[] $translations
 * @property-read int|null $translations_count
 * @property-read Collection|ModelLog[] $logs
 * @property-read int|null $logs_count
 * @property-read Collection|Receipt[] $receipts
 * @property-read int|null $receipts_count
 * @method static CategoryFactory factory(...$parameters)
 * @method static Builder|self filter($array)
 * @method static Builder|self withThreeChildren($array)
 * @method static Builder|self withTrashedThreeChildren($array)
 * @method static Builder|self withSecondChildren($array)
 * @method static Builder|self withParent($array)
 * @method static Builder|self newModelQuery()
 * @method static Builder|self newQuery()
 * @method static Builder|self onlyTrashed()
 * @method static Builder|self query()
 * @method static Builder|self updatedDate($updatedDate)
 * @method static Builder|self whereActive($value)
 * @method static Builder|self whereCreatedAt($value)
 * @method static Builder|self whereDeletedAt($value)
 * @method static Builder|self whereId($value)
 * @method static Builder|self whereImg($value)
 * @method static Builder|self whereKeywords($value)
 * @method static Builder|self whereParentId($value)
 * @method static Builder|self whereType($value)
 * @method static Builder|self whereUpdatedAt($value)
 * @method static Builder|self whereUuid($value)
 * @method static Builder|self withTrashed()
 * @method static Builder|self withoutTrashed()
 * @mixin Eloquent
 */
class Category extends Model
{
    use HasFactory, Loadable, SoftDeletes, MetaTagable;

    protected $guarded = ['id'];

    const MAIN      = 1;
    const BLOG      = 2;
    const BRAND     = 3;
    const SHOP      = 4;
    const RECEIPT   = 5;
    const MENU      = 6;
    const CAREER    = 7;

    const TYPES = [
        'main'    => self::MAIN,
        'blog'    => self::BLOG,
        'brand'   => self::BRAND,
        'shop'    => self::SHOP,
        'receipt' => self::RECEIPT,
        'menu'    => self::MENU,
        'career'  => self::CAREER,
    ];

    const TYPES_VALUES = [
        self::MAIN      => 'main',
        self::BLOG      => 'blog',
        self::BRAND     => 'brand',
        self::SHOP      => 'shop',
        self::RECEIPT   => 'receipt',
        self::MENU      => 'menu',
        self::CAREER    => 'career',
    ];

    protected $casts = [
        'active'     => 'bool',
        'created_at' => 'datetime:Y-m-d H:i:s',
        'updated_at' => 'datetime:Y-m-d H:i:s',
    ];

    public function getTypeAttribute($value)
    {
        return !is_null($value) ? data_get(self::TYPES, $value) : 'main';
    }

    public function translations(): HasMany
    {
        return $this->hasMany(CategoryTranslation::class);
    }

    public function translation(): HasOne
    {
        return $this->hasOne(CategoryTranslation::class);
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id');
    }

    public function products(): HasMany
    {
        return $this->hasMany(Product::class);
    }

    public function receipts(): HasMany
    {
        return $this->hasMany(Receipt::class);
    }

    public function stocks(): HasManyThrough
    {
        return $this
            ->hasManyThrough(Stock::class, Product::class, 'category_id', 'countable_id')
            ->where('countable_type', Product::class);
    }

    public function shopCategory(): HasMany
    {
        return $this->hasMany(ShopCategory::class);
    }

    public function logs(): MorphMany
    {
        return $this->morphMany(ModelLog::class, 'model');
    }

    public function scopeUpdatedDate($query, $updatedDate)
    {
        $query->where('updated_at', '>', $updatedDate);
    }

    #region Withes

    public function scopeWithSecondChildren($query, $data)
    {
        $query->with([
            'translation' => fn($q) => $q->select('id', 'locale', 'title', 'category_id')
                ->where('locale', data_get($data, 'language')),

            'children' => fn($q) => $q->select(['id', 'uuid', 'keywords', 'parent_id', 'type', 'img', 'active']),

            'children.translation' => fn($q) => $q->select('id', 'locale', 'title', 'category_id')
                ->where('locale', data_get($data, 'language')),
        ])
            ->when(data_get($data, 'has_products'), fn($b) => $b->whereHas('products',
                fn($q) => $q->where('status', Product::PUBLISHED)->where('addon', false)->where('active', true),
            ));
    }

    public function scopeWithParent($query, $data)
    {
        $query->with([
            'translation' => fn($q) => $q->select('id', 'locale', 'title', 'category_id')
                ->where('locale', data_get($data, 'language')),

            'parent' => fn($q) => $q->select(['id', 'uuid', 'keywords', 'parent_id', 'type', 'img', 'active']),

            'parent.translation' => fn($q) => $q->select('id', 'locale', 'title', 'category_id')
                ->where('locale', data_get($data, 'language')),
        ])
            ->when(data_get($data, 'has_products'), fn($b) => $b->whereHas('products',
                fn($q) => $q->where('status', Product::PUBLISHED)->where('addon', false)->where('active', true),
            ));
    }

    public function scopeWithThreeChildren($query, $data)
    {
        $query->with([
            'translation' => fn($q) => $q->select('id', 'locale', 'title', 'category_id')
                ->where('locale', data_get($data, 'language')),

            'children' => fn($q) => $q->select(['id', 'uuid', 'keywords', 'parent_id', 'type', 'img', 'active']),

            'children.translation' => fn($q) => $q->select('id', 'locale', 'title', 'category_id')
                ->where('locale', data_get($data, 'language')),

            'children.children' => fn($q) => $q->select([
                'id', 'uuid', 'keywords', 'parent_id', 'type', 'img', 'active'
            ]),

            'children.children.translation' => fn($q) => $q->select('id', 'locale', 'title', 'category_id')
                ->where('locale', data_get($data, 'language')),
        ])
            ->when(data_get($data, 'has_products'), fn($b) => $b->whereHas('products',
                fn($q) => $q->where('status', Product::PUBLISHED)->where('addon', false)->where('active', true),
            ));
    }

    public function scopeWithTrashedThreeChildren($query, $data)
    {
        $with = [
            'translation' => fn($q) => $q->select('id', 'locale', 'title', 'category_id')
                ->where('locale', data_get($data, 'language')),

            'children' => fn($q) => $q->withTrashed()->select(['id', 'uuid', 'keywords', 'parent_id', 'type', 'img', 'active']),

            'children.translation' => fn($q) => $q->select('id', 'locale', 'title', 'category_id')
                ->where('locale', data_get($data, 'language')),

            'children.children' => fn($q) => $q->withTrashed()->select([
                'id', 'uuid', 'keywords', 'parent_id', 'type', 'img', 'active'
            ]),

            'children.children.translation' => fn($q) => $q->select('id', 'locale', 'title', 'category_id')
                ->where('locale', data_get($data, 'language')),
        ];

        if (data_get($data, 'with')) {
            $with += data_get($data, 'with');
        }

        $query->with($with)->when(data_get($data, 'has_products'), fn($b) => $b->whereHas('products',
            fn($q) => $q->where('status', Product::PUBLISHED)->where('addon', false)->where('active', true),
        ));
    }

    #endregion

    /* Filter Scope */
    public function scopeFilter($value, $array)
    {
        return $value
            ->when(in_array(data_get($array, 'type'), array_keys(Category::TYPES)), function ($q) use ($array) {
                $q->where('type', '=', data_get(Category::TYPES, $array['type'],Category::MAIN));
            })
            ->when(isset($array['active']), function ($q) use ($array) {
                $q->whereActive($array['active']);
            })
            ->when(isset($array['length']), function ($q) use ($array) {
                $q->skip(data_get($array, 'start', 0))->take($array['length']);
            })
            ->when(isset($array['shop_id']), fn($query) => $query
                ->whereHas('shopCategory', fn($q) => $q->where('shop_id', $array['shop_id']) )
            )
            ->when(isset($array['r_shop_id']), fn($query) => $query
                ->whereHas('receipts', fn($q) => $q->where('shop_id', $array['r_shop_id']) )
            )
            ->when(data_get($array, 'has_products'), function ($builder) use ($array) {

                if(data_get($array, 'type') === self::TYPES_VALUES[self::RECEIPT]) {
                    return $builder->whereHas('receipts');
                }

                if (data_get($array, 'type') === self::TYPES_VALUES[self::CAREER]) {
                    return $builder;
                }

                return $builder->whereHas('products', fn($q) => $q
                    ->when(data_get($array, 'p_shop_id'), fn($q, $shopId) => $q->where('shop_id', $shopId))
                    ->where('status', Product::PUBLISHED)
                    ->where('addon', false)
                    ->where('active', true),
                );
            })
            ->when(isset($array['deleted_at']), fn($q) => $q->onlyTrashed())
            ->when(data_get($array, 'search'), function ($query, $search) {
                $query->where(function ($q) use($search) {
                    $q->where('keywords', 'LIKE', '%' . $search . '%')
                        ->orWhereHas('translation', function ($q) use ($search) {

                            $q->where('title', 'LIKE', '%' . $search . '%')
                                ->orWhere('keywords', 'LIKE', '%' . $search . '%');

                        })->orWhereHas('children.translation', function ($q) use ($search) {

                            $q->where('title', 'LIKE', '%' . $search . '%')
                                ->orWhere('keywords', 'LIKE', '%' . $search . '%');

                        })->orWhereHas('children.children.translation', function ($q) use ($search) {

                            $q->where('title', 'LIKE', '%' . $search . '%')
                                ->orWhere('keywords', 'LIKE', '%' . $search . '%');
                        });
                });
            });
    }
}
