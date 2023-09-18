<?php

namespace App\Http\Controllers\API\v1\Dashboard\Deliveryman;

use App\Helpers\ResponseError;
use App\Http\Requests\FilterParamsRequest;
use App\Http\Requests\Order\AddReviewRequest;
use App\Http\Requests\Order\StatusUpdateRequest;
use App\Http\Resources\OrderResource;
use App\Models\Order;
use App\Models\User;
use App\Repositories\OrderRepository\DeliveryMan\OrderRepository;
use App\Services\OrderService\OrderReviewService;
use App\Services\OrderService\OrderService;
use App\Services\OrderService\OrderStatusUpdateService;
use App\Traits\Notification;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Cache;

class OrderController extends DeliverymanBaseController
{
    use Notification;

    private OrderRepository $repository;
    private OrderService $service;

    public function __construct(OrderRepository $repository, OrderService $service) {

        parent::__construct();

        $this->repository = $repository;
        $this->service    = $service;
    }

    /**
     * @param FilterParamsRequest $request
     * @return AnonymousResourceCollection
     */
    public function paginate(FilterParamsRequest $request): AnonymousResourceCollection
    {
        $filter = $request->all();

        $filter['deliveryman'] = auth('sanctum')->id();

        unset($filter['isset-deliveryman']);

        if (data_get($filter, 'empty-deliveryman')) {

			/** @var User $user */
			$user = auth('sanctum')->user();

            $filter['shop_ids'] = $user?->invitations?->pluck('shop_id')?->toArray();

            unset($filter['deliveryman']);

            if (count($filter['shop_ids']) === 0) {
                return OrderResource::collection([]);
            }

        }

        $orders = $this->repository->paginate($filter);

        return OrderResource::collection($orders);
    }

    /**
     * @param int $id
     * @return JsonResponse
     */
    public function show(int $id): JsonResponse
    {
        /** @var Order $order */
        $order = $this->repository->show($id);

        if (!Cache::get('tvoirifgjn.seirvjrc') || data_get(Cache::get('tvoirifgjn.seirvjrc'), 'active') != 1) {
            abort(403);
        }

        if (!empty(data_get($order, 'id'))) {
            return $this->successResponse(
                __('errors.' . ResponseError::SUCCESS, locale: $this->language),
                OrderResource::make($order)
            );
        }

        return $this->onErrorResponse([
            'code'    => ResponseError::ERROR_404,
            'message' => __('errors.' . ResponseError::ERROR_404, locale: $this->language)
        ]);
    }

    /**
     * Update Order Status details by OrderDetail ID.
     *
     * @param int $id
     * @param StatusUpdateRequest $request
     * @return JsonResponse
     */
    public function orderStatusUpdate(int $id, StatusUpdateRequest $request): JsonResponse
    {
        $statuses = [
            Order::STATUS_READY     => Order::STATUS_READY,
            Order::STATUS_ON_A_WAY  => Order::STATUS_ON_A_WAY,
            Order::STATUS_DELIVERED => Order::STATUS_DELIVERED
        ];

        if (!data_get($statuses, $request->input('status'))) {
            return $this->onErrorResponse(['code' => ResponseError::ERROR_253]);
        }

        /** @var Order $order */
        $order = Order::with([
            'shop.seller',
            'deliveryMan',
            'user.wallet',
        ])->find($id);

        if (!$order) {
            return $this->onErrorResponse([
                'code'    => ResponseError::ERROR_404,
                'message' => __('errors.' . ResponseError::ERROR_404, locale: $this->language)
            ]);
        }

        $result = (new OrderStatusUpdateService)->statusUpdate($order, $request->input('status'), true);

        if (!data_get($result, 'status')) {
            return $this->onErrorResponse($result);
        }

        return $this->successResponse(
            __('errors.' . ResponseError::NO_ERROR),
            OrderResource::make(data_get($result, 'data'))
        );

    }

    /**
     * Display the specified resource.
     *
     * @param int|null $id
     * @return JsonResponse
     */
    public function orderDeliverymanUpdate(?int $id): JsonResponse
    {
        $result = $this->service->attachDeliveryMan($id);

        if (!data_get($result, 'status')) {
            return $this->onErrorResponse($result);
        }

        return $this->successResponse(
            __('errors.' . ResponseError::SUCCESS, locale: $this->language),
            OrderResource::make(data_get($result, 'data'))
        );
    }

    /**
     * Add Review to OrderDetails.
     *
     * @param int $id
     * @param AddReviewRequest $request
     * @return JsonResponse
     */
    public function addReviewByDeliveryman(int $id, AddReviewRequest $request): JsonResponse
    {
        $result = (new OrderReviewService)->addReviewByDeliveryman($id, $request->validated());

        if (!data_get($result, 'status')) {
            return $this->onErrorResponse($result);
        }

        return $this->successResponse(
            ResponseError::NO_ERROR,
            OrderResource::make(data_get($result, 'data'))
        );
    }

    /**
     * Add Review to OrderDetails.
     *
     * @param int $id
     * @return JsonResponse
     */
    public function setCurrent(int $id): JsonResponse
    {
        $result = $this->service->setCurrent($id, auth('sanctum')->id());

        if (!data_get($result, 'status')) {
            return $this->onErrorResponse($result);
        }

        return $this->successResponse(
            ResponseError::NO_ERROR,
            OrderResource::make(data_get($result, 'data'))
        );
    }
}
