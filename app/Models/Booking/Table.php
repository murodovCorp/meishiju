<?php

namespace App\Models\Booking;

use App\Models\Settings;
use Eloquent;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;
use Stripe\Collection;

/**
 * App\Models\Table
 *
 * @property int $id
 * @property int $name
 * @property int $shop_section_id
 * @property double $tax
 * @property int $chair_count
 * @property boolean $active
 * @property ShopSection|null $shopSection
 * @property Collection|UserBooking[] $users
 * @property int $users_count
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property string|null $deleted_at
 * @method static Builder|self newModelQuery()
 * @method static Builder|self newQuery()
 * @method static Builder|self query()
 * @method static Builder|self filter(array $filter)
 * @method static Builder|self whereCreatedAt($value)
 * @method static Builder|self whereDeletedAt($value)
 * @method static Builder|self whereId($value)
 * @method static Builder|self whereUpdatedAt($value)
 * @mixin Eloquent
 */
class Table extends Model
{
    use SoftDeletes;

    protected $guarded = ['id'];

    public function shopSection(): BelongsTo
    {
        return $this->belongsTo(ShopSection::class);
    }

    public function users(): HasMany
    {
        return $this->hasMany(UserBooking::class, 'table_id');
    }

    public function scopeFilter($query, $filter) {
        $query
            ->when(data_get($filter, 'name'), fn($q, $name) => $q->where('name', 'LIKE', "%$name%"))
            ->when(data_get($filter, 'shop_section_id'), fn($q, $shopSectionId) => $q->where('shop_section_id', $shopSectionId))
            ->when(data_get($filter, 'chair_count_from'), fn($q, $countFrom) => $q->where('chair_count', $countFrom))
            ->when(data_get($filter, 'chair_count_to'), fn($q, $countTo) => $q->where('chair_count', $countTo))
            ->when(data_get($filter, 'free_from'), function ($query, $freeFrom) use ($filter) {

                $query->whereDoesntHave('users', function ($q) use ($freeFrom, $filter) {

                    $freeTo = data_get($filter, 'free_to');

                    $q
                        ->where('start_date', '>=', $freeFrom)
                        ->when($freeTo, fn($b) => $b->where('end_date', '<=', $freeTo))
                        ->when(data_get($filter, 'table_id'), fn($b, $tableId) => $b->where('table_id', $tableId));
                });

            })
            ->when(data_get($filter, 'date_from'), function ($query) use ($filter) {

                $minTime  = Settings::adminSettings()->where('key', 'min_reservation_time')->first()?->value;

                $dateFrom = date('Y-m-d H:i:01', strtotime(data_get($filter, 'date_from', now())));
                $dateTo   = date('Y-m-d H:i:59', strtotime(data_get($filter, 'date_to', $minTime ? "-$minTime hour" : now())));

                $query->whereHas('users', function ($q) use ($dateFrom, $dateTo, $filter) {
                    $q
                        ->where('start_date', '>=', $dateFrom)
                        ->when(data_get($filter, 'date_to'), fn($b) => $b->where('start_date', '<=', $dateTo));
                });

            })
            ->when(data_get($filter, 'shop_id'), function ($query, $shopId) {

                $query->whereHas('shopSection', function ($q) use ($shopId) {

                    $q->where('shop_id', $shopId);

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
