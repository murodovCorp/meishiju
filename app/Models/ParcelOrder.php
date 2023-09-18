<?php

namespace App\Models;

use App\Traits\Loadable;
use App\Traits\Payable;
use App\Traits\Reviewable;
use DB;
use Eloquent;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * App\Models\ParcelOrder
 *
 * @property int $id
 * @property int|null $user_id
 * @property double|null $total_price
 * @property int|null $currency_id
 * @property string|null $type_id
 * @property float|null $rate
 * @property string|null $note
 * @property double|null $tax
 * @property string|null $status
 * @property array|null $address_from
 * @property string|null $phone_from
 * @property string|null $username_from
 * @property array|null $address_to
 * @property string|null $phone_to
 * @property string|null $username_to
 * @property double|null $delivery_fee
 * @property double|null $km
 * @property int|null $deliveryman_id
 * @property Carbon|null $delivery_date
 * @property string|null $delivery_time
 * @property boolean|null $current
 * @property string|null $img
 * @property double|null $rate_total_price
 * @property double|null $rate_delivery_fee
 * @property double|null $rate_tax
 * @property string|null $qr_value
 * @property string|null $instruction
 * @property string|null $description
 * @property boolean|null $notify
 * @property array|null $option
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property User|null $user
 * @property Currency|null $currency
 * @property ParcelOrderSetting|null $type
 * @property User|null $deliveryman
 * @method static Builder|self filter($value)
 * @method static Builder|self newModelQuery()
 * @method static Builder|self newQuery()
 * @method static Builder|self query()
 * @method static Builder|self whereCreatedAt($value)
 * @method static Builder|self whereUpdatedAt($value)
 * @method static Builder|self whereId($value)
 * @mixin Eloquent
 */
class ParcelOrder extends Model
{
    use Loadable, Payable, Reviewable;

    protected $guarded = ['id'];
    protected $casts   = [
        'address_from'  => 'array',
        'address_to'    => 'array',
        'option'        => 'array'
    ];

    const STATUS_NEW        = 'new';
    const STATUS_PENDING    = 'pending';
    const STATUS_ACCEPTED   = 'accepted';
    const STATUS_READY      = 'ready';
    const STATUS_ON_A_WAY   = 'on_a_way';
    const STATUS_DELIVERED  = 'delivered';
    const STATUS_CANCELED   = 'canceled';

    const STATUSES = [
        self::STATUS_NEW        => self::STATUS_NEW,
        self::STATUS_PENDING    => self::STATUS_PENDING,
        self::STATUS_ACCEPTED   => self::STATUS_ACCEPTED,
        self::STATUS_READY      => self::STATUS_READY,
        self::STATUS_ON_A_WAY   => self::STATUS_ON_A_WAY,
        self::STATUS_DELIVERED  => self::STATUS_DELIVERED,
        self::STATUS_CANCELED   => self::STATUS_CANCELED,
    ];

    public function getRateTotalPriceAttribute(): ?float
    {
        if (request()->is('api/v1/dashboard/user/*') || request()->is('api/v1/rest/*')) {
            return $this->total_price * ($this->rate <= 0 ? 1 : $this->rate);
        }

        return $this->total_price;
    }

    public function getRateDeliveryFeeAttribute(): ?float
    {
        if (request()->is('api/v1/dashboard/user/*') || request()->is('api/v1/rest/*')) {
            return $this->delivery_fee * ($this->rate <= 0 ? 1 : $this->rate);
        }

        return $this->delivery_fee;
    }

    public function getRateTaxAttribute(): ?float
    {
        if (request()->is('api/v1/dashboard/user/*') || request()->is('api/v1/rest/*')) {
            return $this->tax * ($this->rate <= 0 ? 1 : $this->rate);
        }

        return $this->tax;
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function currency(): BelongsTo
    {
        return $this->belongsTo(Currency::class);
    }

    public function deliveryman(): BelongsTo
    {
        return $this->belongsTo(User::class, 'deliveryman_id');
    }

    public function type(): BelongsTo
    {
        return $this->belongsTo(ParcelOrderSetting::class, 'type_id');
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
            ->when(data_get($filter, 'search'), function ($q, $search) {
                $q->where(function ($b) use ($search) {

                    $b->where('id', 'LIKE', "%$search%")
                        ->orWhere('user_id', $search)
                        ->orWhere('phone_from', "%$search%")
                        ->orWhere('username_from', "%$search%")
                        ->orWhere('phone_to', "%$search%")
                        ->orWhere('username_to', "%$search%")
                        ->orWhere('note', 'LIKE', "%$search%")
                        ->orWhereHas('user', fn($q) => $q
                            ->where('firstname',  'LIKE', "%$search%")
                            ->orWhere('lastname', 'LIKE', "%$search%")
                            ->orWhere('email',    'LIKE', "%$search%")
                            ->orWhere('phone',    'LIKE', "%$search%")
                        );
                });
            })
            ->when(data_get($filter, 'user_id'), fn($q, int $userId) => $q->where('user_id', $userId))
            ->when(data_get($filter, 'date_from'), function (Builder $query, $dateFrom) use ($filter) {

                $dateFrom = date('Y-m-d', strtotime($dateFrom));
                $dateTo = data_get($filter, 'date_to', date('Y-m-d'));

                $dateTo = date('Y-m-d', strtotime($dateTo));

                $query->whereDate('created_at', '>=', $dateFrom)
					->whereDate('created_at', '<=', $dateTo);
            })
            ->when(data_get($filter, 'delivery_date_from'), function (Builder $query, $dateFrom) use ($filter) {

                $dateFrom = date('Y-m-d', strtotime($dateFrom));

                $dateTo = data_get($filter, 'delivery_date_to', date('Y-m-d'));

                $dateTo = date('Y-m-d', strtotime($dateTo));

				$query
					->whereDate('delivery_date', '>=', $dateFrom)
					->whereDate('delivery_date', '<=', $dateTo);
            })
            ->when(data_get($filter, 'status'), fn($q, $status) => $q->where('status', $status))
            ->when(data_get($filter, 'deliveryman_id'), function (Builder $query, int $deliverymanId) {
                $query->where('deliveryman_id', $deliverymanId);
            })
            ->when(data_get($filter, 'empty-deliveryman'), function (Builder $query) {
                $query->whereNull('deliveryman_id');
            })
            ->when(data_get($filter, 'isset-deliveryman'), function (Builder $query) {
                $query->whereNotNull('deliveryman_id');
            })
            ->when(isset($filter['current']), fn($q) => $q->where('current', $filter['current']))
            ->when(count($orderByStatuses) > 0, fn($q) => $q->whereIn('status', $orderByStatuses))
            ->when(data_get($filter, 'order_statuses'), function ($q) {
                $q->orderByRaw(
                    DB::raw("FIELD(status, 'new', 'pending', 'accepted', 'ready', 'on_a_way',  'delivered', 'canceled') ASC")
                );
            });
    }
}
