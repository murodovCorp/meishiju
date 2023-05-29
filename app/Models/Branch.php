<?php

namespace App\Models;

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
 * App\Models\Branch
 *
 * @property int $id
 * @property int $shop_id
 * @property int $address
 * @property int $location
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property Carbon|null $deleted_at
 * @property Shop|null $shop
 * @property Collection|BranchTranslation[] $translations
 * @property BranchTranslation|null $translation
 * @property int|null $translations_count
 * @method static Builder|self filter(array $filter)
 * @method static Builder|self newModelQuery()
 * @method static Builder|self newQuery()
 * @method static Builder|self query()
 * @method static Builder|self whereId($value)
 * @mixin Eloquent
 */
class Branch extends Model
{
    use SoftDeletes;

    public $guarded = ['id'];

    public $casts = [
        'address'   => 'array',
        'location'  => 'array',
    ];

    public function translations(): HasMany
    {
        return $this->hasMany(BranchTranslation::class);
    }

    public function translation(): HasOne
    {
        return $this->hasOne(BranchTranslation::class);
    }

    public function shop(): BelongsTo
    {
        return $this->belongsTo(Shop::class);
    }

    public function scopeFilter($query, $filter) {
        $query
            ->when(data_get($filter, 'shop_id'), fn($q, $shopId) => $q->where('shop_id', $shopId))
            ->when(data_get($filter, 'search'), function ($query, $search) {
                $query->whereHas('translations', function ($q) use ($search) {
                    $q->where('title', 'LIKE', "%$search%")->select('id', 'branch_id', 'locale', 'title');
                });
            });
    }
}
