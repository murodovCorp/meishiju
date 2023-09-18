<?php

namespace App\Http\Controllers\API\v1\Rest;

use App\Helpers\ResponseError;
use App\Helpers\Utility;
use App\Http\Controllers\Controller;
use App\Http\Requests\FilterParamsRequest;
use App\Http\Requests\ParcelOrder\CalculatePriceRequest;
use App\Http\Resources\ParcelOrderSettingResource;
use App\Models\ParcelOrderSetting;
use App\Repositories\ParcelOrderSettingRepository\ParcelOrderSettingRepository;
use App\Traits\ApiResponse;
use App\Traits\SetCurrency;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class ParcelOrderSettingController extends Controller
{
    use ApiResponse, SetCurrency;

    public function __construct(
        private ParcelOrderSettingRepository $repository,
    )
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
        $parcels = $this->repository->restPaginate($request->all());

        return ParcelOrderSettingResource::collection($parcels);
    }

    /**
     * Display a listing of the resource.
     *
     * @param int $id
     * @return JsonResponse
     */
    public function show(int $id): JsonResponse
    {
        $parcel = $this->repository->showById($id);

        return $this->successResponse(
            __('errors.' . ResponseError::SUCCESS, locale: $this->language),
            $parcel ? ParcelOrderSettingResource::make($parcel) : null
        );
    }

    public function calculatePrice(CalculatePriceRequest $request): JsonResponse
    {
        $type = ParcelOrderSetting::find($request->input('type_id'));

        $helper = new Utility;
        $km     = $helper->getDistance($request->input('address_from', []), $request->input('address_to', []));

        if ($km > $type->max_range) {
            return $this->onErrorResponse([
                'code'    => ResponseError::ERROR_433,
                'message' => __('errors.' . ResponseError::NOT_IN_PARCEL_POLYGON, ['km' => $type->max_range], $this->language),
            ]);
        }

        $deliveryFee = $helper->getParcelPriceByDistance($type, $km, $this->currency());

        return $this->successResponse(
            __('errors.' . ResponseError::SUCCESS, locale: $this->language),
            [
                'price' => $deliveryFee,
                'km'    => $km,
            ]
        );
    }
}
