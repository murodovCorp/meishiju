<?php

namespace App\Models;

use App\Traits\Payable;
use Eloquent;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;

/**
 * App\Models\PaymentToPartner
 *
 * @property int $id
 * @property int $user_id
 * @property int $order_id
 * @property string $type
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property Carbon|null $deleted_at
 * @property-read User|null $user
 * @property-read Order|null $order
 * @method static Builder|self newModelQuery()
 * @method static Builder|self newQuery()
 * @method static Builder|self query()
 * @method static Builder|self whereCreatedAt($value)
 * @method static Builder|self whereUpdatedAt($value)
 * @method static Builder|self whereDeletedAt($value)
 * @method static Builder|self whereId($value)
 * @mixin Eloquent
 */
class PaymentToPartner extends Model
{
    use HasFactory, SoftDeletes, Payable;

    protected $guarded = ['id'];

	const SELLER		= 'seller';
	const DELIVERYMAN 	= 'deliveryman';

	const TYPES = [
		self::SELLER 		=> self::SELLER,
		self::DELIVERYMAN 	=> self::DELIVERYMAN,
	];

    /**
     * @return BelongsTo
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return BelongsTo
     */
    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

	public function scopeFilter($query, array $filter) {
		$query
			->when(data_get($filter, 'order_id'),  fn($q, $orderId) => $q->where('order_id', $orderId))
			->when(data_get($filter, 'shop_id'),   fn($q, $shopId)  => $q->whereHas('order', fn($q) => $q->where('shop_id', $shopId)))
			->when(data_get($filter, 'user_id'),   fn($q, $userId)  => $q->where('user_id', $userId))
			->when(data_get($filter, 'type'), 	   fn($q, $type)    => $q->where('type', $type))
            ->when(data_get($filter, 'date_from'), function ($query, $dateFrom) use ($filter) {

                $dateFrom = date('Y-m-d', strtotime($dateFrom));
                $dateTo   = data_get($filter, 'date_to', date('Y-m-d'));

                $dateTo = date('Y-m-d', strtotime($dateTo));

                $query->whereDate('created_at', '>=', $dateFrom)->whereDate('created_at', '<=', $dateTo);
            });
	}
}
