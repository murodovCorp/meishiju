<?php

namespace App\Http\Controllers\API\v1\Rest;

use App\Helpers\ResponseError;
use App\Http\Requests\Order\AddReviewRequest;
use App\Http\Requests\Order\StoreRequest;
use App\Http\Requests\Order\UpdateTipsRequest;
use App\Http\Resources\UserResource;
use App\Models\Order;
use App\Repositories\OrderRepository\OrderRepository;
use App\Services\OrderService\OrderReviewService;
use App\Services\OrderService\OrderService;
use Illuminate\Http\JsonResponse;

class OrderController extends RestBaseController
{
    public function __construct(
        private OrderRepository $orderRepository,
        private OrderService $orderService
    )
    {
        parent::__construct();
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

        $result = $this->orderService->create($validated);

        if (!data_get($result, 'status')) {
            return $this->onErrorResponse($result);
        }

        return $this->successResponse(
            __('errors.' . ResponseError::RECORD_WAS_SUCCESSFULLY_CREATED, locale: $this->language),
            $this->orderRepository->reDataOrder(data_get($result, 'data'))
        );
    }

    /**
     * Display the specified resource.
     *
     * @param int $id
     * @return JsonResponse
     */
    public function show(int $id): JsonResponse
    {
        $order = $this->orderRepository->orderById($id);

        return $this->successResponse(ResponseError::NO_ERROR, $this->orderRepository->reDataOrder($order));
    }

    /**
     * Display the specified resource.
     *
     * @param int $id
     * @param UpdateTipsRequest $request
     * @return JsonResponse
     */
    public function updateTips(int $id, UpdateTipsRequest $request): JsonResponse
    {
        $order = $this->orderService->updateTips($id, $request->validated());

        return $this->successResponse(ResponseError::NO_ERROR, $this->orderRepository->reDataOrder($order));
    }

    /**
     * Add Review to Order.
     *
     * @param int $id
     * @param AddReviewRequest $request
     * @return JsonResponse
     */
    public function addOrderReview(int $id, AddReviewRequest $request): JsonResponse
    {
        /** @var Order $order */
        $order = Order::with(['review', 'reviews'])->find($id);

        $result = (new OrderReviewService)->addReview($order, $request->validated());

        if (!data_get($result, 'status')) {
            return $this->onErrorResponse($result);
        }

        return $this->successResponse(
            ResponseError::NO_ERROR,
            $this->orderRepository->reDataOrder(data_get($result, 'data'))
        );

    }

	/**
	 * Display the specified resource.
	 *
	 * @param int $id
	 * @return JsonResponse
	 */
	public function showByTableId(int $id): JsonResponse
	{
		$order = $this->orderRepository->orderByTableId($id);

		return $this->successResponse(ResponseError::NO_ERROR, $this->orderRepository->reDataOrder($order));
	}

	/**
	 * Display the specified resource.
	 *
	 * @param int $id
	 * @return JsonResponse
	 */
	public function showDeliveryman(int $id): JsonResponse
	{
        $user = $this->orderRepository->showDeliveryman($id);

        if (empty($user)) {
            return $this->onErrorResponse([
                'code' => ResponseError::ERROR_404,
                'message' => __('errors.' . ResponseError::ERROR_404, locale: $this->language)
            ]);
        }

		return $this->successResponse(ResponseError::NO_ERROR, UserResource::make($user));
	}

}
