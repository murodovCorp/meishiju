<?php

namespace App\Models;

use Eloquent;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * App\Models\MenuTranslation
 *
 * @property int $id
 * @property int $menu_id
 * @property string $locale
 * @property string $title
 * @property string|null $description
 * @method static Builder|self newModelQuery()
 * @method static Builder|self newQuery()
 * @method static Builder|self query()
 * @method static Builder|self whereDescription($value)
 * @method static Builder|self whereId($value)
 * @method static Builder|self whereLocale($value)
 * @method static Builder|self whereMenuId($value)
 * @method static Builder|self whereTitle($value)
 * @mixin Eloquent
 */
class MenuTranslation extends Model
{
    use SoftDeletes;

    protected $guarded = ['id'];

    public $timestamps = false;
}
