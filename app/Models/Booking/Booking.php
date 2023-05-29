<?php

namespace App\Models\Booking;

use App\Models\Shop;
use App\Models\User;
use Eloquent;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/**
 * App\Models\Booking
 *
 * @property int $id
 * @property int|null $shop_id
 * @property int|null $max_time
 * @property string|null $start_time
 * @property string|null $end_time
 * @property bool|null $active
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property string|null $deleted_at
 * @property Shop|null $shop
 * @property Collection|User[] $users
 * @property int $users_count
 * @method static Builder|self newModelQuery()
 * @method static Builder|self newQuery()
 * @method static Builder|self query()
 * @method static Builder|self filter($filter)
 * @method static Builder|self whereCreatedAt($value)
 * @method static Builder|self whereDeletedAt($value)
 * @method static Builder|self whereId($value)
 * @method static Builder|self whereUpdatedAt($value)
 * @mixin Eloquent
 */
class Booking extends Model
{
    use SoftDeletes;

    protected $guarded = ['id'];

    public function shop(): BelongsTo
    {
        return $this->belongsTo(Shop::class);
    }

    public function users(): HasMany
    {
        return $this->hasMany(UserBooking::class);
    }

    public function scopeFilter($query, $filter) {
        $query
            ->when(data_get($filter, 'shop_id'), fn($q, $shopId) => $q->where('shop_id', $shopId))
            ->when(data_get($filter, 'max_time'), fn($q, $maxTime) => $q->where('max_time', $maxTime))
            ->when(data_get($filter, 'start_time_from'), fn($q, $startFrom) => $q->where('start_time', '>=', $startFrom))
            ->when(data_get($filter, 'start_time_to'), fn($q, $startTo) => $q->where('start_time', '<=', $startTo))
            ->when(data_get($filter, 'end_time_from'), fn($q, $endFrom) => $q->where('end_time', '>=', $endFrom))
            ->when(data_get($filter, 'end_time_to'), fn($q, $endTo) => $q->where('end_time', '<=', $endTo))
            ->when(data_get($filter, 'free_from'), function ($query, $freeFrom) use ($filter) {

                $query->whereDoesntHave('users', function ($q) use ($freeFrom, $filter) {

                    $freeTo = data_get($filter, 'free_to');

                    $q
                        ->where('start_date', '>=', $freeFrom)
                        ->when($freeTo, fn($b) => $b->where('end_date', '<=', $freeTo))
                        ->when(data_get($filter, 'table_id'), fn($b, $tableId) => $b->where('table_id', $tableId));
                });

            })
            ->when(data_get($filter, 'status'), function ($query, $status) {

                if ($status === 'booked') {
                    return $query->whereHas('users', fn($b) => $b->where('status', UserBooking::NEW));
                } else if ($status === 'occupied') {
                    return $query->whereHas('users', fn($b) => $b->where('status', UserBooking::ACCEPTED));
                }

                return $query->whereDoesntHave('users', fn($b) => $b->where('end_date', '>=', now()));
            });
    }
}
