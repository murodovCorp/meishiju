<?php

namespace App\Services\Yandex;

use App\Models\Cart;
use App\Models\Currency;
use App\Models\Order;
use Http;
use Illuminate\Http\Client\PendingRequest;
use Str;

class YandexService
{
    private string $baseUrl = 'https://b2b.taxi.yandex.net';

    /**
     * @return PendingRequest
     */
    private function getBaseHttp(): PendingRequest
    {
        return Http::withToken(config('app.yandex_token'))
            ->withHeaders([
                'Accept-Language' => 'RU-ru'
            ]);
    }

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

            if (data_get($item, 'quantity') === 0) {
                continue;
            }

            $items[] = [
                'cost_currency' => $currency?->title ?? 'RUB',
                'cost_value'    => (string)round(data_get($item, 'total_price') * ($currency?->rate ?? 1), 2),
                'pickup_point'  => 1, //Идентификатор точки, откуда нужно забрать товар.
                'droppof_point' => 2, //Идентификатор точки, куда нужно доставить товар.
                'quantity'      => data_get($item, 'quantity'),
                'size' => [
                    'height' => 0.015 * data_get($item, 'quantity'),
                    'length' => 0.015 * data_get($item, 'quantity'),
                    'width' => 0.015 * data_get($item, 'quantity')
                ],
                'title' => 'Плюмбус',
                'weight' => 0.03 * data_get($item, 'quantity')
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
                'cost_value'    => (string)round($orderDetail->total_price * ($currency?->rate ?? 1), 2),
                'pickup_point'  => 1, //Идентификатор точки, откуда нужно забрать товар.
                'droppof_point' => 2, //Идентификатор точки, куда нужно доставить товар.
                'quantity'      => $orderDetail->quantity,
                'size' => [
                    'height' => 0.015 * $orderDetail->quantity,
                    'length' => 0.015 * $orderDetail->quantity,
                    'width' => 0.015 * $orderDetail->quantity
                ],
                'title' => 'Плюмбус',
                'weight' => 0.03 * $orderDetail->quantity
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
                    'cost_value'    => (string)round($cartDetail->price * ($currency?->rate ?? 1), 2),
                    'pickup_point'  => 1, //Идентификатор точки, откуда нужно забрать товар.
                    'droppof_point' => 2, //Идентификатор точки, куда нужно доставить товар.
                    'quantity'      => $cartDetail->quantity,
                    'size' => [
                        'height' => 0.015 * $cartDetail->quantity,
                        'length' => 0.015 * $cartDetail->quantity,
                        'width' => 0.015 * $cartDetail->quantity
                    ],
                    'title' => 'Плюмбус',
                    'weight' => 0.03 * $cartDetail->quantity
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

    /**
     * @param Order|Cart|array $model
     * @param array $shopLocation
     * @param array $clientLocation
     * @return array
     */
    public function checkPrice(Order|Cart|array $model, array $shopLocation, array $clientLocation): array
    {
        $coordinates = [
            [
                'coordinates' => [
                    (double)data_get($shopLocation, 'longitude'),
                    (double)data_get($shopLocation, 'latitude'),
                ]
            ],
            [
                'coordinates' => [
                    (double)data_get($clientLocation, 'longitude'),
                    (double)data_get($clientLocation, 'latitude'),
                ]
            ],
        ];

        $request = $this->getBaseHttp()
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

        return [
            'code' => $request->status(),
            'data' => $request->json(),
        ];
    }

    /**
     * @param Order $order
     * @return array
     */
    public function createOrder(Order $order): array
    {
        $requestId = data_get($order->yandex, 'id');

        if (empty($requestId)) {
            $requestId = Str::uuid();
        }

        $request = $this->getBaseHttp()
            ->post("$this->baseUrl/b2b/cargo/integration/v2/claims/create?request_id=$requestId", [
                'callback_url' => request()->getSchemeAndHttpHost() . '/api/v1/webhook/yandex/order',
                'items'        => $this->getItems($order),
                'route_points' => [
                    [
                        'address' => [
                            'coordinates' => [
                                (double)data_get($order->shop->location, 'longitude'),
                                (double)data_get($order->shop->location, 'latitude')
                            ],
                            'fullname' => $order->shop?->translation?->address,
                        ],
                        'contact' => [
                            'name'  => $order->shop?->translation?->title,
                            'phone' => '+' . str_replace('+', '', $order->shop?->phone ?? $order->shop?->seller?->phone)
                        ],
                        'point_id'          => 1,
                        'skip_confirmation' => true,
                        'type'              => 'source',
                        'visit_order'       => 1,
                    ],
                    [
                        'address' => [
                            'coordinates' => [
                                (double)data_get($order->location, 'longitude'),
                                (double)data_get($order->location, 'latitude')
                            ],
                            'fullname' => data_get($order->address, 'address')
                        ],
                        'contact' => [
                            'name'  => $order->username ?? "{$order->user?->firstname} {$order->user?->lastname}",
                            'phone' => '+' . str_replace('+', '', $order->phone ?? $order->user?->phone)
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

        $data = $request->json();

        $yandex = $order->yandex;
        $yandex['request_id']     = data_get($data, 'id');
        $yandex['id']             = $requestId;
        $yandex['status']         = data_get($data, 'status');
        $yandex['corp_client_id'] = data_get($data, 'corp_client_id');

        $order->update([
            'yandex' => $yandex
        ]);

        return [
            'code' => $request->status(),
            'data' => $data,
        ];
    }

    /**
     * @param Order $order
     * @return array
     */
    public function getOrderInfo(Order $order): array
    {
        $requestId = data_get($order->yandex, 'request_id');

        $request = $this->getBaseHttp()
            ->post("$this->baseUrl/b2b/cargo/integration/v2/claims/info?claim_id=$requestId");

        $data = $request->json();

        $yandex = $order->yandex;

        $defStatus = data_get($order->yandex, 'status');

        $yandex['status'] = data_get($data, 'status', $defStatus);

        $order->update([
            'yandex' => $yandex
        ]);

        return [
            'code' => $request->status(),
            'data' => $data,
        ];
    }

    /**
     * @param Order $order
     * @return array
     */
    public function acceptOrder(Order $order): array
    {
        $requestId = data_get($order->yandex, 'request_id');

        $request = $this->getBaseHttp()
            ->post("$this->baseUrl/b2b/cargo/integration/v2/claims/accept?claim_id=$requestId", [
                'version' => 1
            ]);

        $data = $request->json();

        $yandex = $order->yandex;

        $defStatus   = data_get($order->yandex, 'status');
        $defVersion  = data_get($order->yandex, 'version');
        $defRVersion = data_get($order->yandex, 'user_request_revision');
        $defNotify   = data_get($order->yandex, 'skip_client_notify');

        $yandex['status']                = data_get($data, 'status', $defStatus);
        $yandex['version']               = data_get($data, 'version', $defVersion);
        $yandex['user_request_revision'] = data_get($data, 'user_request_revision', $defRVersion);
        $yandex['skip_client_notify']    = data_get($data, 'skip_client_notify', $defNotify);

        $order->update([
            'yandex' => $yandex
        ]);

        return [
            'code' => $request->status(),
            'data' => $data,
        ];
    }

    /**
     * @param Order $order
     * @return array
     */
    public function cancelInfoOrder(Order $order): array
    {
        $requestId = data_get($order->yandex, 'request_id');

        $request = $this->getBaseHttp()
            ->post("$this->baseUrl/b2b/cargo/integration/v2/claims/cancel-info?claim_id=$requestId");

        $data     = $request->json();
        $yandex   = $order->yandex;
        $defState = data_get($order->yandex, 'cancel_state');

        $yandex['cancel_state'] = data_get($data, 'cancel_state', $defState);

        $order->update([
            'yandex' => $yandex
        ]);

        return [
            'code' => $request->status(),
            'data' => $data,
        ];
    }

    /**
     * @param Order $order
     * @return array
     */
    public function cancelOrder(Order $order): array
    {
        $requestId = data_get($order->yandex, 'request_id');

        $state = $this->cancelInfoOrder($requestId);

        $request = $this->getBaseHttp()
            ->post("$this->baseUrl/b2b/cargo/integration/v2/claims/cancel?claim_id=$requestId", [
                'cancel_state' => data_get($state, 'data.cancel_state'),
                'version'      => 1,
            ]);

        $data = $request->json();

        $yandex = $order->yandex;

        $defStatus   = data_get($order->yandex, 'status');
        $defVersion  = data_get($order->yandex, 'version');
        $defRVersion = data_get($order->yandex, 'user_request_revision');
        $defNotify   = data_get($order->yandex, 'skip_client_notify');

        $yandex['status'] = data_get($data, 'status', $defStatus);
        $yandex['version'] = data_get($data, 'version', $defVersion);
        $yandex['user_request_revision'] = data_get($data, 'user_request_revision', $defRVersion);
        $yandex['skip_client_notify'] = data_get($data, 'skip_client_notify', $defNotify);

        $order->update([
            'yandex' => $yandex
        ]);

        return [
            'code' => $request->status(),
            'data' => $data,
        ];
    }

    /**
     * @param Order $order
     * @return array
     */
    public function orderDriverVoiceForwarding(Order $order): array
    {
        $requestId = data_get($order->yandex, 'request_id');

        $request = $this->getBaseHttp()
            ->post("$this->baseUrl/b2b/cargo/integration/v2/driver-voiceforwarding", [
                'claim_id' => $requestId
            ]);

        $data = $request->json();

        $yandex = $order->yandex;

        $defPhone = data_get($order->yandex, 'deliveryman.phone');
        $defExt   = data_get($order->yandex, 'deliveryman.ext');
        $defTtl   = data_get($order->yandex, 'deliveryman.ttl_seconds');

        unset($yandex['deliveryman']['phone']);
        unset($yandex['deliveryman']['ext']);
        unset($yandex['deliveryman']['ttl_seconds']);

        $yandex['deliveryman'] += [
            'phone'       => data_get($data, 'phone', $defPhone),
            'ext'         => data_get($data, 'ext', $defExt),
            'ttl_seconds' => data_get($data, 'ttl_seconds', $defTtl),
        ];

        $order->update([
            'yandex' => $yandex
        ]);

        return [
            'code' => $request->status(),
            'data' => $data,
        ];
    }

    /**
     * @param Order $order
     * @return array
     */
    public function orderDriverPerformerPosition(Order $order): array
    {
        $requestId = data_get($order->yandex, 'request_id');

        $request = $this->getBaseHttp()
            ->post("$this->baseUrl/b2b/cargo/integration/v2/performer-position?claim_id=$requestId");

        $data = $request->json();

        $yandex = $order->yandex;

        $defPosition    = data_get($order->yandex, 'position');
        $defRoutePoints = data_get($order->yandex, 'route_points');

        unset($yandex['deliveryman']['position']);
        unset($yandex['deliveryman']['route_points']);

        $yandex['deliveryman'] += [
            'position'      => data_get($data, 'position', $defPosition),
            'route_points'  => data_get($data, 'route_points', $defRoutePoints),
        ];

        $order->update([
            'yandex' => $yandex
        ]);

        return [
            'code' => $request->status(),
            'data' => $data,
        ];
    }

    /**
     * @param Order $order
     * @return array
     */
    public function orderTrackingLinks(Order $order): array
    {
        $requestId = data_get($order->yandex, 'request_id');

        $request = $this->getBaseHttp()
            ->post("$this->baseUrl/b2b/cargo/integration/v2/tracking-links?claim_id=$requestId");

        $data = $request->json();

        $yandex = $order->yandex;

        $defRoutePoints = data_get($order->yandex, 'route_points');

        unset($yandex['deliveryman']['route_points']);

        $yandex['deliveryman'] += [
            'route_points'  => data_get($data, 'route_points', $defRoutePoints),
        ];

        $order->update([
            'yandex' => $yandex
        ]);

        return [
            'code' => $request->status(),
            'data' => $data,
        ];
    }

    /**
     * @param string $requestId
     * @return array
     */
    public function orderPointsEta(string $requestId): array
    {
        $request = $this->getBaseHttp()
            ->post("$this->baseUrl/b2b/cargo/integration/v2/points-eta?claim_id=$requestId");

        return [
            'code' => $request->status(),
            'data' => $request->json(),
        ];
    }

}
