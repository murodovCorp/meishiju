<?php

namespace App\Http\Controllers\API\v1\Rest;

use App\Helpers\ResponseError;
use App\Http\Controllers\Controller;
use App\Http\Requests\Yandex\CheckPriceRequest;
use App\Http\Requests\Yandex\DeliveryMethodRequest;
use App\Services\YandexService\YandexService;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Log;

class YandexController extends Controller
{
    use ApiResponse;

    public function __construct(private YandexService $service)
    {
        parent::__construct();
    }

    public function deliveryMethods(DeliveryMethodRequest $request): JsonResponse
    {
        $result = $this->service->baseInfoMethods($request->validated(), 'v1/delivery-methods');

        return $this->successResponse(__('errors.' . ResponseError::SUCCESS, locale: $this->language), $result);
    }

    public function tariffs(DeliveryMethodRequest $request): JsonResponse
    {
        $result = $this->service->baseInfoMethods($request->validated(), 'v2/tariffs');

        return $this->successResponse(__('errors.' . ResponseError::SUCCESS, locale: $this->language), $result);
    }

    public function checkPrice(CheckPriceRequest $request): JsonResponse
    {
        $result = $this->service->checkPrice($request->validated());

        return $this->successResponse(__('errors.' . ResponseError::SUCCESS, locale: $this->language), $result);
    }

    /**
     * @param Request $request
     * @return void
     */
    public function webhook(Request $request): void
    {
        Log::error('yandex', $request->all());
    }

}
