<?php

namespace App\Services\WalletHistoryService;

use App\Helpers\ResponseError;
use App\Models\Payment;
use App\Models\Transaction;
use App\Models\User;
use App\Models\WalletHistory;
use App\Services\CoreService;
use DB;
use Illuminate\Support\Str;
use Throwable;

class WalletHistoryService extends CoreService
{
    protected function getModelClass(): string
    {
        return WalletHistory::class;
    }

	/**
	 * @param array $data
	 * @return array
	 * @throws Throwable
	 */
	public function create(array $data): array
    {
        if (!data_get($data, 'type') || !data_get($data, 'price') || !data_get($data, 'user')
        ) {
            return [
                'status'  => false,
                'code'    => ResponseError::ERROR_502,
                'message' => __('errors.' . ResponseError::TYPE_PRICE_USER, locale: $this->language)
            ];
        }

		$walletHistory = DB::transaction(function () use ($data) {
			/** @var User $user */
			$user = data_get($data, 'user');

			/** @var WalletHistory $walletHistory */
			$walletHistory = $this->model()->create([
				'uuid'          => Str::uuid(),
				'wallet_uuid'   => $user?->wallet?->uuid ?? data_get($user, 'wallet.uuid'),
				'type'          => data_get($data, 'type', 'withdraw'),
				'price'         => data_get($data, 'price'),
				'note'          => data_get($data, 'note'),
				'created_by'    => $user->id,
				'status'        => data_get($data, 'status', WalletHistory::PROCESSED),
			]);

			$transaction = $walletHistory->createTransaction([
				'price'                 => data_get($data, 'price'),
				'user_id'               => $user->id,
				'payment_sys_id'        => Payment::where('tag', 'wallet')->first()?->id,
				'payment_trx_id'        => $user->wallet?->id,
				'note'                  => $user->wallet?->id,
				'perform_time'          => now(),
				'status'                => Transaction::STATUS_PAID,
				'status_description'    => 'Transaction for wallet #' . $user->wallet?->id
			]);

			$walletHistory->update([
				'transaction_id' => $transaction->id,
			]);

			if (data_get($data, 'type') == 'topup') {

				$user->wallet()->increment('price', data_get($data, 'price'));

			} else if (data_get($data, 'type') == 'withdraw') {

				$user->wallet()->decrement('price', data_get($data, 'price'));

			}

			return $walletHistory;
		});

        return ['status' => true, 'code' => ResponseError::NO_ERROR, 'data' => $walletHistory];
    }

    public function changeStatus(string $uuid, string $status = null): array
    {
        /** @var WalletHistory $walletHistory */
        $walletHistory = $this->model()->firstWhere('uuid', $uuid);

        if (!$walletHistory) {
            return [
                'status'  => false,
                'code'    => ResponseError::ERROR_404,
                'message' => __('errors.' . ResponseError::ERROR_404, locale: $this->language)
            ];
        }

        if ($walletHistory->status === WalletHistory::PROCESSED) {

            $isCancel = $status === WalletHistory::REJECTED || $status === WalletHistory::CANCELED;

            $walletHistory->update([
                'status' => $status,
                'price' => $isCancel ? $walletHistory->wallet->price + $walletHistory->price : $walletHistory->price
            ]);

        }

        return ['status' => true, 'code' => ResponseError::NO_ERROR];
    }
}
