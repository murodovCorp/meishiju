<?php

namespace App\Services\Yandex;

use App\Models\Cart;
use App\Models\Currency;
use App\Models\Order;
use Http;
use Str;

class YandexService
{
    private string $baseUrl = 'https://b2b.taxi.yandex.net';

    /**
     * @param array $data
     * @return array
     */
    private function getArrayItems(array $data): array
    {
        $items = [];

        $currency = Currency::find(data_get($data, 'currency_id'));

        $currency = $currency?->title === 'RUB' ? $currency : Currency::currenciesList()->where('title', 'RUB')->first();

        foreach ($data as $item) {

            $items[] = [
                'cost_currency' => $currency?->title ?? 'RUB',
                'cost_value'    => (string)(data_get($item, 'total_price') * ($currency?->rate ?? 1)),
                'pickup_point'  => 1, //Идентификатор точки, откуда нужно забрать товар.
                'droppof_point' => 2, //Идентификатор точки, куда нужно доставить товар.
                'quantity'      => data_get($item, 'quantity'),
                'size' => [
                    'height' => 0.15 * data_get($item, 'quantity'),
                    'length' => 0.15 * data_get($item, 'quantity'),
                    'width' => 0.15 * data_get($item, 'quantity')
                ],
                'title' => 'Плюмбус',
                'weight' => 0.3 * data_get($item, 'quantity')
            ];

        }

        return $items;
    }
    /**
     * @param Order $order
     * @return array
     */
    private function getOrderItems(Order $order): array
    {
        $items = [];

        $currency = $order->currency?->title === 'RUB'
            ? $order->currency
            : Currency::currenciesList()->where('title', 'RUB')->first();

        foreach ($order->orderDetails as $orderDetail) {

            $items[] = [
                'cost_currency' => $currency?->title ?? 'RUB',
                'cost_value'    => (string)($orderDetail->total_price * ($currency?->rate ?? 1)),
                'pickup_point'  => 1, //Идентификатор точки, откуда нужно забрать товар.
                'droppof_point' => 2, //Идентификатор точки, куда нужно доставить товар.
                'quantity'      => $orderDetail->quantity,
                'size' => [
                    'height' => 0.15 * $orderDetail->quantity,
                    'length' => 0.15 * $orderDetail->quantity,
                    'width' => 0.15 * $orderDetail->quantity
                ],
                'title' => 'Плюмбус',
                'weight' => 0.3 * $orderDetail->quantity
            ];

        }

        return $items;
    }

    /**
     * @param Cart $cart
     * @return array
     */
    private function getCartItems(Cart $cart): array
    {
        $items = [];

        $currency = $cart->currency?->title === 'RUB'
            ? $cart->currency
            : Currency::currenciesList()->where('title', 'RUB')->first();

        foreach ($cart->userCarts as $userCart) {

            foreach ($userCart->cartDetails as $cartDetail) {

                $items[] = [
                    'cost_currency' => $currency?->title ?? 'RUB',
                    'cost_value'    => (string)($cartDetail->price * ($currency?->rate ?? 1)),
                    'pickup_point'  => 1, //Идентификатор точки, откуда нужно забрать товар.
                    'droppof_point' => 2, //Идентификатор точки, куда нужно доставить товар.
                    'quantity'      => $cartDetail->quantity,
                    'size' => [
                        'height' => 0.15 * $cartDetail->quantity,
                        'length' => 0.15 * $cartDetail->quantity,
                        'width' => 0.15 * $cartDetail->quantity
                    ],
                    'title' => 'Плюмбус',
                    'weight' => 0.3 * $cartDetail->quantity
                ];

            }

        }

        return $items;
    }

    /**
     * @param Order|Cart|array $model
     * @return array
     */
    private function getItems(Order|Cart|array $model): array
    {

        if ($model instanceof Order) {

            return $this->getOrderItems($model);

        } else if ($model instanceof Cart) {

            return $this->getCartItems($model);

        }

        return $this->getArrayItems($model);
    }

