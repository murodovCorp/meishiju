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
 * App\Models\Career
 *
 * @property int $id
 * @property int $category_id
 * @property array $location
 * @property boolean $active
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property Carbon|null $deleted_at
 * @property Category $category
 * @property Collection|CareerTranslation[] $translations
 * @property CareerTranslation|null $translation
 * @property int|null $translations_count
 * @method static Builder|self active()
 * @method static Builder|self filter(array $filter)
 * @method static Builder|self newModelQuery()
 * @method static Builder|self newQuery()
 * @method static Builder|self query()
 * @method static Builder|self whereId($value)
 * @mixin Eloquent
 */
class Career extends Model
{
    use SoftDeletes;

    public $guarded = ['id'];

    public $casts = [
        'active'    => 'bool',
        'location'  => 'array'
    ];

    public function translations(): HasMany
    {
        return $this->hasMany(CareerTranslation::class);
    }

    public function translation(): HasOne
    {
        return $this->hasOne(CareerTranslation::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function scopeActive($query): Builder
    {
        /** @var Bonus $query */
        return $query->where('status', true);
    }

    public function scopeFilter($query, $filter) {
        $query
            ->when(data_get($filter, 'category_id'), fn($q, $categoryId) => $q->where('category_id', $categoryId))
            ->when(isset($filter['active']), fn($q) => $q->where('active', $filter['active']))
            ->when(data_get($filter, 'search'), function ($query, $search) {
                $query->whereHas('translations', function ($q) use ($search) {
                    $q->where('title', 'LIKE', "%$search%")->select('id', 'career_id', 'locale', 'title');
                });
            });
    }
}
