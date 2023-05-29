<?php

namespace App\Http\Controllers\API\v1\Dashboard\Waiter\Booking;

use App\Helpers\ResponseError;
use App\Http\Controllers\API\v1\Dashboard\Waiter\WaiterBaseController;
use App\Http\Requests\Booking\Booking\AdminUserBookingStoreRequest;
use App\Http\Requests\Booking\Booking\StatusUpdateRequest;
use App\Http\Requests\Booking\Booking\UserBookingStoreRequest;
use App\Http\Requests\FilterParamsRequest;
use App\Http\Resources\Booking\UserBookingResource;
use App\Models\Booking\UserBooking;
use App\Repositories\Booking\BookingRepository\UserBookingRepository;
use App\Services\Booking\BookingService\UserBookingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class UserBookingController extends WaiterBaseController
{
    public function __construct(private UserBookingService $service, private UserBookingRepository $repository)
    {
        parent::__construct();
    }

    /**
     * Display a listing of the resource.
     *
     * @param FilterParamsRequest $request
     * @return AnonymousResourceCollection
     */
    public function index(FilterParamsRequest $request): AnonymousResourceCollection
    {
        $model = $this->repository->paginate($request->merge(['shop_id' => auth('sanctum')->user()->invite?->shop_id])->all());

        return UserBookingResource::collection($model);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param UserBookingStoreRequest $request
     * @return JsonResponse
     */
    public function store(UserBookingStoreRequest $request): JsonResponse
    {
        $validated = $request->validated();
        $validated['user_id'] = auth('sanctum')->id();

        $result = $this->service->create($validated);

        if (!data_get($result, 'status')) {
            return $this->onErrorResponse($result);
        }

        return $this->successResponse(
            __('errors.' . ResponseError::RECORD_WAS_SUCCESSFULLY_CREATED, locale: $this->language),
            UserBookingResource::make(data_get($result, 'data'))
        );
    }

    /**
     * @param UserBooking $userBooking
     * @return JsonResponse
     */
    public function show(UserBooking $userBooking): JsonResponse
    {
        if ($userBooking->booking?->shop_id !== auth('sanctum')->user()->invite?->shop_id) {
            return $this->onErrorResponse([
                'code'      => ResponseError::ERROR_404,
                'message'   => __('errors.' . ResponseError::ERROR_404, locale: $this->language)
            ]);
        }

        return $this->successResponse(
            __('errors.' . ResponseError::SUCCESS, locale: $this->language),
            UserBookingResource::make($userBooking)
        );
    }

    /**
     * Update the specified resource in storage.
     *
     * @param UserBooking $userBooking
     * @param AdminUserBookingStoreRequest $request
     * @return JsonResponse
     */
    public function update(UserBooking $userBooking, AdminUserBookingStoreRequest $request): JsonResponse
    {
        if ($userBooking->booking?->shop_id !== auth('sanctum')->user()->invite?->shop_id) {
            return $this->onErrorResponse([
                'code'      => ResponseError::ERROR_404,
                'message'   => __('errors.' . ResponseError::ERROR_404, locale: $this->language)
            ]);
        }

        $validated = $request->validated();

        $result = $this->service->update($userBooking, $validated);

        if (!data_get($result, 'status')) {
            return $this->onErrorResponse($result);
        }

        return $this->successResponse(
            __('errors.' . ResponseError::RECORD_WAS_SUCCESSFULLY_UPDATED, locale: $this->language),
            UserBookingResource::make(data_get($result, 'data'))
        );
    }

    /**
     * @param int $id
     * @param StatusUpdateRequest $request
     * @return JsonResponse
     */
    public function statusUpdate(int $id, StatusUpdateRequest $request): JsonResponse
    {
        $result = $this->service->statusUpdate($id, $request->validated());

        if (!data_get($result, 'status')) {
            return $this->onErrorResponse($result);
        }

        return $this->successResponse(
            __('errors.' . ResponseError::RECORD_WAS_SUCCESSFULLY_UPDATED, locale: $this->language),
            UserBookingResource::make(data_get($result, 'data'))
        );
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param FilterParamsRequest $request
     * @return JsonResponse
     */
    public function destroy(FilterParamsRequest $request): JsonResponse
    {
        $result = $this->service->delete($request->input('ids', []));

        if (!data_get($result, 'status')) {
            return $this->onErrorResponse([
                'code'      => ResponseError::ERROR_404,
                'message'   => __('errors.' . ResponseError::ERROR_404, locale: $this->language)
            ]);
        }

        return $this->successResponse(
            __('errors.' . ResponseError::RECORD_WAS_SUCCESSFULLY_DELETED, locale: $this->language)
        );
    }

    /**
     * @return JsonResponse
     */
    public function dropAll(): JsonResponse
    {
        $this->service->dropAll();

        return $this->successResponse(
            __('errors.' . ResponseError::RECORD_WAS_SUCCESSFULLY_DELETED, locale: $this->language)
        );
    }

    /**
     * @return JsonResponse
     */
    public function truncate(): JsonResponse
    {
        $this->service->truncate();

        return $this->successResponse(
            __('errors.' . ResponseError::RECORD_WAS_SUCCESSFULLY_DELETED, locale: $this->language)
        );
    }

    /**
     * @return JsonResponse
     */
    public function restoreAll(): JsonResponse
    {
        $this->service->restoreAll();

        return $this->successResponse(
            __('errors.' . ResponseError::RECORD_WAS_SUCCESSFULLY_DELETED, locale: $this->language)
        );
    }

}
