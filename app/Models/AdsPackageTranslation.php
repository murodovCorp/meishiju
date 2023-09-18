<?php

namespace App\Models;

use Eloquent;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;

/**
 * App\Models\AdsPackageTranslation
 *
 * @property int $id
 * @property int $ads_package_id
 * @property string $locale
 * @property string $title
 * @property Carbon|null $deleted_at
 * @property AdsPackage|null adsPackage
 * @method static Builder|self newModelQuery()
 * @method static Builder|self newQuery()
 * @method static Builder|self query()
 * @method static Builder|self whereId($value)
 * @method static Builder|self whereAreaId($value)
 * @method static Builder|self whereTitle($value)
 * @mixin Eloquent
 */
class AdsPackageTranslation extends Model
{
    use SoftDeletes;

    public $timestamps = false;

    protected $guarded = ['id'];

    public function adsPackage(): BelongsTo
    {
        return $this->belongsTo(AdsPackage::class);
    }
}
