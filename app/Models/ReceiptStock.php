<?php

namespace App\Models;

use Eloquent;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;

/**
 * App\Models\ReceiptStock
 *
 * @property int $receipt_id
 * @property int $stock_id
 * @property int $min_quantity
 * @property Receipt|null $receipt
 * @property Stock|null $stock
 * @property Carbon|null $deleted_at
 * @method static Builder|ReceiptStock newModelQuery()
 * @method static Builder|ReceiptStock newQuery()
 * @method static Builder|ReceiptStock query()
 * @method static Builder|ReceiptStock whereCreatedAt($value)
 * @method static Builder|ReceiptStock whereId($value)
 * @method static Builder|ReceiptStock whereUpdatedAt($value)
 * @mixin Eloquent
 */
class ReceiptStock extends Model
{
    use SoftDeletes;

    public $guarded = ['id'];
    public $timestamps = false;

    public function receipt(): BelongsTo
    {
        return $this->belongsTo(Receipt::class);
    }

    public function stock(): BelongsTo
    {
        return $this->belongsTo(Stock::class);
    }
}
