<?php

namespace App\Models;

use App\Traits\Loadable;
use Eloquent;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;

/**
 * App\Models\UserAddress
 *
 * @property int $id
 * @property string $title
 * @property int $user_id
 * @property array $address
 * @property array $location
 * @property bool $active
 * @property User|null $user
 * @property Order[]|Collection $orders
 * @property int $orders_count
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property Carbon|null $deleted_at
 * @method static Builder|self active()
 * @method static Builder|self filter(array $filter)
 * @method static Builder|self newModelQuery()
 * @method static Builder|self newQuery()
 * @method static Builder|self query()
 * @method static Builder|self whereId($value)
 * @mixin Eloquent
 */
class UserAddress extends Model
{
    use SoftDeletes, Loadable;

    public $guarded = ['id'];

    public $casts = [
        'address'   => 'array',
        'location'  => 'array',
        'active'    => 'bool',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class, 'address_id');
    }

    public function scopeFilter($query, $filter) {
        $query
            ->when(data_get($filter, 'user_id'), fn($q, $userId) => $q->where('user_id', $userId))
            ->when(data_get($filter, 'search'), function ($query, $search) {
                $query
                    ->where('title', 'LIKE', "%$search")
                    ->orWhere('address', 'LIKE', "%$search")
                    ->orWhere(function($query) use ($search) {

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
