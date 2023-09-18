<?php

namespace App\Http\Controllers\API\v1\Dashboard\Seller;

use App\Helpers\ResponseError;
use App\Http\Requests\FilterParamsRequest;
use App\Http\Resources\AdsPackageResource;
use App\Models\AdsPackage;
use App\Repositories\AdsPackageRepository\AdsPackageRepository;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class AdsPackageController extends SellerBaseController
{
    public function __construct(
        private AdsPackageRepository $repository,
    )
    {
        parent::__construct();
    }

    public function index(FilterParamsRequest $request): AnonymousResourceCollection
    {
        $models = $this->repository->paginate($request->all());

        return AdsPackageResource::collection($models);
    }

    /**
     * Display the specified resource.
     * @param AdsPackage $adsPackage
     * @return JsonResponse
     */
    public function show(AdsPackage $adsPackage): JsonResponse
    {
        $model = $this->repository->show($adsPackage);

        return $this->successResponse(
            __('errors.' . ResponseError::NO_ERROR, locale: $this->language),
            AdsPackageResource::make($this->repository->show($model))
        );
    }

}
