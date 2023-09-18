<?php

namespace App\Http\Controllers\API\v1\Dashboard\Admin;

use App\Helpers\ResponseError;
use App\Http\Requests\Ads\ShopAdsUpdateRequest;
use App\Http\Requests\FilterParamsRequest;
use App\Http\Resources\ShopAdsPackageResource;
use App\Models\ShopAdsPackage;
use App\Repositories\AdsPackageRepository\ShopAdsPackageRepository;
use App\Services\AdsPackageService\ShopAdsPackageService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class ShopAdsPackageController extends AdminBaseController
{
    public function __construct(
        private ShopAdsPackageRepository $repository,
        private ShopAdsPackageService $service,
    )
    {
        parent::__construct();
    }

    public function index(FilterParamsRequest $request): AnonymousResourceCollection
    {
        $models = $this->repository->paginate($request->all());

        return ShopAdsPackageResource::collection($models);
    }

    /**
     * Display the specified resource.
     *
     * @param ShopAdsPackage $shopAdsPackage
     * @param ShopAdsUpdateRequest $request
     * @return JsonResponse
     */
    public function update(ShopAdsPackage $shopAdsPackage, ShopAdsUpdateRequest $request): JsonResponse
    {
        $result = $this->service->update($shopAdsPackage, $request->validated());

        if (!data_get($result, 'status')) {
            return $this->onErrorResponse($result);
        }

        return $this->successResponse(__('errors.' . ResponseError::NO_ERROR, locale: $this->language));
    }

    /**
     * Display the specified resource.
     * @param ShopAdsPackage $shopAdsPackage
     * @return JsonResponse
     */
    public function show(ShopAdsPackage $shopAdsPackage): JsonResponse
    {
        $model = $this->repository->show($shopAdsPackage);

        return $this->successResponse(
            __('errors.' . ResponseError::NO_ERROR, locale: $this->language),
            ShopAdsPackageResource::make($model)
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
