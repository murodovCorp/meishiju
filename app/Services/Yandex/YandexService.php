<?php

namespace App\Services\Yandex;

use Http;

class YandexService
{
    private string $baseUrl = 'https://b2b.taxi.yandex.net';

    public function checkPrice(array $shopLocation, array $clientLocation): array
    {
        $coordinates = [
            [
                'coordinates' => [
                    (double)data_get($shopLocation, 'longitude'),
                    (double)data_get($shopLocation, 'latitude')
                ]
            ],
            [
                'coordinates' => [
                    (double)data_get($clientLocation, 'longitude'),
                    (double)data_get($clientLocation, 'latitude')
                ]
            ],
        ];

        $request = Http::withToken('y0_AgAAAABsYLysAAc6MQAAAADkNEQMsgJuRaQ0RM-mS_yfM0t-OgxlJ9E')
            ->withHeaders([
                'Accept-Language' => 'RU-ru'
            ])
            ->post("$this->baseUrl/b2b/cargo/integration/v1/check-price", [
                'items' => [
                    [
                        'quantity' => 1,
                        'size' => [
                            'height' => 0.80,
                            'length' => 0.80,
                            'width'  => 0.80
                        ],
                        'weight' => 1,
                    ],
                ],
                'client_requirements' => [
                    'assign_robot'  => false,
                    'cargo_loaders' => 0,
                    'cargo_options' => [
                        'thermobag',
                        'auto_courier'
                    ],
                    'cargo_type'  => 'van',
                    'pro_courier' => false,
                    'taxi_class'  => 'express',
                ],
                'route_points' => $coordinates,
                'skip_door_to_door' => false,
            ]);

        return $request->json();
    }

}
