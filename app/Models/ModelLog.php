<?php

namespace App\Models;

use Eloquent;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;
use Illuminate\Database\Eloquent\Model;
use Str;

/**
 * App\Models\ModelLog
 *
 * @property int $id
 * @property string $model_type
 * @property int $model_id
 * @property array $data
 * @property string $type
 * @property int $created_by
 * @property Carbon|null $created_at
 * @method static Builder|self newModelQuery()
 * @method static Builder|self newQuery()
 * @method static Builder|self query()
 * @method static Builder|self filter(array $filter)
 * @method static Builder|self whereCreatedAt($value)
 * @method static Builder|self whereId($value)
 * @property-read User|null $createdBy
 * @property-read Model|null $modelType
 * @mixin Eloquent
 */
class ModelLog extends Model
{
    protected $guarded = ['id'];

    public $timestamps = false;

    protected $casts = [
        'data' => 'array',
    ];

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function modelType(): BelongsTo
    {
        return $this->morphTo($this->model_type, 'model_type', 'model_id');
    }

    public function scopeFilter(Builder $query, array $filter) {

        $query->when(data_get($filter, 'model_type'), function (Builder $q, $modelType) {

            $modelName = 'App\\Models\\' . Str::ucfirst($modelType);

            $q->where('model_type', $modelName);

        })->when(data_get($filter, 'model_id'), function (Builder $q, $modelId) {

            $q->where('model_id', $modelId);

        })->when(data_get($filter, 'type') && data_get($filter, 'model_type'), function (Builder $q) use ($filter) {

            $q->where('type', data_get($filter, 'model_type') . '_' . data_get($filter, 'type'));

        })->when(data_get($filter, 'user_id'), function (Builder $q, $userId) {

            $q->where('created_by', $userId);

        })->when(data_get($filter, 'search'), function ($q, $search) {
            $q->whereHas('createdBy', function($query) use ($search) {

                $firstNameLastName = explode(' ', $search);

                if (data_get($firstNameLastName, 1)) {
                    return $query
                        ->where('firstname',  'LIKE', '%' . $firstNameLastName[0] . '%')
                        ->orWhere('lastname',   'LIKE', '%' . $firstNameLastName[1] . '%');
                }

                return $query
                    ->where('id',           'LIKE', "%$search%")
                    ->orWhere('firstname',  'LIKE', "%$search%")
                    ->orWhere('lastname',   'LIKE', "%$search%")
                    ->orWhere('email',      'LIKE', "%$search%")
                    ->orWhere('phone',      'LIKE', "%$search%");
            });
        });

    }
}
