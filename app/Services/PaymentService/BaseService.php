<?php

namespace App\Services\PaymentService;

use App\Models\Order;
use App\Models\PaymentProcess;
use App\Models\Payout;
use App\Models\PushNotification;
use App\Models\Shop;
use App\Models\Transaction;
use App\Models\User;
use App\Models\WalletHistory;
use App\Services\CoreService;
use App\Services\SubscriptionService\SubscriptionService;
use App\Traits\Notification;
use Illuminate\Support\Str;

class BaseService extends CoreService
{

    use Notification;

    protected function getModelClass(): string
    {
        return Payout::class;
    }

    public function afterHook($token, $status) {

        /** @var PaymentProcess $paymentProcess */
        $paymentProcess = PaymentProcess::with(['order.transaction', 'subscription.transaction'])
            ->where('id', $token)
            ->first();

        if (empty($paymentProcess)) {
            return;
        }

        if (!empty($paymentProcess->subscription_id)) {
            $subscription = $paymentProcess->subscription;

            $shop = Shop::find(data_get($paymentProcess->data, 'shop_id'));

            $shopSubscription = (new SubscriptionService)->subscriptionAttach(
                $subscription,
                (int)$shop?->id,
                $status === 'paid'
            );

            $shopSubscription->transaction?->update([
                'payment_trx_id' => $token,
                'status'         => $status,
            ]);

            return;
        }

        if (!empty($paymentProcess->order_id)) {

            $paymentProcess->order?->transaction?->update([
                'payment_trx_id' => $token,
                'status'         => $status,
            ]);

            $tokens = $this->tokens($paymentProcess->order);

            $this->sendNotification(
                data_get($tokens, 'tokens'),
                "New order was created",
                $paymentProcess->order->id,
                $paymentProcess->order?->setAttribute('type', PushNotification::NEW_ORDER)?->only(['id', 'status', 'type']),
                data_get($tokens, 'ids', [])
            );

            return;
        }

        $userId = data_get($paymentProcess->data, 'user_id');
        $type   = data_get($paymentProcess->data, 'type');

        if ($userId && $type === 'wallet') {

            $trxId       = data_get($paymentProcess->data, 'trx_id');
            $transaction = Transaction::find($trxId);

            $transaction->update([
                'payment_trx_id' => $token,
                'status'         => $status,
            ]);

            if ($status === WalletHistory::PAID) {

                $user = User::find($userId);

                $user?->wallet?->increment('price', data_get($paymentProcess->data, 'price'));

                $user->wallet->histories()->create([
                    'uuid'              => Str::uuid(),
                    'transaction_id'    => $transaction->id,
                    'type'              => 'topup',
                    'price'             => $transaction->price,
                    'note'              => "Payment #{$user?->wallet?->id} via Wallet" ,
                    'status'            => WalletHistory::PAID,
                    'created_by'        => $transaction->user_id,
                ]);

            }

        }

    }


    public function tokens(Order $order): array
    {
        $adminFirebaseTokens = User::with([
            'roles' => fn($q) => $q->where('name', 'admin')
        ])
            ->whereHas('roles', fn($q) => $q->where('name', 'admin') )
            ->whereNotNull('firebase_token')
            ->pluck('firebase_token', 'id')
            ->toArray();

        $sellersFirebaseTokens = User::with([
            'shop' => fn($q) => $q->where('id', $order->shop_id)
        ])
            ->whereHas('shop', fn($q) => $q->where('id', $order->shop_id))
            ->whereNotNull('firebase_token')
            ->pluck('firebase_token', 'id')
            ->toArray();

        $aTokens = [];
        $sTokens = [];

        foreach ($adminFirebaseTokens as $adminToken) {
            $aTokens = array_merge($aTokens, is_array($adminToken) ? array_values($adminToken) : [$adminToken]);
        }

        foreach ($sellersFirebaseTokens as $sellerToken) {
            $sTokens = array_merge($sTokens, is_array($sellerToken) ? array_values($sellerToken) : [$sellerToken]);
        }

        return [
            'tokens' => array_values(array_unique(array_merge($aTokens, $sTokens))),
            'ids'    => array_merge(array_keys($adminFirebaseTokens), array_keys($sellersFirebaseTokens))
        ];
    }

}
