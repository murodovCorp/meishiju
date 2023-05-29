<?php

namespace App\Models;

use Eloquent;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * App\Models\CareerTranslation
 *
 * @property int $id
 * @property int $career_id
 * @property string $locale
 * @property string $title
 * @property string|null $description
 * @property array|null $address
 * @method static Builder|self newModelQuery()
 * @method static Builder|self newQuery()
 * @method static Builder|self query()
 * @method static Builder|self whereAddress($value)
 * @method static Builder|self whereDescription($value)
 * @method static Builder|self whereId($value)
 * @method static Builder|self whereLocale($value)
 * @method static Builder|self whereCareerId($value)
 * @method static Builder|self whereTitle($value)
 * @mixin Eloquent
 */
class CareerTranslation extends Model
{
    use SoftDeletes;

    public $timestamps = false;

    protected $guarded = ['id'];

    public $casts = [
        'address' => 'array'
    ];
}
