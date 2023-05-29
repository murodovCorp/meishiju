<?php

namespace App\Http\Controllers\API\v1\Dashboard\Seller\Booking;

use App\Helpers\ResponseError;
use App\Http\Controllers\API\v1\Dashboard\Seller\SellerBaseController;
use App\Http\Requests\FilterParamsRequest;
use App\Http\Requests\Booking\ShopBookingWorkingDay\SellerRequest;
use App\Http\Resources\Booking\BookingResource;
use App\Http\Resources\Booking\ShopBookingWorkingDayResource;
use App\Repositories\Booking\ShopBookingWorkingDayRepository\ShopBookingWorkingDayRepository;
use App\Services\Booking\ShopBookingWorkingDayService\ShopBookingWorkingDayService;
use Illuminate\Http\JsonResponse;

class ShopWorkingDayController extends SellerBaseController
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
        return $this->show($this->shop->uuid);
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
        $validated['shop_id'] = $this->shop->id;
        $result = $this->service->create($validated);

        if (!data_get($result, 'status')) {
            return $this->onErrorResponse($result);
        }

        return $this->successResponse(ResponseError::NO_ERROR, []);
    }

    /**
     * Display the specified resource.
     *
     * @param string $uuid
     * @return JsonResponse
     */
    public function show(string $uuid): JsonResponse
    {
        if ($this->shop->uuid != $uuid) {
            return $this->onErrorResponse([
                'code'    => ResponseError::ERROR_101,
                'message' => __('errors.' . ResponseError::ERROR_101, locale: $this->language)
            ]);
        }

        $models = $this->repository->show($this->shop->id);

        return $this->successResponse(ResponseError::NO_ERROR, [
            'dates' => ShopBookingWorkingDayResource::collection($models),
            'shop'  => BookingResource::make($this->shop),
        ]);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param string $uuid
     * @param SellerRequest $request
     * @return JsonResponse
     */
    public function update(string $uuid, SellerRequest $request): JsonResponse
    {
        if ($this->shop->uuid != $uuid) {
            return $this->onErrorResponse([
                'code'    => ResponseError::ERROR_101,
                'message' => __('errors.' . ResponseError::ERROR_101, locale: $this->language)
            ]);
        }

        $result = $this->service->update($this->shop->id, $request->validated());

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
        $this->service->delete($request->input('ids', []), $this->shop->id);

        return $this->successResponse(
            __('errors.' . ResponseError::RECORD_WAS_SUCCESSFULLY_DELETED, locale: $this->language)
        );
    }
}
