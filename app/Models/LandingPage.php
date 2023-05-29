<?php

namespace App\Models;

use App\Traits\Loadable;
use Eloquent;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;

/**
 * App\Models\LandingPage
 *
 * @property int $id
 * @property array $data
 * @property string $type
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property string|null $deleted_at
 * @method static Builder|self filter($array)
 * @method static Builder|self newModelQuery()
 * @method static Builder|self newQuery()
 * @method static Builder|self query()
 * @method static Builder|self updatedDate($updatedDate)
 * @method static Builder|self whereActive($value)
 * @method static Builder|self whereCreatedAt($value)
 * @method static Builder|self whereDeletedAt($value)
 * @method static Builder|self whereId($value)
 * @method static Builder|self whereUpdatedAt($value)
 * @mixin Eloquent
 */
class LandingPage extends Model
{
    use Loadable, SoftDeletes;

    protected $guarded = ['id'];

    protected $casts = [
        'data' => 'array',
    ];

    const WELCOME = 'welcome';

    const TYPES = [
        self::WELCOME => self::WELCOME
    ];

    /* Filter Scope */
    public function scopeFilter($value, $filter)
    {
        return $value->when(data_get($filter, 'type'), function ($query, $type) {

            $type = data_get(self::TYPES, $type, self::WELCOME);

            $query->where('type', $type);

        });
    }

}
