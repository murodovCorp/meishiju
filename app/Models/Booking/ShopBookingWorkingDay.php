<?php

namespace App\Models\Booking;

use Eloquent;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;

/**
 * App\Models\ShopBookingWorkingDay
 *
 * @property int $id
 * @property int $shop_id
 * @property string $day
 * @property string|null $from
 * @property string|null $to
 * @property boolean|null $disabled
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property Carbon|null $deleted_at
 * @method static Builder|self filter($array = [])
 * @method static Builder|self newModelQuery()
 * @method static Builder|self newQuery()
 * @method static Builder|self query()
 * @method static Builder|self whereCreatedAt($value)
 * @method static Builder|self whereUpdatedAt($value)
 * @method static Builder|self whereId($value)
 * @method static Builder|self whereShopId($value)
 * @method static Builder|self whereDay($value)
 * @method static Builder|self whereFrom($value)
 * @method static Builder|self whereTo($value)
 * @mixin Eloquent
 */
class ShopBookingWorkingDay extends Model
{
    use SoftDeletes;

    protected $guarded = ['id'];

    const MONDAY    = 'monday';
    const TUESDAY   = 'tuesday';
    const WEDNESDAY = 'wednesday';
    const THURSDAY  = 'thursday';
    const FRIDAY    = 'friday';
    const SATURDAY  = 'saturday';
    const SUNDAY    = 'sunday';

    const DAYS = [
        self::MONDAY    => self::MONDAY,
        self::TUESDAY   => self::TUESDAY,
        self::WEDNESDAY => self::WEDNESDAY,
        self::THURSDAY  => self::THURSDAY,
        self::FRIDAY    => self::FRIDAY,
        self::SATURDAY  => self::SATURDAY,
        self::SUNDAY    => self::SUNDAY,
    ];

    public function shop(): BelongsTo
    {
        return $this->belongsTo(BookingShop::class, 'shop_id');
    }

    public function scopeFilter($query, $filter = [])
    {
        return $query
            ->when(data_get($filter, 'day'),        fn($q, $day)        => $q->where('day', $day))
            ->when(isset($filter['deleted_at']),        fn($q)              => $q->onlyTrashed())
            ->when(data_get($filter, 'shop_id'),    fn($q, $shopId)     => $q->where('shop_id', $shopId))
            ->when(data_get($filter, 'from'),       fn($q, $from)       => $q->where('from', '>=', $from))
            ->when(data_get($filter, 'to'),         fn($q, $to)         => $q->where('to', '<=', $to))
            ->when(data_get($filter, 'disabled'),   fn($q, $disabled)   => $q->where('disabled', $disabled));
    }
}
