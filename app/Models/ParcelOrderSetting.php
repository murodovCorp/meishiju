<?php

namespace App\Models;

use App\Traits\Loadable;
use Eloquent;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/**
 * App\Models\ParcelOrderSetting
 *
 * @property int $id
 * @property string $type
 * @property string $img
 * @property int $min_width
 * @property int $min_height
 * @property int $min_length
 * @property int $max_width
 * @property int $max_height
 * @property int $max_length
 * @property int $max_range
 * @property int $min_g
 * @property int $max_g
 * @property int $price
 * @property int $price_per_km
 * @property int $special
 * @property int $special_price
 * @property int $special_price_per_km
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property Collection|ParcelOption[] $parcelOptions
 * @method static Builder|self filter($value)
 * @method static Builder|self newModelQuery()
 * @method static Builder|self newQuery()
 * @method static Builder|self query()
 * @method static Builder|self whereCreatedAt($value)
 * @method static Builder|self whereUpdatedAt($value)
 * @method static Builder|self whereId($value)
 * @mixin Eloquent
 */
class ParcelOrderSetting extends Model
{
    use Loadable;

    protected $guarded = ['id'];
    protected $casts   = [
        'special' => 'boolean'
    ];

    /**
     * @return BelongsToMany
     */
    public function parcelOptions(): BelongsToMany
    {
        return $this->belongsToMany(ParcelOption::class, 'parcel_setting_options');
    }

    /**
     * @param $query
     * @param $filter
     * @return void
     */
    public function scopeFilter($query, $filter): void
    {
        $query
            ->when(data_get($filter, 'min_width'),  fn($q, $value) => $q->where('min_width',  '>=', $value))
            ->when(data_get($filter, 'min_height'), fn($q, $value) => $q->where('min_height', '>=', $value))
            ->when(data_get($filter, 'min_length'), fn($q, $value) => $q->where('min_length', '>=', $value))
            ->when(data_get($filter, 'min_range'),  fn($q, $value) => $q->where('max_range',  '<=', $value))

            ->when(data_get($filter, 'max_width'),  fn($q, $value) => $q->where('max_width',  '<=', $value))
            ->when(data_get($filter, 'max_height'), fn($q, $value) => $q->where('max_height', '<=', $value))
            ->when(data_get($filter, 'max_length'), fn($q, $value) => $q->where('max_length', '<=', $value))
            ->when(data_get($filter, 'max_range'),  fn($q, $value) => $q->where('max_range',  '<=', $value))
            ->when(data_get($filter, 'min_g'),      fn($q, $value) => $q->where('min_g',      '>=', $value))
            ->when(data_get($filter, 'max_g'),      fn($q, $value) => $q->where('max_g',      '<=', $value))
            ->when(isset($filter['special']),           fn($q)          => $q->where('special', $filter['special']))
            ->when(data_get($filter, 'price_from'), function ($q, $from) use ($filter) {
                $q
                    ->where('price', '>=', $from)
                    ->where('price', '<=', data_get($filter, 'price_to'));
            })
            ->when(data_get($filter, 'special_price_from'), function ($q, $from) use ($filter) {
                $q
                    ->where('price', '>=', $from)
                    ->where('price', '<=', data_get($filter, 'special_price_to'));
            });
    }
}
