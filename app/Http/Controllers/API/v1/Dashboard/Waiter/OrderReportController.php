<?php

namespace App\Http\Controllers\API\v1\Dashboard\Waiter;

use App\Helpers\ResponseError;
use App\Http\Requests\Order\SellerOrderReportRequest;
use App\Repositories\OrderRepository\Waiter\OrderReportRepository;
use Illuminate\Http\JsonResponse;

class OrderReportController extends WaiterBaseController
{
    /**
     * @param OrderReportRepository $repository
     */
    public function __construct(
        private OrderReportRepository $repository
    )
    {
        parent::__construct();
    }

    public function report(SellerOrderReportRequest $request): JsonResponse
    {
        $validated = $request->validated();
        $validated['waiter_id'] = auth('sanctum')->id();

        return $this->successResponse(
            __('errors.' . ResponseError::SUCCESS, locale: $this->language),
            $this->repository->report($validated)
        );
    }
}
