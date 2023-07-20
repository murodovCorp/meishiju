<?php

namespace App\Http\Controllers\API\v1\Dashboard\Seller;

use App\Helpers\ResponseError;
use App\Models\Order;
use App\Services\Yandex\YandexService;
use Illuminate\Http\JsonResponse;

class YandexController extends SellerBaseController
{
    public function __construct(private YandexService $service)
    {
        parent::__construct();
    }

    /**
     * @param int $id
     * @return array
     */
    public function getOrder(int $id): array
    {
        /** @var Order $order */

        $order = Order::with([
            'currency',
            'orderDetails',
            'shop.seller',
            'shop.translation' => fn($q) => $q->where('locale', $this->language),
            'user',
        ])
            ->where('shop_id', $this->shop->id)
            ->find($id);

        if (!$order) {
            return [
                'status'  => false,
                'code'    => ResponseError::ERROR_404,
                'message' => __('errors.' . ResponseError::ORDER_NOT_FOUND, locale: $this->language)
            ];
        }

        if (!$order->shop?->location) {
            return [
                'status'  => false,
                'code'    => ResponseError::ERROR_400,
                'message' => 'Shop location is incorrect'
            ];
        }

        if (!$order->location) {
            return [
                'status'  => false,
                'code'    => ResponseError::ERROR_400,
                'message' => 'Order location is incorrect'
            ];
        }

        return [
            'status' => true,
            'data'   => $order
        ];
    }

    /**
     * Display a listing of the resource.
     *
     * @param int $id
     * @return JsonResponse
     */
    public function checkPrice(int $id): JsonResponse
    {
        $result = $this->getOrder($id);

        if (!data_get($result, 'status')) {
            return $this->onErrorResponse($result);
        }

        /** @var Order $order */
        $order  = data_get($result, 'data');

        $result = $this->service->checkPrice($order, $order->shop?->location, $order->location);

        if (data_get($result, 'code') !== 200) {
            return new JsonResponse(data_get($result, 'data'), data_get($result, 'code'));
        }

        return $this->successResponse(
            __('errors.' . ResponseError::SUCCESS, locale: $this->language),
            data_get($result, 'data')
        );
    }

    /**
     * Display a listing of the resource.
     *
     * @param int $id
     * @return JsonResponse
     */
    public function createOrder(int $id): JsonResponse
    {
        $result = $this->getOrder($id);

        if (!data_get($result, 'status')) {
            return $this->onErrorResponse($result);
        }

        /** @var Order $order */
        $order  = data_get($result, 'data');

        $result = $this->service->createOrder($order);

        if (data_get($result, 'code') !== 200) {
            return new JsonResponse(data_get($result, 'data'), data_get($result, 'code'));
        }

        return $this->successResponse(
            __('errors.' . ResponseError::SUCCESS, locale: $this->language),
            data_get($result, 'data')
        );

    }

    /**
     * Display a listing of the resource.
     *
     * @param int $id
     * @return JsonResponse
     */
    public function getOrderInfo(int $id): JsonResponse
    {
        $result = $this->getOrder($id);

        if (!data_get($result, 'status')) {
            return $this->onErrorResponse($result);
        }

        /** @var Order $order */
        $order  = data_get($result, 'data');

        $result = $this->service->getOrderInfo($order);

        if (data_get($result, 'code') !== 200) {
            return new JsonResponse(data_get($result, 'data'), data_get($result, 'code'));
        }

        return $this->successResponse(
            __('errors.' . ResponseError::SUCCESS, locale: $this->language),
            data_get($result, 'data')
        );

    }

    /**
     * Display a listing of the resource.
     *
     * @param int $id
     * @return JsonResponse
     */
    public function acceptOrder(int $id): JsonResponse
    {
        $result = $this->getOrder($id);

        if (!data_get($result, 'status')) {
            return $this->onErrorResponse($result);
        }

        /** @var Order $order */
        $order  = data_get($result, 'data');

        $result = $this->service->acceptOrder($order);

        if (data_get($result, 'code') !== 200) {
            return new JsonResponse(data_get($result, 'data'), data_get($result, 'code'));
        }

        return $this->successResponse(
            __('errors.' . ResponseError::SUCCESS, locale: $this->language),
            data_get($result, 'data')
        );

    }

