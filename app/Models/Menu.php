<?php

namespace App\Models;

use Eloquent;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;

/**
 * App\Models\Menu
 *
 * @property int $id
 * @property int $category_id
 * @property int $shop_id
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property Carbon|null $deleted_at
 * @property-read Category|null $category
 * @property-read Shop|null $shop
 * @property-read Collection|Product[] $products
 * @property-read MenuTranslation|null $translation
 * @property-read Collection|MenuTranslation[] $translations
 * @property-read int|null $translations_count
 * @method static Builder|self filter($array)
 * @method static Builder|self newModelQuery()
 * @method static Builder|self newQuery()
 * @method static Builder|self onlyTrashed()
 * @method static Builder|self query()
 * @method static Builder|self whereCategoryId($value)
 * @method static Builder|self whereCreatedAt($value)
 * @method static Builder|self whereDeletedAt($value)
 * @method static Builder|self whereId($value)
 * @method static Builder|self whereUpdatedAt($value)
 * @method static Builder|self withTrashed()
 * @method static Builder|self withoutTrashed()
 * @mixin Eloquent
 */
class Menu extends Model
{
    use SoftDeletes;

    protected $guarded = ['id'];

    // Translations
    public function translations(): HasMany
    {
        return $this->hasMany(MenuTranslation::class);
    }

    public function translation(): HasOne
    {
        return $this->hasOne(MenuTranslation::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function shop(): BelongsTo
    {
        return $this->belongsTo(Shop::class);
    }

    public function products(): BelongsToMany
    {
        return $this->belongsToMany(Product::class, MenuProduct::class);
    }

    public function scopeFilter($query, $array)
    {

    }
}
