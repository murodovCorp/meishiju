<?php

namespace App\Models;

use Eloquent;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Carbon;

/**
 * App\Models\RequestModel
 *
 * @property int $id
 * @property int $model_id
 * @property string $model_type
 * @property array $data
 * @property int $created_by
 * @property string $status
 * @property string $status_note
 * @property Category|Product|User $model
 * @property User $createdBy
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @method static Builder|self active()
 * @method static Builder|self filter(array $filter)
 * @method static Builder|self newModelQuery()
 * @method static Builder|self newQuery()
 * @method static Builder|self query()
 * @method static Builder|self whereId($value)
 * @mixin Eloquent
 */
class RequestModel extends Model
{
    public $guarded = ['id'];

	public $casts = [
		'created_by' => 'int',
		'data'		 => 'array',
		'model_id'	 => 'int',
		'model_type' => 'string',
	];

	const CATEGORY 	= 'category';
	const PRODUCT  	= 'product';
	const USER  	= 'user';

	const STATUS_PENDING   = 'pending';
	const STATUS_APPROVED  = 'approved';
	const STATUS_CANCELED  = 'canceled';

	const TYPES = [
		self::CATEGORY 	=> Category::class,
		self::PRODUCT	=> Product::class,
		self::USER  	=> User::class,
	];

	const BY_TYPES = [
		Category::class	=> self::CATEGORY,
		Product::class 	=> self::PRODUCT,
		User::class 	=> self::USER,
	];

	const STATUSES = [
		self::STATUS_PENDING  => self::STATUS_PENDING,
		self::STATUS_APPROVED => self::STATUS_APPROVED,
		self::STATUS_CANCELED => self::STATUS_CANCELED,
	];

    public function createdBy(): BelongsTo
	{
        return $this->belongsTo(User::class, 'created_by');
    }

    public function model(): MorphTo
	{
        return $this->morphTo('model');
    }

    public function scopeFilter($query, array $filter) {
        $query
			->when(data_get($filter, 'created_by'), fn($q, $id) => $q->where('created_by', $id))
			->when(data_get($filter, 'status'), fn($q, $status) => $q->where('status', $status))
			->when(data_get($filter, 'type'), function ($q, $type) {
				$q->where('model_type', data_get(self::TYPES, $type, 'category'));
			});
    }

}
