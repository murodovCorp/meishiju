<?php

namespace App\Models;

use Eloquent;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Carbon;

/**
 * App\Models\ParcelOption
 *
 * @property int $id
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read ParcelOptionTranslation|null $translation
 * @property-read Collection|ParcelOptionTranslation[] $translations
 * @property-read int|null $translations_count
 * @method static Builder|self filter($value)
 * @method static Builder|self newModelQuery()
 * @method static Builder|self newQuery()
 * @method static Builder|self query()
 * @method static Builder|self whereCreatedAt($value)
 * @method static Builder|self whereUpdatedAt($value)
 * @method static Builder|self whereId($value)
 * @mixin Eloquent
 */
class ParcelOption extends Model
{
    protected $guarded = ['id'];

    // Translations
    public function translations(): HasMany
    {
        return $this->hasMany(ParcelOptionTranslation::class);
    }

    public function translation(): HasOne
    {
        return $this->hasOne(ParcelOptionTranslation::class);
    }

    public function scopeFilter($query, $filter) {
        $query->when(data_get($filter, 'search'), function ($query, $search) {
            $query->where(function ($query) use ($search) {
                $query
                    ->where('id', 'LIKE', "%$search%")
                    ->orWhereHas('translations', function ($q) use ($search) {
                        $q
                            ->where('title', 'LIKE', "%$search%")
                            ->select('id', 'parcel_option_id', 'locale', 'title');
                    });
            });
        });
    }
}
