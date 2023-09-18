<?php

namespace App\Services\TransactionService;

use App\Helpers\ResponseError;
use App\Models\Order;
use App\Models\Payment;
use App\Models\ShopAdsPackage;
use App\Models\ShopSubscription;
use App\Models\Transaction;
use App\Models\User;
use App\Models\Wallet;
use App\Models\WalletHistory;
use App\Services\CoreService;
use App\Services\UserServices\UserWalletService;
use DB;
use Illuminate\Support\Str;
use Throwable;

class TransactionService extends CoreService
{
    protected function getModelClass(): string
    {
        return Transaction::class;
    }

    public function adsTransaction(int $id, $class = ShopAdsPackage::class): array
    {
        $model = $class::with([
            'adsPackage',
            'shop.seller',
        ])->find($id);

        if (empty($model)) {
            return ['status' => false, 'code' => ResponseError::ERROR_404];
        }

		$payment = Payment::where([
            'active' => 1,
            'tag'    => 'wallet'
        ])->first();

        if (!$payment) {
            return [
                'status'  => false,
                'code'    => ResponseError::ERROR_404,
                'message' => __('errors.' . ResponseError::ERROR_404, locale: $this->language)
            ];
        }

        /** @var User $user */
		/** @var ShopAdsPackage $model */

		$user = User::with('wallet')->find($model->shop->user_id);

        $price = $model->adsPackage->price;

		if (empty($user->wallet?->uuid)) {
			$user = (new UserWalletService)->create($user);
		}

        if ($user->wallet?->price <= $price) {
            return [
                'status'  => false,
                'code'    => empty($user->wallet?->uuid) ? ResponseError::ERROR_108 : ResponseError::ERROR_109,
                'message' => __('errors.' . ResponseError::ERROR_108, locale: $this->language)
            ];
        }

        $result = [
            'status'  => false,
            'code'    => ResponseError::ERROR_501,
            'message' => __('errors.' . ResponseError::ERROR_501, locale: $this->language)
        ];

        try {
            $result = DB::transaction(function () use ($model, $user, $payment, $price) {

                $user->wallet()->update([
                    'price' => $user->wallet->price - max($price, 0)
                ]);

                /** @var Transaction $transaction */
                $transaction = $model->createTransaction([
                    'price'                 => $model->adsPackage->price,
                    'user_id'               => $model->shop->user_id,
                    'payment_sys_id'        => $payment->id,
                    'payment_trx_id'        => $model->transaction?->id,
                    'note'                  => $model->id,
                    'perform_time'          => now(),
                    'status'                => Transaction::STATUS_PAID,
                    'status_description'    => 'Transaction for ads #' . $model->id
                ]);

                if (data_get($payment, 'wallet')) {
                    $this->walletHistoryAdd($model->shop->seller, $transaction, $model, 'Ads', 'withdraw');
                }

                return ['status' => true, 'code' => ResponseError::NO_ERROR, 'data' => $model];
            });
        } catch (Throwable $e) {

            $this->error($e);

            $result['message'] = $e->getMessage();
        }

        return $result;
    }

    public function orderTransaction(int $id, array $data, $class = Order::class): array
    {
        /** @var Order $order */
        $order = $class::with('user')->find($id);

        if (!$order) {
            return ['status' => false, 'code' => ResponseError::ERROR_404];
        }

        $payment = $this->checkPayment(data_get($data, 'payment_sys_id'), $order, true);

        if (!data_get($payment, 'status')) {
            return $payment;
        }

        if (data_get($payment, 'already_payed')) {
            return ['status' => true, 'code' => ResponseError::NO_ERROR, 'data' => $order];
        }

        $tag = data_get($payment, 'payment_tag');

        /** @var Transaction $transaction */
        $transaction = $order->createTransaction([
            'price'              => $order->total_price,
            'user_id'            => $order->user_id,
            'payment_sys_id'     => data_get($data, 'payment_sys_id'),
            'payment_trx_id'     => data_get($data, 'payment_trx_id'),
            'note'               => $order->id,
            'perform_time'       => now(),
            'status_description' => "Transaction for order #$order->id",
            'request'            => $tag === 'cash' ? Transaction::REQUEST_WAITING : null,
        ]);

        if (data_get($payment, 'wallet')) {

            $this->walletHistoryAdd($order->user, $transaction, $order);

        }

        return ['status' => true, 'code' => ResponseError::NO_ERROR, 'data' => $order];
    }

    /**
     * @throws Throwable
     */
    public function payDebit(User $user, Order $order)
    {
        try {

            if ($order->deliveryMan?->id) {
                $order->deliveryMan->increment('price', $order->total_price);
            }

            $user->wallet->decrement('price', $order->total_price);

            $data = [
                'price'                 => $order->total_price,
                'user_id'               => $order->user_id,
                'payment_sys_id'        => $order->transaction->payment_sys_id,
                'payment_trx_id'        => $order->transaction->payment_trx_id,
                'note'                  => $order->transaction->id,
                'perform_time'          => now(),
                'status_description'    => "Transaction for debit transaction #{$order->transaction->id}",
                'status'                => Transaction::STATUS_PAID
            ];

            $transaction = $order->createTransaction($data);

            $this->walletHistoryAdd($user, $transaction, $order);

            $order->transaction->update([
                'status' => Transaction::STATUS_PAID
            ]);

        } catch (Throwable $e) {
            $this->error($e);
        }
    }

