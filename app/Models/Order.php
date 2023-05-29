<?php

namespace App\Models;

use App\Models\Booking\Table;
use App\Traits\Loadable;
use App\Traits\Payable;
use App\Traits\Reviewable;
use Database\Factories\OrderFactory;
use DB;
use Eloquent;
use Facade\Ignition\Tabs\Tab;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Relations\MorphOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;

/**
 * App\Models\Order
 *
 * @property int $id
 * @property int $user_id
 * @property string $delivery_type
 * @property int $rate_delivery_fee
 * @property int $rate_waiter_fee
 * @property double $total_price
 * @property int $currency_id
 * @property int $rate
 * @property string|null $note
 * @property int $shop_id
 * @property int $seller_price
 * @property float $tax
 * @property float|null $commission_fee
 * @property float|null $rate_commission_fee
 * @property string $status
 * @property array|null $location
 * @property string|null $address
 * @property float $delivery_fee
 * @property int|null $deliveryman
 * @property string|null $delivery_date
 * @property string|null $delivery_time
 * @property double|null $total_discount
 * @property string|null $phone
 * @property string|null $username
 * @property string|null $img
 * @property boolean|null $current
 * @property float|null $waiter_fee
 * @property int|null $waiter_id
 * @property int|null $cook_id
 * @property int|null $table_id
 * @property int|null $booking_id
 * @property int|null $user_booking_id
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property Carbon|null $deleted_at
 * @property-read int $rate_total_price
 * @property-read double $rate_total_discount
 * @property-read double $order_details_sum_total_price
 * @property-read double $order_details_sum_discount
 * @property-read int $rate_tax
 * @property-read Currency|null $currency
 * @property-read UserAddress|null $myAddress
 * @property-read OrderCoupon|null $coupon
 * @property-read Collection|OrderDetail[] $orderDetails
 * @property-read int|null $order_details_count
 * @property-read Collection|OrderDetail[] $orderRefunds
 * @property-read int|null $order_refunds_count
 * @property-read int|null $order_details_sum_quantity
 * @property-read PointHistory|null $pointHistory
 * @property-read PointHistory|null $pointHistories
 * @property-read Review|null $review
 * @property-read Transaction|null $transaction
 * @property-read Collection|Transaction[] $transactions
 * @property-read int $transactions_count
 * @property-read Collection|PaymentProcess[] $paymentProcess
 * @property-read int $payment_process_count
 * @property-read User|null $user
 * @property-read Shop $shop
 * @property-read User $deliveryMan
 * @property-read User $waiter
 * @property-read User $cook
 * @property-read Table $table
 * @property-read Collection|Gallery[] $galleries
 * @property-read int|null $galleries_count
 * @property-read Collection|ModelLog[] $logs
 * @property-read int|null $logs_count
 * @method static OrderFactory factory(...$parameters)
 * @method static Builder|self filter($array)
 * @method static Builder|self newModelQuery()
 * @method static Builder|self newQuery()
 * @method static Builder|self query()
 * @method static Builder|self updatedDate($updatedDate)
 * @method static Builder|self whereCommissionFee($value)
 * @method static Builder|self whereCreatedAt($value)
 * @method static Builder|self whereCurrencyId($value)
 * @method static Builder|self whereAddressId($value)
 * @method static Builder|self whereDeletedAt($value)
 * @method static Builder|self whereDeliveryDate($value)
 * @method static Builder|self whereDeliveryFee($value)
 * @method static Builder|self whereDeliveryTime($value)
 * @method static Builder|self whereDeliveryman($value)
 * @method static Builder|self whereId($value)
 * @method static Builder|self whereNote($value)
 * @method static Builder|self whereTotalPrice($value)
 * @method static Builder|self whereRate($value)
 * @method static Builder|self whereShopId($value)
 * @method static Builder|self whereStatus($value)
 * @method static Builder|self whereTax($value)
 * @method static Builder|self whereTotalDiscount($value)
 * @method static Builder|self whereUpdatedAt($value)
 * @method static Builder|self whereUserId($value)
 * @mixin Eloquent
 */
class Order extends Model
{
    use HasFactory, SoftDeletes, Payable, Reviewable, Loadable;

    protected $guarded = ['id'];

    protected $casts = [
        'location' => 'array',
        'address'  => 'array',
    ];

    const STATUS_NEW        = 'new';
    const STATUS_ACCEPTED   = 'accepted';
    const STATUS_COOKING    = 'cooking';
    const STATUS_READY      = 'ready';
    const STATUS_ON_A_WAY   = 'on_a_way';
    const STATUS_DELIVERED  = 'delivered';
    const STATUS_CANCELED   = 'canceled';

