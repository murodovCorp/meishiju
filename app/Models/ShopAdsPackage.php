<?php

namespace App\Models;

use App\Traits\Payable;
use App\Traits\SetCurrency;
use Eloquent;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;

/**
 * App\Models\ShopAdsPackage
 *
 * @property int $id
 * @property boolean $active
 * @property int $ads_package_id
 * @property int $position_page
 * @property int $shop_id
 * @property int $banner_id
 * @property string $status
 * @property Carbon|null $expired_at
 * @property AdsPackage|null $adsPackage
 * @property Shop|null $shop
 * @property Banner|null $banner
 * @property Carbon|null $deleted_at
 * @method static Builder|self active()
 * @method static Builder|self filter(array $filter)
 * @method static Builder|self newModelQuery()
 * @method static Builder|self newQuery()
 * @method static Builder|self query()
 * @method static Builder|self whereId($value)
 * @mixin Eloquent
 */
class ShopAdsPackage extends Model
{
    use SoftDeletes, SetCurrency, Payable;

    public $guarded     = ['id'];
    public $timestamps  = false;

    protected $casts    = [
        'expired_at' => 'datetime'
    ];

    const NEW       = 'new';
    const APPROVED  = 'approved';
    const CANCELED  = 'canceled';

    const STATUSES  = [
        self::NEW       => self::NEW,
        self::APPROVED  => self::APPROVED,
        self::CANCELED  => self::CANCELED,
    ];

    public function shop(): BelongsTo
    {
        return $this->belongsTo(Shop::class);
    }

    public function banner(): BelongsTo
    {
        return $this->belongsTo(Banner::class);
    }

    public function adsPackage(): BelongsTo
    {
        return $this->belongsTo(AdsPackage::class);
    }

    public function scopeActive($query): Builder
    {
        return $query->where('active', true);
    }

    public function scopeFilter($query, array $filter) {
        $query
            ->when(data_get($filter, 'ads_package_id'), fn($q, $id)   => $q->where('ads_package_id', $id))
            ->when(data_get($filter, 'shop_id'),        fn($q, $id)   => $q->where('shop_id', $id))
            ->when(data_get($filter, 'position_page'),  fn($q, $page) => $q->where('position_page', $page))
            ->when(isset($filter['active']),                fn($q)        => $q->where('active', $filter['active']))
            ->when(data_get($filter, 'search'), function ($query, $search) {
                $query->whereHas('adsPackage', function ($q) use ($search) {
                    $q->whereHas('translations', function ($q) use ($search) {
                        $q->where('title', 'LIKE', "%$search%");
                    });
                });
            });
    }
}
