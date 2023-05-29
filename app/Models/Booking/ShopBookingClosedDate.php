<?php

namespace App\Models\Booking;

use Eloquent;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;

/**
 * App\Models\ShopBookingClosedDate
 *
 * @property int $id
 * @property int $shop_id
 * @property Carbon|null $date
 * @property BookingShop|null $shop
 * @property Carbon $created_at
 * @property Carbon $updated_at
 * @property Carbon $deleted_at
 * @method static Builder|self filter($query, $filter)
 * @method static Builder|self newModelQuery()
 * @method static Builder|self newQuery()
 * @method static Builder|self query()
 * @method static Builder|self whereCreatedAt($value)
 * @method static Builder|self whereId($value)
 * @method static Builder|self whereShopId($value)
 * @method static Builder|self whereUpdatedAt($value)
 * @mixin Eloquent
 */
class ShopBookingClosedDate extends Model
{
    use HasFactory, SoftDeletes;

    protected $guarded = ['id'];

    public function shop(): BelongsTo
    {
        return $this->belongsTo(BookingShop::class, 'shop_id');
    }

    public function scopeFilter($query, array $filter) {
        $query
            ->when(data_get($filter, 'shop_id'),    fn($q, $shopId)     => $q->where('shop_id', $shopId))
            ->when(isset($filter['deleted_at']),        fn($q)              => $q->onlyTrashed())
            ->when(data_get($filter, 'date_from'),  fn($q, $dateFrom)   => $q->where('date', '>=', $dateFrom))
            ->when(data_get($filter, 'date_to'),    fn($q, $dateTo)     => $q->where('date', '<=', $dateTo));
    }
}
