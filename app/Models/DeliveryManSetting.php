<?php

namespace App\Models;

use App\Traits\Loadable;
use Database\Factories\DeliveryManSettingFactory;
use Eloquent;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;

/**
 * App\Models\DeliveryManSetting
 *
 * @property int $id
 * @property int $user_id
 * @property string $type_of_technique
 * @property string $brand
 * @property string $model
 * @property string $number
 * @property string $color
 * @property boolean $online
 * @property array $location
 * @property integer|null $width
 * @property integer|null $height
 * @property integer|null $length
 * @property integer|null $kg
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property Carbon|null $deleted_at
 * @property-read User|null $deliveryMan
 * @property-read Collection|Gallery[] $galleries
 * @property-read int|null $galleries_count
 * @method static DeliveryManSettingFactory factory(...$parameters)
 * @method static Builder|self newModelQuery()
 * @method static Builder|self newQuery()
 * @method static Builder|self query()
 * @method static Builder|self whereUserId($value)
 * @method static Builder|self whereTypeOfTechnique($value)
 * @method static Builder|self whereBrand($value)
 * @method static Builder|self whereModel($value)
 * @method static Builder|self whereNumber($value)
 * @method static Builder|self whereColor($value)
 * @method static Builder|self whereOnline($value)
 * @mixin Eloquent
 */
class DeliveryManSetting extends Model
{
    use HasFactory, Loadable, SoftDeletes;

    protected $guarded  = ['id'];

    protected $table    = 'deliveryman_settings';

    protected $casts    = ['location' => 'array'];

    const BENZINE       = 'benzine';
    const ELECTRIC      = 'electric';
    const DIESEL        = 'diesel';
    const GAS           = 'gas';
    const MOTORBIKE     = 'motorbike';
    const BIKE          = 'bike';
    const FOOT          = 'foot';
    const HYBRID        = 'hybrid';

    const TYPE_OF_TECHNIQUES = [
        self::BENZINE       => self::BENZINE,
        self::DIESEL        => self::DIESEL,
        self::ELECTRIC      => self::ELECTRIC,
        self::GAS           => self::GAS,
        self::MOTORBIKE     => self::MOTORBIKE,
        self::BIKE          => self::BIKE,
        self::FOOT          => self::FOOT,
        self::HYBRID        => self::HYBRID,
    ];

    public function deliveryMan(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
