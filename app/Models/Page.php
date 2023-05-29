<?php

namespace App\Models;

use App\Traits\Loadable;
use Eloquent;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/**
 * App\Models\Page
 *
 * @property int $id
 * @property boolean $active
 * @property string $type
 * @property string $img
 * @property string $bg_img
 * @property array $buttons
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property Carbon|null $deleted_at
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
class Page extends Model
{
    use SoftDeletes, Loadable;

    public $guarded = ['id'];

    public $casts = [
        'active'    => 'bool',
        'buttons'   => 'array',
    ];

    public const ALL_ABOUT      = 'all_about'; // DONT ADD THIS CONT IN TYPES. IT`S ONLY FOR FILTER

    public const DELIVERY       = 'delivery';
    public const ABOUT          = 'about';
    public const ABOUT_SECOND   = 'about_second';
    public const ABOUT_THREE    = 'about_three';
    public const ABOUT_FOURTH   = 'about_fourth';

    public const TYPES = [
        self::DELIVERY      => self::DELIVERY,
        self::ABOUT         => self::ABOUT,
        self::ABOUT_SECOND  => self::ABOUT_SECOND,
        self::ABOUT_THREE   => self::ABOUT_THREE,
        self::ABOUT_FOURTH  => self::ABOUT_FOURTH,
    ];

    public function translations(): HasMany
    {
        return $this->hasMany(PageTranslation::class);
    }

    public function translation(): HasOne
    {
        return $this->hasOne(PageTranslation::class);
    }

    public function scopeActive($query): Builder
    {
        /** @var Bonus $query */
        return $query->where('status', true);
    }

    public function scopeFilter($query, $filter) {
        $query
            ->when(data_get($filter, 'type'), fn($q, $type) => $q->where('type', $type))
            ->when(data_get($filter, 'types'), fn($q, $types) => $q->whereIn('type', $types))
            ->when(isset($filter['active']), fn($q) => $q->where('active', $filter['active']))
            ->when(data_get($filter, 'search'), function ($query, $search) {
                $query->whereHas('translations', function ($q) use ($search) {
                    $q->where('title', 'LIKE', "%$search%")->select('id', 'career_id', 'locale', 'title');
                });
            });
    }
}
