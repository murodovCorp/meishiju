<?php

namespace App\Services\YandexService;

use App\Models\Order;
use App\Services\CoreService;
use Http;
use Illuminate\Support\Collection;

class YandexService extends CoreService
{
    private string $baseUrl = 'https://b2b.taxi.yandex.net/b2b/cargo/integration';

    /**
     * @return string
     */
    protected function getModelClass(): string
    {
        return Order::class;
    }

    /**
     * @return string
     */
    private function getToken(): string
    {
        return config('app.yandex_token', '');
    }

    /**
     * @return string
     */
    private function getLang(): string
    {
        return $this->language === 'ru' ? 'ru/ru' : 'en/en';
    }

    /**
     * @param array $data
     * @param string $endpoint
     * @return Collection
     */
    public function baseInfoMethods(array $data, string $endpoint = ''): Collection
    {
        $fullName = data_get($data, 'address');

        $body = [
            'start_point' => [
                data_get($data, 'location.longitude'),
                data_get($data, 'location.latitude'),
            ]
        ];

        if (!empty($fullName)) {
            $body['fullname'] = $fullName;
        }

        $request = Http::withToken($this->getToken())
            ->withHeaders([
                'Accept-Language' => $this->getLang(),
                'Content-Type' => 'application/json',
            ])
            ->post("$this->baseUrl/$endpoint", $body);

        return collect($request->json());
    }

    /**
     * @param array $data
     * @return Collection
     */
    public function checkPrice(array $data): Collection
    {
        $addressFrom = data_get($data, 'address_from');
        $addressTo   = data_get($data, 'address_to');

        $body = [
            'items' => [
                [
                    'quantity' => 1,
                    'size' => [
                        'height' => 0.1,
                        'length' => 0.1,
                        'width'  => 0.1
                    ],
                    'weight' => 1
                ]
            ],
            'client_requirements' => [
                'assign_robot'  => false,
                'cargo_loaders' => 0,
                'cargo_options' => [
                    'thermobag',
//                    "auto_courier"
                ],
                'cargo_type'  => 'van', // van ("Маленький кузов") lcv_m ("Средний кузов") lcv_l ("Большой кузов").
                'pro_courier' => false,
                'taxi_class'  => 'express'
            ],
            'route_points' => [
                [
                    'coordinates' => [
                        data_get($data, 'location_from.longitude'),
                        data_get($data, 'location_from.latitude'),
                    ],
                ],
                [
                    'coordinates' => [
                        data_get($data, 'location_to.longitude'),
                        data_get($data, 'location_to.latitude'),
                    ],
                ],
            ],
            'skip_door_to_door' => false
        ];

        if (!empty($addressFrom)) {
            $body['route_points'][0]['full_name'] = $addressFrom;
        }

        if (!empty($addressTo)) {
            $body['route_points'][1]['full_name'] = $addressTo;
        }

        $request = Http::withToken($this->getToken())
            ->withHeaders([
                'Accept-Language' => $this->getLang(),
                'Content-Type' => 'application/json',
            ])
            ->post("$this->baseUrl/v2/check-price", $body);

        return collect($request->json());
    }

}
