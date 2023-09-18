<?php

namespace App\Models;

use App\Traits\Loadable;
use App\Traits\SetCurrency;
use Eloquent;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/**
 * App\Models\AdsPackage
 *
 * @property int $id
 * @property boolean $active
 * @property string $time_type
 * @property int $time
 * @property double $price
 * @property int $banner_id
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property Carbon|null $deleted_at
 * @property Collection|AdsPackageTranslation[] $translations
 * @property AdsPackageTranslation|null $translation
 * @property Banner|null $banner
 * @property ShopAdsPackage|null $shopAdsPackage
 * @property Collection|ShopAdsPackage[] $shopAdsPackages
 * @property int|null $translations_count
 * @method static Builder|self active()
 * @method static Builder|self filter(array $filter)
 * @method static Builder|self newModelQuery()
 * @method static Builder|self newQuery()
 * @method static Builder|self query()
 * @method static Builder|self whereId($value)
 * @mixin Eloquent
 */
class AdsPackage extends Model
{
    use SoftDeletes, SetCurrency, Loadable;

    public $guarded = ['id'];

    // Time type
    const MINUTE = 'minute';
    const HOUR   = 'hour';
    const DAY    = 'day';
    const WEEK   = 'week';
    const MONTH  = 'month';
    const YEAR   = 'year';

    const TIME_TYPES = [
        self::MINUTE => self::MINUTE,
        self::HOUR   => self::HOUR,
        self::DAY    => self::DAY,
        self::WEEK   => self::WEEK,
        self::MONTH  => self::MONTH,
        self::YEAR   => self::YEAR,
    ];

    public function translations(): HasMany
    {
        return $this->hasMany(AdsPackageTranslation::class);
    }

    public function translation(): HasOne
    {
        return $this->hasOne(AdsPackageTranslation::class);
    }

    public function banner(): BelongsTo
    {
        return $this->belongsTo(Banner::class);
    }

    public function shopAdsPackage(): HasOne
    {
        return $this->hasOne(ShopAdsPackage::class);
    }

    public function shopAdsPackages(): HasMany
    {
        return $this->hasMany(ShopAdsPackage::class);
    }

    public function scopeActive($query): Builder
    {
        /** @var AdsPackage $query */
        return $query->where('active', true);
    }

    public function scopeFilter($query, array $filter) {
        $query
            ->when(isset($filter['active']), fn($q) => $q->where('active', $filter['active']))
            ->when(data_get($filter, 'time_type'), fn($q, $timeType) => $q->where('time_type', $timeType))
            ->when(data_get($filter, 'time'), fn($q, $time) => $q->where('time', $time))
            ->when(data_get($filter, 'search'), function ($query, $search) {
                $query->whereHas('translations', function ($q) use ($search) {
                    $q->where('title', 'LIKE', "%$search%")->select('id', 'ads_package_id', 'locale', 'title');
                });
            })
            ->when(data_get($filter, 'price_from'), function ($query, $priceFrom) use ($filter) {
                $query
                    ->where('price', '>=', $priceFrom)
                    ->where('price', '<=', data_get($filter, 'price_to', 100000000));
            })
            ->when(data_get($filter, 'limit_from'), function ($query, $limitFrom) use ($filter) {
                $query
                    ->where('product_limit', '>=', $limitFrom)
                    ->where('product_limit', '<=', data_get($filter, 'limit_to', 1000000000));
            });
    }
}
