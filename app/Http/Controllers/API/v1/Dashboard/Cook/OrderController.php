<?php

namespace App\Http\Controllers\API\v1\Dashboard\Cook;

use App\Helpers\ResponseError;
use App\Http\Requests\FilterParamsRequest;
use App\Http\Requests\Order\StatusUpdateRequest;
use App\Http\Requests\Order\StoreRequest;
use App\Http\Resources\OrderResource;
use App\Models\Order;
use App\Models\User;
use App\Repositories\OrderRepository\OrderRepository;
use App\Services\OrderService\OrderStatusUpdateService;
use App\Services\OrderService\OrderService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class OrderController extends CookBaseController
{
    public function __construct(
        private OrderRepository $repository,
        private OrderService $service
    ) {
        parent::__construct();
    }

    /**
     * @param FilterParamsRequest $request
     * @return AnonymousResourceCollection
     */
    public function paginate(FilterParamsRequest $request): AnonymousResourceCollection
    {
        $filter = $request->all();
        $filter['cook_id'] = auth('sanctum')->id();
        $filter['status']  = Order::STATUS_ACCEPTED;

        unset($filter['isset-cook']);

        if (data_get($filter, 'empty-cook')) {
            /** @var User $user */
            $user = auth('sanctum')->user();
            $filter['shop_ids'] = $user->invitations->pluck('shop_id')->toArray();
            unset($filter['cook_id']);
        }

        $orders = $this->repository->ordersPaginate($filter, with: [
            'shop:id,location,tax,background_img,logo_img',
            'shop.translation'      => fn($q) => $q->where('locale', $this->language),
            'currency'              => fn($q) => $q->select('id', 'title', 'symbol'),
            'user:id,firstname,lastname,img',
            'table:id,name,shop_section_id,chair_count,tax,active',
        ]);

        return OrderResource::collection($orders);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param StoreRequest $request
     * @return JsonResponse
     */
    public function store(StoreRequest $request): JsonResponse
    {
        $validated = $request->validated();
        $validated['cook_id'] = auth('sanctum')->id();

        $result = $this->service->create($validated);

        if (!data_get($result, 'status')) {
            return $this->onErrorResponse($result);
        }

        return $this->successResponse(
            __('errors.' . ResponseError::RECORD_WAS_SUCCESSFULLY_CREATED, locale: $this->language),
            $this->repository->reDataOrder(data_get($result, 'data')),
        );
    }

    /**
     * @param int $id
     * @return JsonResponse
     */
    public function show(int $id): JsonResponse
    {
        /** @var Order $order */
        $order = $this->repository->orderById($id);

        if (!empty(data_get($order, 'id'))) {
            return $this->successResponse(
                __('errors.' . ResponseError::SUCCESS, locale: $this->language),
                OrderResource::make($order)
            );
        }

        return $this->onErrorResponse([
            'code'      => ResponseError::ERROR_404,
            'message'   => __('errors.' . ResponseError::ERROR_404, locale: $this->language)
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
        $order = Order::with([
            'shop.seller',
            'waiter',
            'user.wallet',
        ])->find($id);

        if (!$order) {
            return $this->onErrorResponse([
                'code'    => ResponseError::ERROR_404,
                'message' => __('errors.' . ResponseError::ERROR_404, locale: $this->language)
            ]);
        }

        if (!$request->input('status')) {
            return $this->onErrorResponse();
        }

        /** @var Order $order */
        $result = (new OrderStatusUpdateService)->statusUpdate($order, $request->input('status'));

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
    public function orderCookUpdate(?int $id): JsonResponse
    {
        $result = $this->service->attachCook($id);

        if (!data_get($result, 'status')) {
            return $this->onErrorResponse($result);
        }

        return $this->successResponse(
            __('errors.' . ResponseError::SUCCESS, locale: $this->language),
            OrderResource::make(data_get($result, 'data'))
        );
    }
}