    const STATUSES = [
        self::STATUS_NEW        => self::STATUS_NEW,
        self::STATUS_ACCEPTED   => self::STATUS_ACCEPTED,
        self::STATUS_COOKING    => self::STATUS_COOKING,
        self::STATUS_READY      => self::STATUS_READY,
        self::STATUS_ON_A_WAY   => self::STATUS_ON_A_WAY,
        self::STATUS_DELIVERED  => self::STATUS_DELIVERED,
        self::STATUS_CANCELED   => self::STATUS_CANCELED,
    ];

    const PICKUP    = 'pickup';
    const DELIVERY  = 'delivery';
    const DINE_IN   = 'dine_in';

    const DELIVERY_TYPES = [
        self::PICKUP   => self::PICKUP,
        self::DELIVERY => self::DELIVERY,
        self::DINE_IN  => self::DINE_IN,
    ];

    public function currency(): BelongsTo
    {
        return $this->belongsTo(Currency::class)->withTrashed();
    }

    public function myAddress(): BelongsTo
    {
        return $this->belongsTo(UserAddress::class, 'address_id')->withTrashed();
    }

    public function shop(): BelongsTo
    {
        return $this->belongsTo(Shop::class)->withTrashed();
    }

    public function orderDetails(): HasMany
    {
        return $this->hasMany(OrderDetail::class)->withTrashed();
    }

    public function coupon(): HasOne
    {
        return $this->hasOne(OrderCoupon::class, 'order_id')->withTrashed();
    }

    public function transaction(): MorphOne
    {
        return $this->morphOne(Transaction::class, 'payable')->withTrashed();
    }

    public function deliveryMan(): BelongsTo
    {
        return $this->belongsTo(User::class, 'deliveryman')->withTrashed();
    }

    public function cook(): BelongsTo
    {
        return $this->belongsTo(User::class, 'cook_id')->withTrashed();
    }

