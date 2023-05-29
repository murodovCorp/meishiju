<?php

namespace App\Http\Controllers\API\v1\Dashboard\User\Booking;

use App\Helpers\ResponseError;
use App\Http\Controllers\API\v1\Dashboard\User\UserBaseController;
use App\Http\Requests\FilterParamsRequest;
use App\Http\Resources\Booking\ShopSectionResource;
use App\Models\Booking\ShopSection;
use App\Repositories\Booking\ShopSectionRepository\ShopSectionRepository;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class ShopSectionController extends UserBaseController
{
    public function __construct(private ShopSectionRepository $repository)
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

        return ShopSectionResource::collection($model);
    }

    /**
     * @param ShopSection $shopSection
     * @return JsonResponse
     */
    public function show(ShopSection $shopSection): JsonResponse
    {
        $result = $this->repository->show($shopSection);

        return $this->successResponse(
            __('errors.' . ResponseError::SUCCESS, locale: $this->language),
            ShopSectionResource::make($result)
        );
    }

}
