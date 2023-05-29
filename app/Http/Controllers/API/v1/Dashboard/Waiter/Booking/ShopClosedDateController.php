<?php

namespace App\Http\Controllers\API\v1\Dashboard\Waiter\Booking;

use App\Helpers\ResponseError;
use App\Http\Controllers\API\v1\Dashboard\Waiter\WaiterBaseController;
use App\Http\Requests\FilterParamsRequest;
use App\Http\Requests\Booking\ShopBookingClosedDate\SellerRequest;
use App\Http\Resources\Booking\BookingShopResource;
use App\Http\Resources\Booking\ShopBookingClosedDateResource;
use App\Repositories\Booking\ShopBookingClosedDateRepository\ShopBookingClosedDateRepository;
use App\Services\Booking\ShopBookingClosedDateService\ShopBookingClosedDateService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Artisan;

class ShopClosedDateController extends WaiterBaseController
{
    public function __construct(
        private ShopBookingClosedDateRepository $repository,
        private ShopBookingClosedDateService $service
    )
    {
        parent::__construct();
    }

    /**
     * @return JsonResponse
     */
    public function index(): JsonResponse
    {
        Artisan::call('remove:expired:closed:dates');

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
            return $this->onErrorResponse(['code' => ResponseError::ERROR_101]);
        }

        $shopClosedDate = $this->repository->show(auth('sanctum')->user()->invite?->shop_id);

        return $this->successResponse(ResponseError::NO_ERROR, [
            'booking_shop_closed_date'  => ShopBookingClosedDateResource::collection($shopClosedDate),
            'shop'                      => BookingShopResource::make($invite->shop),
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
            return $this->onErrorResponse(['code' => ResponseError::ERROR_101]);
        }

        $result = $this->service->update($invite?->shop_id, $request->validated());

        if (!data_get($result, 'status')) {
            return $this->onErrorResponse($result);
        }

        return $this->successResponse(
            __('errors.' . ResponseError::RECORD_WAS_SUCCESSFULLY_UPDATED, locale: $this->language)
        );
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
