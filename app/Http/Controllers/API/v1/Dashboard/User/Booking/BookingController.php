<?php

namespace App\Http\Controllers\API\v1\Dashboard\User\Booking;

use App\Helpers\ResponseError;
use App\Http\Controllers\API\v1\Dashboard\User\UserBaseController;
use App\Http\Requests\FilterParamsRequest;
use App\Http\Resources\Booking\BookingResource;
use App\Repositories\Booking\BookingRepository\BookingRepository;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class BookingController extends UserBaseController
{
    public function __construct(private BookingRepository $repository)
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
        $model = $this->repository->paginate($request->all());

        return BookingResource::collection($model);
    }

    /**
     * @param int $shopId
     * @return JsonResponse
     */
    public function show(int $shopId): JsonResponse
    {
        return $this->successResponse(
            __('errors.' . ResponseError::SUCCESS, locale: $this->language),
            BookingResource::make($this->repository->showByShopId($shopId))
        );
    }
}
