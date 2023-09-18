<?php

namespace App\Models;

use App\Helpers\Utility;
use App\Traits\Loadable;
use App\Traits\Reviewable;
use App\Traits\SetCurrency;
use Database\Factories\ShopFactory;
use Eloquent;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Relations\MorphOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;

/**
 * App\Models\Shop
 *
 * @property int $id
 * @property string $uuid
 * @property int $user_id
 * @property float $tax
 * @property float $rate_tax
 * @property float $percentage
 * @property array|null $location
 * @property string|null $phone
 * @property int|null $show_type
 * @property boolean $open
 * @property boolean $visibility
 * @property boolean $verify
 * @property string|null $background_img
 * @property string|null $logo_img
 * @property float $min_amount
 * @property string $status
 * @property string $delivery_price
 * @property string|null $status_note
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property Carbon|null $deleted_at
 * @property int|null $service_fee
 * @property int|null $type
 * @property array|null $delivery_time
 * @property-read Collection|Gallery[] $galleries
 * @property-read int|null $galleries_count
 * @property-read Collection|Discount[] $discounts
 * @property-read int|null $discounts_count
 * @property-read Collection|Invitation[] $invitations
 * @property-read int|null $invitations_count
 * @property-read Collection|OrderDetail[] $orders
 * @property-read int|null $orders_count
 * @property-read Collection|Product[] $products
 * @property-read int|null $products_count
 * @property-read Collection|ShopPayment[] $shopPayments
 * @property-read int|null $shop_payments_count
 * @property-read Collection|Review[] $reviews
 * @property-read DeliveryZone|null $deliveryZone
 * @property-read int|null $reviews_count
 * @property-read int|null $reviews_avg_rating
 * @property-read User $seller
 * @property-read ShopSubscription|null $subscription
 * @property-read ShopTranslation|null $translation
 * @property-read Collection|ShopTranslation[] $translations
 * @property-read int|null $translations_count
 * @property-read Collection|User[] $users
 * @property-read int|null $users_count
 * @property-read Collection|ShopWorkingDay[] $workingDays
 * @property-read int|null $working_days_count
 * @property-read Collection|ShopClosedDate[] $closedDates
 * @property-read int|null $closed_dates_count
 * @property-read Collection|ShopTag[] $tags
 * @property-read int|null $tags_count
 * @property float|null $avg_rate
 * @property-read Bonus|null $bonus
 * @property-read ShopDeliverymanSetting|null $shopDeliverymanSetting
 * @property-read Collection|ModelLog[] $logs
 * @property-read int|null $logs_count
 * @method static ShopFactory factory(...$parameters)
 * @method static Builder|Shop filter($filter)
 * @method static Builder|Shop newModelQuery()
 * @method static Builder|Shop newQuery()
 * @method static Builder|Shop onlyTrashed()
 * @method static Builder|Shop query()
 * @method static Builder|Shop updatedDate($updatedDate)
 * @method static Builder|Shop whereBackgroundImg($value)
 * @method static Builder|Shop whereCloseTime($value)
 * @method static Builder|Shop whereCreatedAt($value)
 * @method static Builder|Shop whereDeletedAt($value)
 * @method static Builder|Shop whereDeliveryRange($value)
 * @method static Builder|Shop whereId($value)
 * @method static Builder|Shop whereLocation($value)
 * @method static Builder|Shop whereLogoImg($value)
 * @method static Builder|Shop whereMinAmount($value)
 * @method static Builder|Shop whereOpen($value)
 * @method static Builder|Shop whereOpenTime($value)
 * @method static Builder|Shop wherePercentage($value)
 * @method static Builder|Shop wherePhone($value)
 * @method static Builder|Shop whereShowType($value)
 * @method static Builder|Shop whereStatus($value)
 * @method static Builder|Shop whereStatusNote($value)
 * @method static Builder|Shop whereTax($value)
 * @method static Builder|Shop whereUpdatedAt($value)
 * @method static Builder|Shop whereUserId($value)
 * @method static Builder|Shop whereUuid($value)
 * @method static Builder|Shop withTrashed()
 * @method static Builder|Shop withoutTrashed()
 * @mixin Eloquent
 */
