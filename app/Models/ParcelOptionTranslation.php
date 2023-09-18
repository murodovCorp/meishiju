<?php

namespace App\Models;

use Eloquent;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * App\Models\ParcelOptionTranslation
 *
 * @property int $id
 * @property integer $parcel_option_id
 * @property string $locale
 * @property string $title
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @method static Builder|self filter($value)
 * @method static Builder|self newModelQuery()
 * @method static Builder|self newQuery()
 * @method static Builder|self query()
 * @method static Builder|self whereCreatedAt($value)
 * @method static Builder|self whereUpdatedAt($value)
 * @method static Builder|self whereId($value)
 * @mixin Eloquent
 */
class ParcelOptionTranslation extends Model
{
    protected $guarded = ['id'];
    public $timestamps = false;
}
