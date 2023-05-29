<?php

namespace App\Http\Controllers\API\v1\Dashboard\Waiter\Booking;

use App\Helpers\ResponseError;
use App\Http\Controllers\API\v1\Dashboard\Waiter\WaiterBaseController;
use App\Http\Requests\FilterParamsRequest;
use App\Http\Requests\Booking\ShopBookingWorkingDay\SellerRequest;
use App\Http\Resources\Booking\BookingResource;
use App\Http\Resources\Booking\ShopBookingWorkingDayResource;
use App\Repositories\Booking\ShopBookingWorkingDayRepository\ShopBookingWorkingDayRepository;
use App\Services\Booking\ShopBookingWorkingDayService\ShopBookingWorkingDayService;
use Illuminate\Http\JsonResponse;

class ShopWorkingDayController extends WaiterBaseController
{
    public function __construct(
        private ShopBookingWorkingDayRepository $repository,
        private ShopBookingWorkingDayService $service
    )
    {
        parent::__construct();
    }

    /**
     * Display a listing of the resource.
     *
     * @return JsonResponse
     */
    public function index(): JsonResponse
    {
        return $this->show(auth('sanctum')->user()->invite?->shop?->uuid);
    }

    /**
     * NOT USED
     * Display the specified resource.
     *
     * @param SellerRequest $request
     * @return JsonResponse
     */
    public function store(SellerRequest $request): JsonResponse
    {
        $validated = $request->validated();
        $validated['shop_id'] = auth('sanctum')->user()->invite?->shop_id;
        $result = $this->service->create($validated);

        if (!data_get($result, 'status')) {
            return $this->onErrorResponse($result);
        }

        return $this->successResponse(ResponseError::NO_ERROR, []);
    }

    /**
     * Display the specified resource.
     *
     * @param int $id
     * @return JsonResponse
     */
    public function show(int $id): JsonResponse
    {
        $invite = auth('sanctum')->user()->invite;

        if ($invite?->shop_id !== $id) {
            return $this->onErrorResponse([
                'code'    => ResponseError::ERROR_101,
                'message' => __('errors.' . ResponseError::ERROR_101, locale: $this->language)
            ]);
        }

        $models = $this->repository->show($invite?->shop_id);

        return $this->successResponse(ResponseError::NO_ERROR, [
            'dates' => ShopBookingWorkingDayResource::collection($models),
            'shop'  => BookingResource::make($invite?->shop),
        ]);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param int $id
     * @param SellerRequest $request
     * @return JsonResponse
     */
    public function update(int $id, SellerRequest $request): JsonResponse
    {
        $invite = auth('sanctum')->user()->invite;

        if ($invite?->shop_id !== $id) {
            return $this->onErrorResponse([
                'code'    => ResponseError::ERROR_101,
                'message' => __('errors.' . ResponseError::ERROR_101, locale: $this->language)
            ]);
        }

        $result = $this->service->update($invite?->shop_id, $request->validated());

        if (!data_get($result, 'status')) {
            return $this->onErrorResponse($result);
        }

        return $this->successResponse(__('errors.' . ResponseError::RECORD_WAS_SUCCESSFULLY_CREATED, locale: $this->language));
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param FilterParamsRequest $request
     * @return JsonResponse
     */
    public function destroy(FilterParamsRequest $request): JsonResponse
    {
        $this->service->delete($request->input('ids', []), auth('sanctum')->user()->invite?->shop_id);

        return $this->successResponse(
            __('errors.' . ResponseError::RECORD_WAS_SUCCESSFULLY_DELETED, locale: $this->language)
        );
    }
}