    public function checkPrice(Order|Cart|array $model, array $shopLocation, array $clientLocation): array
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
                'items' => $this->getItems($model),
//                'client_requirements' => [
//                    'assign_robot'  => false,
//                    'cargo_loaders' => 0,
//                    'cargo_options' => [
//                        'thermobag',
//                        'auto_courier'
//                    ],
//                    'cargo_type'  => 'van',
//                    'pro_courier' => false,
//                    'taxi_class'  => 'courier',
//                ],
                'route_points' => $coordinates,
                'skip_door_to_door' => false,
            ]);

        return $request->json();
    }

    public function createOrder(Order $order, array $shopLocation, array $clientLocation): array
    {
        $requestId = Str::uuid();

        $request = Http::withToken('y0_AgAAAABsYLysAAc6MQAAAADkNEQMsgJuRaQ0RM-mS_yfM0t-OgxlJ9E')
            ->withHeaders([
                'Accept-Language' => 'RU-ru'
            ])
            ->post("$this->baseUrl/b2b/cargo/integration/v2/claims/create?request_id=$requestId", [
                'callback_url'  => request()->getSchemeAndHttpHost() . '/api/v1/webhook/yandex/order',
                'items'         => $this->getItems($order),
                'route_points'  => [
                    [
                        'address' => [
                            'coordinates' => [
                                (double)data_get($shopLocation, 'longitude'),
                                (double)data_get($shopLocation, 'latitude')
                            ],
                            'fullname' => 'проспект 60-летия Октября, 21к2',
                        ],
                        'contact' => [
                            'name'  => $order->shop?->translation?->title,
                            'phone' => '+' . str_replace('+', '', $order->shop?->phone)
                        ] + ($order->shop?->seller?->email ? ['email' => $order->shop->seller->email] : []),
                        'point_id'          => 1,
                        'skip_confirmation' => true,
                        'type'              => 'source',
                        'visit_order'       => 1,
                    ],
                    [
                        'address' => [
                            'coordinates' => [
                                (double)data_get($clientLocation, 'longitude'),
                                (double)data_get($clientLocation, 'latitude')
                            ],
                            'fullname' => 'проспект 60-летия Октября, 21к2' //data_get($order->address, 'address')
                        ],
                        'contact' => [
                            'name'  => $order->username ?? $order->user?->firstname,
                            'phone' => "+" . str_replace('+', '', $order->phone ?? $order->user?->phone)
                        ] + ($order->user?->email ? [$order->user->email] : []),
                        'point_id'          => 2,
                        'skip_confirmation' => true,
                        'type'              => 'destination',
                        'visit_order'       => 2,
                        'external_order_id' => (string)$order->id
                    ]
                ],
                'optional_return'       => false,
                'skip_act'              => true,
                'skip_client_notify'    => false,
                'skip_door_to_door'     => false,
                'skip_emergency_notify' => false
            ]);

        return $request->json();
    }

    public function getOrderInfo(string $requestId = '14d2d94c801543b3aee148cbe59d02f7'): array
    {
        $request = Http::withToken('y0_AgAAAABsYLysAAc6MQAAAADkNEQMsgJuRaQ0RM-mS_yfM0t-OgxlJ9E')
            ->withHeaders([
                'Accept-Language' => 'RU-ru'
            ])
            ->post("$this->baseUrl/b2b/cargo/integration/v2/claims/info?claim_id=$requestId");

        return $request->json();
    }

    public function acceptOrder(string $requestId = '14d2d94c801543b3aee148cbe59d02f7'): array
    {
        $request = Http::withToken('y0_AgAAAABsYLysAAc6MQAAAADkNEQMsgJuRaQ0RM-mS_yfM0t-OgxlJ9E')
            ->withHeaders([
                'Accept-Language' => 'RU-ru'
            ])
            ->post("$this->baseUrl/b2b/cargo/integration/v2/claims/accept?claim_id=$requestId");

        return $request->json();
    }

    public function cancelInfoOrder(string $requestId = '14d2d94c801543b3aee148cbe59d02f7'): array
    {
        $request = Http::withToken('y0_AgAAAABsYLysAAc6MQAAAADkNEQMsgJuRaQ0RM-mS_yfM0t-OgxlJ9E')
            ->withHeaders([
                'Accept-Language' => 'RU-ru'
            ])
            ->post("$this->baseUrl/b2b/cargo/integration/v2/claims/cancel-info?claim_id=$requestId");

        //free
        //paid
        //unavailable
        return $request->json();
    }

    public function cancelOrder(string $requestId = '14d2d94c801543b3aee148cbe59d02f7'): array
    {
        $state = $this->cancelInfoOrder($requestId);

        $request = Http::withToken('y0_AgAAAABsYLysAAc6MQAAAADkNEQMsgJuRaQ0RM-mS_yfM0t-OgxlJ9E')
            ->withHeaders([
                'Accept-Language' => 'RU-ru'
            ])
            ->post("$this->baseUrl/b2b/cargo/integration/v2/claims/cancel?claim_id=$requestId", [
                'cancel_state' => data_get($state, 'cancel_state'),
                'version'      => 2,
            ]);

        return $request->json();
    }

    public function orderDriverVoiceForwarding(string $requestId = '14d2d94c801543b3aee148cbe59d02f7'): array
    {
        $request = Http::withToken('y0_AgAAAABsYLysAAc6MQAAAADkNEQMsgJuRaQ0RM-mS_yfM0t-OgxlJ9E')
            ->withHeaders([
                'Accept-Language' => 'RU-ru'
            ])
            ->post("$this->baseUrl/b2b/cargo/integration/v2/driver-voiceforwarding", [
                'claim_id' => $requestId
            ]);

        return $request->json();
    }

    public function orderDriverPerformerPosition(string $requestId = '14d2d94c801543b3aee148cbe59d02f7'): array
    {
        $request = Http::withToken('y0_AgAAAABsYLysAAc6MQAAAADkNEQMsgJuRaQ0RM-mS_yfM0t-OgxlJ9E')
            ->withHeaders([
                'Accept-Language' => 'RU-ru'
            ])
            ->post("$this->baseUrl/b2b/cargo/integration/v2/performer-position?claim_id=$requestId");

        return $request->json();
    }

    public function orderTrackingLinks(string $requestId = '14d2d94c801543b3aee148cbe59d02f7'): array
    {
        $request = Http::withToken('y0_AgAAAABsYLysAAc6MQAAAADkNEQMsgJuRaQ0RM-mS_yfM0t-OgxlJ9E')
            ->withHeaders([
                'Accept-Language' => 'RU-ru'
            ])
            ->post("$this->baseUrl/b2b/cargo/integration/v2/tracking-links?claim_id=$requestId");

        return $request->json();
    }

    public function orderPointsEta(string $requestId = '14d2d94c801543b3aee148cbe59d02f7'): array
    {
        $request = Http::withToken('y0_AgAAAABsYLysAAc6MQAAAADkNEQMsgJuRaQ0RM-mS_yfM0t-OgxlJ9E')
            ->withHeaders([
                'Accept-Language' => 'RU-ru'
            ])
            ->post("$this->baseUrl/b2b/cargo/integration/v2/points-eta?claim_id=$requestId");

        return $request->json();
    }

}