    public function walletTransaction(int $id, array $data): array
    {
        $wallet = Wallet::find($id);

        if (empty($wallet)) {
            return ['status' => true, 'code' => ResponseError::ERROR_404];
        }

        $wallet->createTransaction([
            'price'                 => data_get($data, 'price'),
            'user_id'               => data_get($data, 'user_id'),
            'payment_sys_id'        => data_get($data, 'payment_sys_id'),
            'payment_trx_id'        => data_get($data, 'payment_trx_id'),
            'note'                  => $wallet->id,
            'perform_time'          => now(),
            'status_description'    => "Transaction for wallet #$wallet->id"
        ]);

        return ['status' => true, 'code' => ResponseError::NO_ERROR, 'data' => $wallet];
    }

    public function subscriptionTransaction(int $id, array $data): array
    {
        $subscription = ShopSubscription::find($id);

        if (empty($subscription)) {
            return ['status' => false, 'code' => ResponseError::ERROR_404];
        } else if ($subscription->active) {
            return ['status' => false, 'code' => ResponseError::ERROR_208];
        }

        $payment = $this->checkPayment(data_get($data, 'payment_sys_id'), request()->merge([
            'user_id'     => auth('sanctum')->id(),
            'total_price' => $subscription->price,
        ]));

        if (!data_get($payment, 'status')) {
            return $payment;
        }

        $subscription->createTransaction([
            'price'              => $subscription->price,
            'user_id'            => auth('sanctum')->id(),
            'payment_sys_id'     => data_get($data, 'payment_sys_id'),
            'payment_trx_id'     => data_get($data, 'payment_trx_id'),
            'note'               => $subscription->id,
            'perform_time'       => now(),
            'status'             => Transaction::STATUS_PAID,
            'status_description' => "Transaction for Subscription #$subscription->id"
        ]);

        if (data_get($payment, 'wallet')) {

            $subscription->update(['active' => 1]);

            $this->walletHistoryAdd(auth('sanctum')->user(), $subscription->transaction, $subscription, 'Subscription', 'withdraw');
        }

        return ['status' => true, 'code' => ResponseError::NO_ERROR, 'data' => $subscription];
    }

    private function checkPayment(int $id, $model, $isOrder = false): array
    {
        $payment = Payment::where('active', 1)->find($id);

        if (!$payment) {
            return [
                'status'  => false,
                'code'    => ResponseError::ERROR_404,
                'message' => __('errors.' . ResponseError::ERROR_404, locale: $this->language)
            ];
        } else if ($payment->tag !== 'wallet') {
            return ['status' => true, 'code' => ResponseError::NO_ERROR, 'payment_tag' => $payment->tag];
        }

        if ($isOrder) {

            /** @var Order $model */
            $changedPrice = max(data_get($model, 'total_price', 0), 0) - $model?->transaction?->price;

            if ($model?->transaction?->status === 'paid' && $changedPrice <= 1) {
                return ['status' => true, 'code' => ResponseError::NO_ERROR, 'already_payed' => true];
            }

            data_set($model, 'total_price', max($changedPrice, 0));
        }

        /** @var User $user */
        $user = User::with('wallet')->find(data_get($model, 'user_id'));

		if (empty($user->wallet?->uuid)) {
			$user = (new UserWalletService)->create($user);
		}

        $ratePrice = max(data_get($model, 'total_price', 0), 0);

        if ($user->wallet?->price >= $ratePrice) {

            $user->wallet()->update(['price' => $user->wallet->price - $ratePrice]);

            return ['status' => true, 'code' => ResponseError::NO_ERROR, 'wallet' => $user->wallet];
        }

        return [
            'status'  => false,
            'code'    => ResponseError::ERROR_109,
            'message' => __('errors.' . ResponseError::ERROR_109, locale: $this->language)
        ];

    }

    /**
     * @param User|null $user
     * @param Transaction $transaction
     * @param $model
     * @param string $type
     * @param string $paymentType
     * @return void
     */
    private function walletHistoryAdd(
        ?User       $user,
        Transaction $transaction,
                    $model,
        string      $type = 'Order',
        string      $paymentType = 'topup'
    ): void
    {
        $modelId = data_get($model, 'id');

        $user->wallet->histories()->create([
            'uuid'              => Str::uuid(),
            'transaction_id'    => $transaction->id,
            'type'              => $paymentType,
            'price'             => $transaction->price,
            'note'              => "Payment $type #$modelId via Wallet" ,
            'status'            => WalletHistory::PAID,
            'created_by'        => $transaction->user_id,
        ]);

        $transaction->update(['status' => WalletHistory::PAID]);
    }

}