    public function waiter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'waiter_id')->withTrashed();
    }

    public function table(): BelongsTo
    {
        return $this->belongsTo(Table::class)->withTrashed();
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class)->withTrashed();
    }

    public function pointHistory(): HasOne
    {
        return $this->hasOne(PointHistory::class, 'order_id')->latest();
    }

    public function paymentProcess(): HasMany
    {
        return $this->hasMany(PaymentProcess::class);
    }

    public function pointHistories(): HasMany
    {
        return $this->hasMany(PointHistory::class);
    }

    public function orderRefunds(): HasMany
    {
        return $this->hasMany(OrderRefund::class)->withTrashed();
    }

    public function logs(): MorphMany
    {
        return $this->morphMany(ModelLog::class, 'model');
    }

    public function getRateTotalPriceAttribute(): ?float
    {
        if (request()->is('api/v1/dashboard/user/*') || request()->is('api/v1/rest/*')) {
            return $this->total_price * $this->rate;
        }

        return $this->total_price;
    }

    public function getRateTotalDiscountAttribute(): ?float
    {
        if (request()->is('api/v1/dashboard/user/*') || request()->is('api/v1/rest/*')) {
            return $this->total_discount * $this->rate;
        }

        return $this->total_discount;
    }

    public function getRateDeliveryFeeAttribute(): ?float
    {
        if (request()->is('api/v1/dashboard/user/*') || request()->is('api/v1/rest/*')) {
            return $this->delivery_fee * $this->rate;
        }

        return $this->delivery_fee;
    }

    public function getRateWaiterFeeAttribute(): ?float
    {
        if (request()->is('api/v1/dashboard/user/*') || request()->is('api/v1/rest/*')) {
            return $this->waiter_fee * $this->rate;
        }

        return $this->waiter_fee;
    }

    public function getRateTaxAttribute(): ?float
    {
        if (request()->is('api/v1/dashboard/user/*') || request()->is('api/v1/rest/*')) {
            return $this->tax * $this->rate;
        }

        return $this->tax;
    }

    public function getRateCommissionFeeAttribute(): ?float
    {
        if (request()->is('api/v1/dashboard/user/*') || request()->is('api/v1/rest/*')) {
            return $this->commission_fee * $this->rate;
        }

        return $this->tax;
    }

    public function getSellerPriceAttribute(): ?float
    {
        return $this->total_price - $this->delivery_fee - $this->waiter_fee - $this->commission_fee;
    }

    /**
     * @param $query
     * @param $updatedDate
     * @return void
     */
    public function scopeUpdatedDate($query, $updatedDate): void
    {
        /** @var self $query */
        $query->where('updated_at', '>', $updatedDate);
    }

    /**
     * @param $query
     * @param $filter
     * @return void
     */
    public function scopeFilter($query, $filter): void
    {
        $orderByStatuses = [];

        if (is_array(data_get($filter, 'statuses'))) {

            $orderStatuses = OrderStatus::listNames();

            if (count($orderStatuses) === 0) {
                $orderStatuses = self::STATUSES;
            }

            $orderByStatuses = array_intersect($orderStatuses, data_get($filter, 'statuses'));
        }

        $query
            ->when(data_get($filter, 'isset-deliveryman'), function ($q) {
                $q->whereHas('deliveryMan');
            })
            ->when(data_get($filter, 'isset-waiter'), function ($q) {
                $q->whereNotNull('waiter_id');
            })
            ->when(data_get($filter, 'isset-cook'), function ($q) {
                $q->whereNotNull('cook_id');
            })
            ->when(data_get($filter, 'search'), function ($q, $search) {
                $q->where(function ($b) use ($search) {

                    $b->where('id', 'LIKE', "%$search%")
                        ->orWhere('user_id', $search)
                        ->orWhereHas('user', fn($q) => $q
                            ->where('firstname',  'LIKE', "%$search%")
                            ->orWhere('lastname', 'LIKE', "%$search%")
                            ->orWhere('email',    'LIKE', "%$search%")
                            ->orWhere('phone',    'LIKE', "%$search%")
                        )
                        ->orWhere('note', 'LIKE', "%$search%");
                });
            })
            ->when(data_get($filter, 'shop_id'), function ($q, $shopId) {
                $q->where('shop_id', $shopId);
            })
            ->when(data_get($filter, 'shop_ids'), function ($q, $shopIds) {
                $q->whereIn('shop_id', is_array($shopIds) ? $shopIds : []);
            })
            ->when(data_get($filter, 'user_id'),    fn($q, $userId)     => $q->where('user_id',   (int)$userId))
            ->when(data_get($filter, 'table_id'),   fn($q, $tableId)    => $q->where('table_id',  (int)$tableId))
            ->when(data_get($filter, 'waiter_id'),  fn($q, $waiterId)   => $q->where('waiter_id', (int)$waiterId))
            ->when(data_get($filter, 'cook_id'),    fn($q, $cookId)     => $q->where('cook_id',   (int)$cookId))
            ->when(data_get($filter, 'delivery_type'), fn($q, $deliveryType) => $q->where('delivery_type', $deliveryType))
            ->when(data_get($filter, 'date_from'), function (Builder $query, $dateFrom) use ($filter) {

                $dateFrom = date('Y-m-d', strtotime($dateFrom . ' -1 day'));
                $dateTo = data_get($filter, 'date_to', date('Y-m-d'));

                $dateTo = date('Y-m-d', strtotime($dateTo . ' +1 day'));

                $query->where([
                    ['created_at', '>', $dateFrom],
                    ['created_at', '<', $dateTo],
                ]);
            })
            ->when(data_get($filter, 'delivery_date_from'), function (Builder $query, $dateFrom) use ($filter) {

                $dateFrom = date('Y-m-d', strtotime($dateFrom . ' -1 day'));

                $dateTo = data_get($filter, 'delivery_date_to', date('Y-m-d'));

                $dateTo = date('Y-m-d', strtotime($dateTo . ' +1 day'));

                $query->where([
                    ['delivery_date', '>=', $dateFrom],
                    ['delivery_date', '<=', $dateTo],
                ]);
            })
            ->when(data_get($filter, 'status'), fn($q, $status) => $q->where('status', $status))
            ->when(data_get($filter, 'deliveryman'), fn(Builder $q, $deliveryman) =>
                $q->whereHas('deliveryMan', function ($q) use($deliveryman) {
                    $q->where('id', $deliveryman);
                })
            )
            ->when(data_get($filter, 'empty-deliveryman'), fn(Builder $q) => $q->where(function ($b) {
                $b->whereNull('deliveryman')
                    ->orWhere('deliveryman', '=', null)
                    ->orWhere('deliveryman', '=', 0);
                })
            )
            ->when(data_get($filter, 'empty-waiter'), fn(Builder $q) => $q->where(function ($b) {
                    $b->whereNull('waiter_id');
                })
            )
            ->when(data_get($filter, 'empty-cook'), fn(Builder $q) => $q->where(function ($b) {
                    $b->whereNull('cook_id');
                })
            )
            ->when(isset($filter['current']), fn($q) => $q->where('current', $filter['current']))
            ->when(isset($filter['deleted_at']), fn($q) => $q->onlyTrashed())
            ->when(count($orderByStatuses) > 0, fn($q) => $q->whereIn('status', $orderByStatuses))
            ->when(data_get($filter, 'order_statuses'), function ($q) {
                $q->orderByRaw(
                    DB::raw("FIELD(status, 'new', 'accepted', 'ready', 'on_a_way',  'delivered', 'canceled') ASC")
                );
            }
            );
    }

}