    /**
     * Display a listing of the resource.
     *
     * @param int $id
     * @return JsonResponse
     */
    public function cancelInfoOrder(int $id): JsonResponse
    {
        $result = $this->getOrder($id);

        if (!data_get($result, 'status')) {
            return $this->onErrorResponse($result);
        }

        /** @var Order $order */
        $order  = data_get($result, 'data');

        $result = $this->service->cancelInfoOrder($order);

        if (data_get($result, 'code') !== 200) {
            return new JsonResponse(data_get($result, 'data'), data_get($result, 'code'));
        }

        return $this->successResponse(
            __('errors.' . ResponseError::SUCCESS, locale: $this->language),
            data_get($result, 'data')
        );

    }

    /**
     * Display a listing of the resource.
     *
     * @param int $id
     * @return JsonResponse
     */
    public function cancelOrder(int $id): JsonResponse
    {
        $result = $this->getOrder($id);

        if (!data_get($result, 'status')) {
            return $this->onErrorResponse($result);
        }

        /** @var Order $order */
        $order  = data_get($result, 'data');

        $result = $this->service->cancelOrder($order);

        if (data_get($result, 'code') !== 200) {
            return new JsonResponse(data_get($result, 'data'), data_get($result, 'code'));
        }

        return $this->successResponse(
            __('errors.' . ResponseError::SUCCESS, locale: $this->language),
            data_get($result, 'data')
        );

    }

    /**
     * Display a listing of the resource.
     *
     * @param int $id
     * @return JsonResponse
     */
    public function orderDriver(int $id): JsonResponse
    {
        $result = $this->getOrder($id);

        if (!data_get($result, 'status')) {
            return $this->onErrorResponse($result);
        }

        /** @var Order $order */
        $order = data_get($result, 'data');

        $result = $this->service->orderDriverVoiceForwarding(data_get($order->yandex, 'request_id'));

        if (data_get($result, 'code') !== 200) {
            return new JsonResponse(data_get($result, 'data'), data_get($result, 'code'));
        }

        return $this->successResponse(
            __('errors.' . ResponseError::SUCCESS, locale: $this->language),
            data_get($result, 'data')
        );

    }

    /**
     * Display a listing of the resource.
     *
     * @param int $id
     * @return JsonResponse
     */
    public function orderDriverPosition(int $id): JsonResponse
    {
        $result = $this->getOrder($id);

        if (!data_get($result, 'status')) {
            return $this->onErrorResponse($result);
        }

        /** @var Order $order */
        $order = data_get($result, 'data');

        $result = $this->service->orderDriverPerformerPosition($order);

        if (data_get($result, 'code') !== 200) {
            return new JsonResponse(data_get($result, 'data'), data_get($result, 'code'));
        }

        return $this->successResponse(
            __('errors.' . ResponseError::SUCCESS, locale: $this->language),
            data_get($result, 'data')
        );

    }

    /**
     * Display a listing of the resource.
     *
     * @param int $id
     * @return JsonResponse
     */
    public function orderTrackingLinks(int $id): JsonResponse
    {
        $result = $this->getOrder($id);

        if (!data_get($result, 'status')) {
            return $this->onErrorResponse($result);
        }

        /** @var Order $order */
        $order = data_get($result, 'data');

        $result = $this->service->orderTrackingLinks(data_get($order->yandex, 'request_id'));

        if (data_get($result, 'code') !== 200) {
            return new JsonResponse(data_get($result, 'data'), data_get($result, 'code'));
        }

        return $this->successResponse(
            __('errors.' . ResponseError::SUCCESS, locale: $this->language),
            data_get($result, 'data')
        );

    }

    /**
     * Display a listing of the resource.
     *
     * @param int $id
     * @return JsonResponse
     */
    public function orderPointsEta(int $id): JsonResponse
    {
        $result = $this->getOrder($id);

        if (!data_get($result, 'status')) {
            return $this->onErrorResponse($result);
        }

        /** @var Order $order */
        $order = data_get($result, 'data');

        $result = $this->service->orderPointsEta(data_get($order->yandex, 'request_id'));

        if (data_get($result, 'code') !== 200) {
            return new JsonResponse(data_get($result, 'data'), data_get($result, 'code'));
        }

        return $this->successResponse(
            __('errors.' . ResponseError::SUCCESS, locale: $this->language),
            data_get($result, 'data')
        );

    }

}
