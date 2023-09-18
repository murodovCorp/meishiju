<?php

namespace App\Traits;

use App\Models\Settings;
use App\Models\User;
use App\Services\PushNotificationService\PushNotificationService;
use Illuminate\Support\Facades\Http;
use Log;

trait Notification
{
    private string $url = 'https://fcm.googleapis.com/fcm/send';

    public function sendNotification(
        array   $receivers = [],
        ?string $message = '',
        ?string $title = null,
        mixed   $data = [],
        array   $userIds = [],
        ?string $firebaseTitle = '',
    ): void
    {
        dispatch(function () use ($receivers, $message, $title, $data, $userIds, $firebaseTitle) {

            if (empty($receivers)) {
                return;
            }

            $serverKey = $this->firebaseKey();

            $fields = [
                'registration_ids' => array_values($receivers),
                'notification' => [
                    'body'  => $message,
                    'title' => $firebaseTitle ?? $title,
					'sound' => 'default',
                ],
                'data' => $data
            ];

            $headers = [
                'Authorization' => "key=$serverKey",
                'Content-Type' => 'application/json'
            ];

            $type = data_get($data, 'order.type');

            if (is_array($userIds) && count($userIds) > 0) {
                (new PushNotificationService)->storeMany([
                    'type' => $type ?? data_get($data, 'type'),
                    'title' => $title,
                    'body' => $message,
                    'data' => $data,
                ], $userIds);
            }

            $result = Http::withHeaders($headers)->post($this->url, $fields);

            Log::error('result', [
                $serverKey,
                $this->url,
                count($receivers),
                'status' => $result->status(),
                'data' => $result->json(),
				[
					'notification' => [
						'body'  => $message,
						'title' => $firebaseTitle ?? $title,
						'sound' => 'default',
					],
					'data' => $data
				]
            ]);
        })->afterResponse();
    }

    public function sendAllNotification(?string $title = null, mixed $data = [], ?string $firebaseTitle = ''): void
    {
        dispatch(function () use ($title, $data, $firebaseTitle) {

            User::select([
                'id',
                'deleted_at',
                'active',
                'email_verified_at',
                'phone_verified_at',
                'firebase_token',
            ])
                ->where('active', 1)
                ->where(fn($q) => $q->whereNotNull('email_verified_at')->orWhereNotNull('phone_verified_at'))
                ->whereNotNull('firebase_token')
                ->orderBy('id')
                ->chunk(100, function ($users) use ($title, $data, $firebaseTitle) {

                    $firebaseTokens = $users?->pluck('firebase_token', 'id')?->toArray();

                    $receives = [];

					Log::error('firebaseTokens ', [
						'count' => !empty($firebaseTokens) ? count($firebaseTokens) : $firebaseTokens
					]);

                    foreach ($firebaseTokens as $firebaseToken) {

                        if (empty($firebaseToken)) {
                            continue;
                        }

                        $receives[] = array_filter($firebaseToken, fn($item) => !empty($item));
                    }

                    $receives = array_merge(...$receives);

					Log::error('count rece ' . count($receives));

                    $this->sendNotification(
                        $receives,
                        $title,
                        data_get($data, 'id'),
                        $data,
                        array_keys(is_array($firebaseTokens) ? $firebaseTokens : []),
                        $firebaseTitle
                    );

                });

        })->afterResponse();

    }

    private function firebaseKey()
    {
        return Settings::adminSettings()->where('key', 'server_key')->pluck('value')->first();
    }
}
