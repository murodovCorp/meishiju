<?php

namespace App\Models\Booking;

use App\Models\User;
use Carbon\Carbon;
use Eloquent;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * App\Models\UserBooking
 *
 * @property int $id
 * @property int|null $booking_id
 * @property int|null $user_id
 * @property int|null $table_id
 * @property string $status
 * @property string|null $note
 * @property int|null $guest
 * @property Carbon|null $start_date
 * @property Carbon|null $end_date
 * @property Carbon|null $deleted_at
 * @property Booking|null $booking
 * @property User|null $user
 * @property Table|null $table
 * @method static Builder|self newModelQuery()
 * @method static Builder|self newQuery()
 * @method static Builder|self query()
 * @method static Builder|self filter($filter)
 * @mixin Eloquent
 */
class UserBooking extends Model
{
    use SoftDeletes;

    protected $guarded = ['id'];

    public $timestamps = false;

    const NEW       = 'new';
    const ACCEPTED  = 'accepted';
    const CANCELED  = 'canceled';

    const STATUSES = [
        self::NEW       => self::NEW,
        self::ACCEPTED  => self::ACCEPTED,
        self::CANCELED  => self::CANCELED,
    ];

    public function booking(): BelongsTo
    {
        return $this->belongsTo(Booking::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function table(): BelongsTo
    {
        return $this->belongsTo(Table::class);
    }

    public function scopeFilter($query, $filter) {

        $bookingId  = data_get($filter, 'booking_id');
        $shopId     = data_get($filter, 'shop_id');
        $userId     = data_get($filter, 'user_id');
        $tableId    = data_get($filter, 'table_id');
        $status     = data_get($filter, 'status');
        $startFrom  = data_get($filter, 'start_from');
        $startTo    = data_get($filter, 'start_to');
        $endFrom    = data_get($filter, 'end_from');
        $endTo      = data_get($filter, 'end_to');
        $sectionId  = data_get($filter, 'shop_section_id');

        $query
			->when(isset($filter['deleted_at']), fn($q) => $q->onlyTrashed())
			->when($sectionId,  fn($q, $id)        => $q->whereHas('table', fn($q) => $q->where('shop_section_id', $id)))
            ->when($bookingId,  fn($q, $bookingId) => $q->where('booking_id', $bookingId))
            ->when($shopId,     fn($q, $shopId)    => $q->whereHas('booking', fn($b) => $b->where('shop_id', $shopId)))
            ->when($userId,     fn($q, $userId)    => $q->where('user_id', $userId))
            ->when($tableId,    fn($q, $tableId)   => $q->where('table_id', $tableId))
            ->when($status,     fn($q, $status)    => $q->where('status', $status))
            ->when($startFrom,  fn($q, $startFrom) => $q->where('start_date', '>=', $startFrom))
            ->when($startTo,    fn($q, $startTo)   => $q->where('start_date', '<=', $startTo))
            ->when($endFrom,    fn($q, $endFrom)   => $q->where('end_date', '>=', $endFrom))
            ->when($endTo,      fn($q, $endTo)     => $q->where('end_date', '<=', $endTo));
    }
}
