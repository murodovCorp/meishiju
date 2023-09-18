<?php

namespace App\Http\Controllers\API\v1\Dashboard\Seller;

use App\Helpers\ResponseError;
use App\Http\Requests\Ads\ShopAdsStoreRequest;
use App\Http\Requests\FilterParamsRequest;
use App\Http\Resources\ShopAdsPackageResource;
use App\Models\ShopAdsPackage;
use App\Repositories\AdsPackageRepository\ShopAdsPackageRepository;
use App\Services\AdsPackageService\ShopAdsPackageService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class ShopAdsPackageController extends SellerBaseController
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
        $models = $this->repository->paginate($request->merge(['shop_id' => $this->shop->id])->all());

        return ShopAdsPackageResource::collection($models);
    }

    /**
     * Display the specified resource.
     *
     * @param ShopAdsStoreRequest $request
     * @return JsonResponse
     */
    public function store(ShopAdsStoreRequest $request): JsonResponse
    {
        $validated              = $request->validated();
        $validated['shop_id']   = $this->shop->id;

        $result = $this->service->create($validated);

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
        $model = $this->repository->shopShow($shopAdsPackage);

        if ($model->shop_id !== $this->shop->id) {
            return $this->onErrorResponse([
                'code'      => ResponseError::ERROR_404,
                'message'   => __('errors.' . ResponseError::ERROR_404, locale: $this->language)
            ]);
        }

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
        $result = $this->service->delete($request->input('ids', []), $this->shop->id);

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

}
