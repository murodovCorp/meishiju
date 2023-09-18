<?php

namespace App\Http\Controllers\API\v1\Dashboard\Waiter;

use App\Helpers\ResponseError;
use App\Http\Requests\FilterParamsRequest;
use App\Http\Requests\Order\AddReviewRequest;
use App\Http\Requests\Order\StatusUpdateRequest;
use App\Http\Requests\Order\StoreRequest;
use App\Http\Resources\OrderResource;
use App\Models\Order;
use App\Models\User;
use App\Repositories\DashboardRepository\DashboardRepository;
use App\Repositories\OrderRepository\OrderRepository;
use App\Services\OrderService\OrderReviewService;
use App\Services\OrderService\OrderStatusUpdateService;
use App\Services\OrderService\OrderService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class OrderController extends WaiterBaseController
{
    public function __construct(
        private OrderRepository $repository,
        private OrderService $service
    ) {
        parent::__construct();
    }

    /**
     * @param Request $request
     * @return JsonResponse
     */
    public function paginate(Request $request): JsonResponse
    {
        $filter = $request->all();
        $filter['waiter_id'] = auth('sanctum')->id();
        unset($filter['isset-waiter']);

        if (data_get($filter, 'empty-waiter')) {
            /** @var User $user */
            $user = auth('sanctum')->user();
            $filter['shop_ids'] = $user?->invitations?->pluck('shop_id')?->toArray();
            unset($filter['shop_id']);
            unset($filter['waiter_id']);
        }

        $orders = $this->repository->ordersPaginate($filter);

        $statistic = (new DashboardRepository)->orderByStatusStatistics($filter);

        $lastPage = (new DashboardRepository)->getLastPage(
            data_get($filter, 'perPage', 10),
            $statistic,
            data_get($filter, 'status')
        );

        return $this->successResponse(__('errors.' . ResponseError::SUCCESS, locale: $this->language), [
            'statistic' => $statistic,
            'orders'    =>  OrderResource::collection($orders),
            'meta'      => [
                'current_page'  => (int)data_get($filter, 'page', 1),
                'per_page'      => (int)data_get($filter, 'perPage', 10),
                'last_page'     => $lastPage,
                'total'         => (int)data_get($statistic, 'total', 0),
            ],
        ]);
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
        $validated['waiter_id'] = auth('sanctum')->id();

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

        if (empty(data_get($order, 'id'))) {
            return $this->onErrorResponse([
                'code'      => ResponseError::ERROR_404,
                'message'   => __('errors.' . ResponseError::ERROR_404, locale: $this->language)
            ]);
        }

        return $this->successResponse(
            __('errors.' . ResponseError::SUCCESS, locale: $this->language),
            OrderResource::make($order->loadMissing(['table']))
        );
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
        /** @var Order $order */
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
    public function orderWaiterUpdate(?int $id): JsonResponse
    {
        $result = $this->service->attachWaiter($id);

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
    public function addReviewByWaiter(int $id, AddReviewRequest $request): JsonResponse
    {
        $result = (new OrderReviewService)->addReviewByWaiter($id, $request->validated());

        if (!data_get($result, 'status')) {
            return $this->onErrorResponse($result);
        }

        return $this->successResponse(
            ResponseError::NO_ERROR,
            OrderResource::make(data_get($result, 'data'))
        );
    }

    public function countStatistics(FilterParamsRequest $request): JsonResponse
    {
        $filter = $request->merge(['deliveryman' => auth('sanctum')->id()])->all();

        return $this->successResponse(
            __('errors.' . ResponseError::SUCCESS, locale: $this->language),
            (new DashboardRepository)->OrderByStatusStatistics($filter)
        );
    }
}
