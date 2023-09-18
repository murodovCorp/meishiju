<?php

namespace App\Http\Controllers\API\v1\Dashboard\Admin;

use App\Helpers\ResponseError;
use App\Http\Requests\FilterParamsRequest;
use App\Http\Requests\PaymentToPartner\StoreRequest;
use App\Http\Resources\PaymentToPartnerResource;
use App\Repositories\PaymentToPartnerRepository\PaymentToPartnerRepository;
use App\Services\PaymentToPartnerService\PaymentToPartnerService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class PaymentToPartnerController extends AdminBaseController
{

    public function __construct(
		private PaymentToPartnerRepository $repository,
		private PaymentToPartnerService $service
	)
    {
        parent::__construct();
    }

    public function index(FilterParamsRequest $request): AnonymousResourceCollection
    {
        $models = $this->repository->paginate($request->all());

        return PaymentToPartnerResource::collection($models);
    }

    public function storeMany(StoreRequest $request): JsonResponse
	{
		$result = $this->service->createMany($request->all());

		if (data_get($result, 'params')) {
			return $this->requestErrorResponse($result['status'], $result['message'], $result['params']);
		}

		if (!data_get($result, 'status')) {
			return $this->onErrorResponse($result);
		}

		return $this->successResponse(
			__('errors.' . ResponseError::NO_ERROR, locale: $this->language),
			data_get($result, 'data')
		);
    }

    public function show(int $id): JsonResponse
    {
        $model = $this->repository->show($id);

        if (empty($model)) {
            return $this->onErrorResponse([
                'code'      => ResponseError::ERROR_404,
                'message'   => __('errors.' . ResponseError::ERROR_404, locale: $this->language)
            ]);
        }

        return $this->successResponse(
			__('errors.' . ResponseError::NO_ERROR, locale: $this->language),
			PaymentToPartnerResource::make($model)
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
