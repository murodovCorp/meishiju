<?php

namespace App\Services\Yandex;

use App\Http\Resources\OrderResource;
use App\Models\Cart;
use App\Models\Currency;
use App\Models\Order;
use Http;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Log;
use Str;

class YandexService
{
    private string $baseUrl = 'https://b2b.taxi.yandex.net';

    public array $startStatuses = [
        'new',
        'estimating',
        'ready_for_approval'
    ];

    public array $canceledStatuses = [
        'estimating_failed',
        'performer_not_found',
        'cancelled',
        'cancelled_with_payment',
        'cancelled_by_taxi',
        'cancelled_with_items_on_hands',
        'failed',
    ];

    public array $returnedStatuses = [
        'returning',
        'return_arrived',
        'ready_for_return_confirmation',
        'returned',
        'returned_finish'
    ];

    public array $deliveredStatuses = [
        //'ready_for_delivery_confirmation',
        'delivered',
        'delivered_finish',
    ];

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
                'title'  => data_get($item, 'stock.countable.translation.title', 'Плюмбус'),
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
                    'width'  => 0.015 * $orderDetail->quantity
                ],
                'title'  => $orderDetail->stock?->countable?->translation?->title ?? 'Плюмбус',
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
                        'width'  => 0.015 * $cartDetail->quantity
                    ],
                    'title'  => $cartDetail?->stock?->countable?->translation?->title ?? 'Плюмбус',
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

    public function list(array $filter): AnonymousResourceCollection
    {
        $orders = Order::with(['user'])
            ->whereJsonLength('yandex', '>', 0)
            ->when(data_get($filter, 'shop_id'), fn($q, $shopId) => $q->where('shop_id', $shopId))
            ->when(data_get($filter, 'status'),  fn($q, $status) => $q->whereJsonContains('yandex->status', $status))
            ->paginate(data_get($filter, 'perPage'));

        return OrderResource::collection($orders);
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
        $yandex     = $order->yandex;
        $requestId  = Str::uuid();
        $url        = "$this->baseUrl/b2b/cargo/integration/v2/claims/create?request_id=$requestId";
        $version    = data_get($yandex, 'version', 1);
        $status     = data_get($yandex, 'status');

        if (!empty($status) && in_array($status, $this->startStatuses)) {
            $requestId = data_get($order->yandex, 'id');
            $url       = "$this->baseUrl/b2b/cargo/integration/v2/claims/edit?claim_id=$requestId&version=$version";
        } else if(!empty($status) && in_array($status, $this->canceledStatuses)) {
            $this->cancelOrder($order);
            $order->update([
                'yandex' => null
            ]);
        } else if(!empty($status)) {
            return $this->getOrderInfo($order);
        }

        $response = $this->getBaseHttp()->post($url, [
            'callback_properties'   => [
                'callback_url' => request()->getSchemeAndHttpHost() . '/api/v1/webhook/yandex/order'
            ],
            'items'                 => $this->getItems($order),
            'route_points'          => [
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
            'auto_accept'           => true,
            'optional_return'       => false,
            'skip_act'              => true,
            'skip_client_notify'    => false,
            'skip_door_to_door'     => false,
            'skip_emergency_notify' => false
        ]);

        return $this->getOrderInfo($order, $response->json(), $response->status());
    }

    /**
     * @param Order $order
     * @param array|null $data
     * @param string|null $code
     * @return array
     */
    public function getOrderInfo(Order $order, ?array $data = null, ?string $code = null): array
    {
        $requestId = data_get($order->yandex, 'id');

        if (empty($data)) {
            $request = $this->getBaseHttp()
                ->post("$this->baseUrl/b2b/cargo/integration/v2/claims/info?claim_id=$requestId");

            $data = $request->json();
            $code = $request->status();
        }

        $defStatus = data_get($order->yandex, 'status');

        $yandexStatus = data_get($data, 'status', $defStatus);

        $data = array_merge((!empty($order->yandex) ? (array)$order->yandex : []), $data);

        if (!in_array($yandexStatus, array_merge($this->returnedStatuses, $this->canceledStatuses))) {
            unset($data['message']);
            unset($data['error_messages']);
            unset($data['code']);
            unset($data['warnings']);
        }

        $order->update([
            'yandex' => $data,
        ]);

        return [
            'code' => $code,
            'data' => $order
        ];
    }

    /**
     * @param Order $order
     * @return array
     */
    public function acceptOrder(Order $order): array
    {
        $requestId = data_get($order->yandex, 'id');

        $response = $this->getBaseHttp()
            ->post("$this->baseUrl/b2b/cargo/integration/v2/claims/accept?claim_id=$requestId", [
                'version' => data_get($order->yandex, 'version', 1)
            ]);

        $order->update([
            'yandex' => array_merge((!empty($order->yandex) ? (array)$order->yandex : []), $response->json()),
        ]);

        return [
            'code' => $response->status(),
            'data' => $order
        ];
    }

    /**
     * @param Order $order
     * @return array
     */
    public function cancelInfoOrder(Order $order): array
    {
        $requestId = data_get($order->yandex, 'id');

        $response = $this->getBaseHttp()
            ->post("$this->baseUrl/b2b/cargo/integration/v2/claims/cancel-info?claim_id=$requestId");

        return [
            'code' => $response->status(),
            'data' => $response->json(),
        ];
    }

    /**
     * @param Order $order
     * @return array
     */
    public function cancelOrder(Order $order): array
    {
        $requestId = data_get($order->yandex, 'id');

        $state = $this->cancelInfoOrder($order);

        $cancelState = data_get($state, 'data.cancel_state', 'free');

        if ($cancelState === 'unavailable') {

            $yandex = $order->yandex;
            $yandex['message'] = 'Нельзя отменить заказ. Возможно заказ уже отменен, проверьте статус через личный кабинет или обновите страницу.';

            $order->update([
                'yandex' => $yandex
            ]);

            return [
                'code' => 400,
                'data' => $order
            ];
        }

        $response = $this->getBaseHttp()
            ->post("$this->baseUrl/b2b/cargo/integration/v2/claims/cancel?claim_id=$requestId", [
                'cancel_state' => $cancelState,
                'version'      => data_get($order->yandex, 'version', 1),
            ]);

        $yandex = array_merge((!empty($order->yandex) ? (array)$order->yandex : []), $response->json());

        unset($yandex['code']);
        unset($yandex['warnings']);

        $order->update([
            'yandex' => array_merge((!empty($order->yandex) ? (array)$order->yandex : []), $response->json()),
        ]);

        return [
            'code' => $response->status(),
            'data' => $order
        ];
    }

    /**
     * @param Order $order
     * @return array
     */
    public function orderDriverVoiceForwarding(Order $order): array
    {
        $requestId = data_get($order->yandex, 'id');

        $response = $this->getBaseHttp()->post("$this->baseUrl/b2b/cargo/integration/v2/driver-voiceforwarding", [
            'claim_id' => $requestId
        ]);

        if ($response->status() === 200) {
            $order->update([
                'yandex' => array_merge((!empty($order->yandex) ? (array)$order->yandex : []), ['deliveryman' => $response->json()]),
            ]);
        } else {
            Log::error('orderDriverVoiceForwarding', [
                'status' => $response->status(),
                'data'   => $response->json(),
            ]);
        }

        return [
            'code' => $response->status(),
            'data' => $order
        ];
    }

    /**
     * @param Order $order
     * @return array
     */
    public function orderDriverPerformerPosition(Order $order): array
    {
        $requestId = data_get($order->yandex, 'id');

        $response = $this->getBaseHttp()
            ->post("$this->baseUrl/b2b/cargo/integration/v2/performer-position?claim_id=$requestId");

        if ($response->status() === 200) {
            $order->update([
                'yandex' => array_merge((!empty($order->yandex) ? (array)$order->yandex : []), ['position' => $response->json()]),
            ]);
        } else {
            Log::error('orderDriverPerformerPosition', [
                'status' => $response->status(),
                'data'   => $response->json(),
            ]);
        }

        return [
            'code' => $response->status(),
            'data' => $order
        ];
    }

    /**
     * @param Order $order
     * @return array
     */
    public function orderTrackingLinks(Order $order): array
    {
        $requestId = data_get($order->yandex, 'id');

        $response = $this->getBaseHttp()
            ->post("$this->baseUrl/b2b/cargo/integration/v2/tracking-links?claim_id=$requestId");

        if ($response->status() === 200) {

            $order->update([
                'yandex' => array_merge((!empty($order->yandex) ? (array)$order->yandex : []), ['links' => $response->json()]),
            ]);

        } else {
            Log::error('orderTrackingLinks', [
                'status' => $response->status(),
                'data'   => $response->json(),
            ]);
        }

        return [
            'code' => $response->status(),
            'data' => $order
        ];
    }

    /**
     * @param Order $order
     * @return array
     */
    public function orderPointsEta(Order $order): array
    {
        $requestId = data_get($order->yandex, 'id');

        $response = $this->getBaseHttp()
            ->post("$this->baseUrl/b2b/cargo/integration/v2/points-eta?claim_id=$requestId");

        if ($response->status() === 200) {
            $order->update([
                'yandex' => array_merge((!empty($order->yandex) ? (array)$order->yandex : []), ['points' => $response->json()]),
            ]);
        } else {
            Log::error('orderPointsEta', [
                'status' => $response->status(),
                'data'   => $response->json(),
            ]);
        }

        return [
            'code' => $response->status(),
            'data' => $order
        ];
    }

}