class Shop extends Model
{
    use HasFactory, SoftDeletes, Loadable, SetCurrency, Reviewable;

    protected $guarded = ['id'];

    const STATUS = [
        'new',
        'edited',
        'approved',
        'rejected',
        'inactive'
    ];

    const DELIVERY_TIME_MINUTE  = 'minute';
    const DELIVERY_TIME_HOUR    = 'hour';
    const DELIVERY_TIME_DAY     = 'day';
    const DELIVERY_TIME_MONTH   = 'month';

    const DELIVERY_TIME_TYPE = [
        self::DELIVERY_TIME_MINUTE,
        self::DELIVERY_TIME_HOUR,
        self::DELIVERY_TIME_DAY,
        self::DELIVERY_TIME_MONTH,
    ];

    protected $casts = [
        'location'      => 'array',
        'delivery_time' => 'array',
        'close_time'    => 'date:H:i',
        'open'          => 'boolean',
    ];

    public function getAvgRateAttribute(): ?float
    {
        return $this->orders()->where([
            ['shop_id', $this->id],
            ['status', Order::STATUS_DELIVERED]
        ])->avg('rate');
    }

    public function bonus(): MorphOne
    {
        return $this->morphOne(Bonus::class, 'bonusable');
    }

    public function tags(): BelongsToMany
    {
        return $this->belongsToMany(ShopTag::class, 'assign_shop_tags', 'shop_id', 'shop_tag_id');
    }

    public function discounts(): HasMany
    {
        return $this->hasMany(Discount::class);
    }

    public function translations(): HasMany
    {
        return $this->hasMany(ShopTranslation::class);
    }

    public function workingDays(): HasMany
    {
        return $this->hasMany(ShopWorkingDay::class);
    }

    public function closedDates(): HasMany
    {
        return $this->hasMany(ShopClosedDate::class);
    }

    public function translation(): HasOne
    {
        return $this->hasOne(ShopTranslation::class);
    }

    public function shopDeliverymanSetting(): HasOne
    {
        return $this->hasOne(ShopDeliverymanSetting::class);
    }

    public function seller(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function shopPayments(): HasMany
    {
        return $this->hasMany(ShopPayment::class);
    }

    public function users(): HasManyThrough
    {
        return $this->hasManyThrough(User::class, Invitation::class,
            'shop_id', 'id', 'id', 'user_id');
    }

    public function products(): HasMany
    {
        return $this->hasMany(Product::class);
    }

    public function invitations(): HasMany
    {
        return $this->hasMany(Invitation::class);
    }

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }

    public function deliveryZone(): HasOne
    {
        return $this->hasOne(DeliveryZone::class);
    }

    public function reviews(): MorphMany
    {
        return $this->morphMany(Review::class, 'assignable');
    }

    public function subscription(): HasOne
    {
        return $this->hasOne(ShopSubscription::class, 'shop_id')
            ->whereDate('expired_at', '>=', today())
            ->where([
                'active' => 1
            ])
            ->orderByDesc('id');
    }

    public function categories(): BelongsToMany
    {
        return $this->belongsToMany(Category::class, 'shop_categories', 'shop_id', 'category_id');
    }

    public function logs(): MorphMany
    {
        return $this->morphMany(ModelLog::class, 'model');
    }

    public function scopeUpdatedDate($query, $updatedDate)
    {
    }

