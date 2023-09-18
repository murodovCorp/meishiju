<?php

namespace App\Models;

use App\Traits\Loadable;
use Database\Factories\ReviewFactory;
use Eloquent;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;

/**
 * App\Models\Review
 *
 * @property int $id
 * @property string $reviewable_type
 * @property int $reviewable_id
 * @property string $assignable_type
 * @property int $assignable_id
 * @property int $user_id
 * @property float $rating
 * @property string|null $comment
 * @property string|null $img
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property Carbon|null $deleted_at
 * @property-read Collection|Gallery[] $galleries
 * @property-read int|null $galleries_count
 * @property-read Model|Eloquent $reviewable
 * @property-read Model|Eloquent $assignable
 * @property-read User $user
 * @method static ReviewFactory factory(...$parameters)
 * @method static Builder|self newModelQuery()
 * @method static Builder|self newQuery()
 * @method static Builder|self query()
 * @method static Builder|self whereComment($value)
 * @method static Builder|self whereCreatedAt($value)
 * @method static Builder|self whereId($value)
 * @method static Builder|self whereImg($value)
 * @method static Builder|self whereRating($value)
 * @method static Builder|self whereReviewableId($value)
 * @method static Builder|self whereReviewableType($value)
 * @method static Builder|self whereUpdatedAt($value)
 * @method static Builder|self whereUserId($value)
 * @mixin Eloquent
 */
class Review extends Model
{
    use HasFactory, Loadable, SoftDeletes;

    protected $guarded = ['id'];

    const REVIEW_TYPES = [
        'shop',
        'blog',
        'order',
        'parcel',
        'product',
    ];

    const ASSIGN_TYPES = [
        'shop',
        'user',
    ];

    public function reviewable(): MorphTo
    {
        return $this->morphTo('reviewable');
    }

    public function assignable(): MorphTo
    {
        return $this->morphTo('assignable');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

}
