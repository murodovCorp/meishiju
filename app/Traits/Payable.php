<?php

namespace App\Traits;

use App\Models\Transaction;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphMany;

trait Payable
{
    public function createTransaction(array $data): Model
    {
        return $this->transactions()->updateOrCreate(['payable_id' => $this->id], [
            'price'                 => data_get($data, 'price') ?? $this->transaction?->price,
            'user_id'               => data_get($data, 'user_id', auth('sanctum')->id()),
            'payment_sys_id'        => data_get($data, 'payment_sys_id') ?? $this->transaction?->payment_sys_id,
            'payment_trx_id'        => data_get($data, 'payment_trx_id') ?? $this->transaction?->payment_trx_id,
            'note'                  => data_get($data, 'note') ?? $this->transaction?->note,
            'perform_time'          => data_get($data, 'perform_time', now()),
            'status_description'    => data_get($data, 'status_description') ?? $this->transaction?->status ?? 'Transaction in progress',
            'status'                => data_get($data, 'status', $this->transaction?->status ?? Transaction::STATUS_PROGRESS),
        ]);
    }

    public function transactions(): MorphMany
    {
        return $this->morphMany(Transaction::class, 'payable');
    }
}