    public function scopeFilter($query, $filter)
    {
        $orders  = [];

        if (data_get($filter, 'address.latitude') && data_get($filter, 'address.longitude')) {
            DeliveryZone::list()->map(function (DeliveryZone $deliveryZone) use ($filter, &$orders) {

                if (!$deliveryZone->shop_id) {
                    return null;
                }

                $shop       = $deliveryZone->shop;

                $location   = data_get($deliveryZone->shop, 'location', []);

                $km         = (new Utility)->getDistance($location, data_get($filter, 'address', []));
                $rate       = data_get($filter, 'currency.rate', 1);

                $orders[$deliveryZone->shop_id] = (new Utility)->getPriceByDistance($km, $shop, $rate);

                if (
                    Utility::pointInPolygon(data_get($filter, 'address'), $deliveryZone->address)
                    && $orders[$deliveryZone->shop_id] > 0
                ) {
                    return $deliveryZone->shop_id;
                }

                unset($orders[$deliveryZone->shop_id]);

                return null;
            })
                ->reject(fn($data) => empty($data))
                ->toArray();

            arsort($orders);
        }

        $query
            ->when(data_get($filter, 'user_id'), function ($q, $userId) {
                $q->where('user_id', $userId);
            })
            ->when(data_get($filter, 'status'), function ($q, $status) {
                $q->where('status', $status);
            })
            ->when(isset($filter['open']), function ($q) use($filter) {
                $q->where('open', $filter['open']);
            })
            ->when(isset($filter['visibility']), function ($q, $visibility) {
                $q->where('visibility', $visibility);
            })
            ->when(isset($filter['verify']), function ($q) use($filter) {
                $q->where('verify', $filter['verify']);
            })
            ->when(isset($filter['show_type']), function ($q, $showType) {
                $q->where('show_type', $showType);
            })
            ->when(data_get($filter, 'category_id'), function ($q, $categoryId) {
                $q->whereHas('categories', function ($query) use ($categoryId) {
                    $query->where('category_id', $categoryId);
                });
            })
            ->when(data_get($filter, 'bonus'), function (Builder $query) {
                $query->whereHas('bonus', function ($q) {
                    $q->where('expired_at', '>', now())->where('status', true);
                });
            })
            ->when(data_get($filter, 'deals'), function (Builder $query) {
                $query->where(function ($query) {
                    $query->whereHas('bonus', function ($q) {
                        $q->where('expired_at', '>', now())->where('status', true);
                    })->orWhereHas('discounts', function ($q) {
                        $q->where('end', '>=', now())->where('active', 1);
                    });
                });
            })
            ->when(data_get($filter, 'work_24_7'), function (Builder $query) {
                $query->whereHas('workingDays', fn($q) => $q
                    ->where('from', '01-00')
                    ->where('to', '>=', '23-00')
                );
            })
            ->when(data_get($filter, 'address'), function ($query) use ($filter, $orders) {
                $orderBys = ['new', 'old', 'best_sale', 'low_sale', 'high_rating', 'low_rating', 'trust_you'];
                $orderByIds = implode(',', array_keys($orders));

                $query
                    ->whereHas('deliveryZone')
                    ->when($orderByIds, function ($builder) use ($filter, $orderByIds, $orders, $orderBys) {

                        $builder->whereIn('id', array_keys($orders));

                        if (!in_array(data_get($filter, 'order_by'), $orderBys)) {
                            $builder->orderByRaw("FIELD(shops.id, $orderByIds) ASC");
                        }

                    });

            })
            ->when(data_get($filter, 'search'), function ($query, $search) {
                $query->where(function ($query) use ($search) {
                    $query
                        ->where('id', 'LIKE', "%$search%")
                        ->orWhere('phone', 'LIKE', "%$search%")
                        ->orWhereHas('translations', function ($q) use ($search) {
                            $q->where('title', 'LIKE', "%$search%")
                                ->select('id', 'shop_id', 'locale', 'title');
                        });
                });
            })
            ->when(data_get($filter, 'take'), function (Builder $query, $take) {

                $query->whereHas('tags', function (Builder $q) use ($take) {
                    $q->when(is_array($take), fn($q) => $q->whereIn('id', $take), fn($q) => $q->where('id', $take));
                });

            })
            ->when(data_get($filter, 'free_delivery'), function (Builder $q) {
                $q->where([
                    ['delivery_price', '=', 0],
                ]);
            })
            ->when(data_get($filter, 'fast_delivery'), function (Builder $q) {
                $q
                    ->where('delivery_time->type','minute')
                    ->orWhere('delivery_time->type','day')
                    ->orWhere('delivery_time->type','month')
                    ->orderByRaw('CAST(JSON_EXTRACT(delivery_time, "$.from") AS from)', 'desc');
            })
            ->when(data_get($filter, 'has_discount'), function (Builder $query) {
                $query->whereHas('discounts', function ($q) {
                    $q
                        ->where('end', '>=', now())
                        ->where('active', 1)
                        ->whereNull('deleted_at');
                });
            })
            ->when(isset($filter['deleted_at']), fn($q) => $q->onlyTrashed());
    }
}
