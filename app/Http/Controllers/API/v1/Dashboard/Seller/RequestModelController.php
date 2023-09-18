<?php

namespace App\Http\Controllers\API\v1\Dashboard\Seller;

use App\Helpers\ResponseError;
use App\Http\Requests\FilterParamsRequest;
use App\Http\Requests\RequestModel\StoreRequest;
use App\Http\Resources\RequestModelResource;
use App\Models\RequestModel;
use App\Repositories\RequestModelRepository\RequestModelRepository;
use App\Services\RequestModelService\RequestModelService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class RequestModelController extends SellerBaseController
{
    public function __construct(
        private RequestModelRepository $repository,
        private RequestModelService $service,
    )
    {
        parent::__construct();
    }

    public function index(FilterParamsRequest $request): AnonymousResourceCollection
    {
        $models = $this->repository->index($request->merge(['created_by' => auth('sanctum')->id()])->all());

        return RequestModelResource::collection($models);
    }

	/**
	 * Display the specified resource.
	 * @param StoreRequest $request
	 * @return JsonResponse
	 */
    public function store(StoreRequest $request): JsonResponse
    {
		$validated = $request->validated();
		$validated['created_by'] = auth('sanctum')->id();

        $result = $this->service->create($validated);

        return $this->successResponse(
            __('errors.' . ResponseError::NO_ERROR, locale: $this->language),
			RequestModelResource::make(data_get($result, 'data'))
        );
    }

	/**
	 * Display the specified resource.
	 * @param RequestModel $requestModel
	 * @return JsonResponse
	 */
    public function show(RequestModel $requestModel): JsonResponse
    {

		if ($requestModel->created_by !== auth('sanctum')->id()) {
			return $this->onErrorResponse([
				'code'    => ResponseError::ERROR_404,
				'message' => __('errors.' . ResponseError::ERROR_404, locale: $this->language)
			]);
		}

        $model = $this->repository->show($requestModel);

        return $this->successResponse(
            __('errors.' . ResponseError::NO_ERROR, locale: $this->language),
			RequestModelResource::make($this->repository->show($model))
        );
    }

	/**
	 * Display the specified resource.
	 * @param RequestModel $requestModel
	 * @param StoreRequest $request
	 * @return JsonResponse
	 */
	public function update(RequestModel $requestModel, StoreRequest $request): JsonResponse
	{

		if ($requestModel->created_by !== auth('sanctum')->id()) {
			return $this->onErrorResponse([
				'code'    => ResponseError::ERROR_404,
				'message' => __('errors.' . ResponseError::ERROR_404, locale: $this->language)
			]);
		}

		$result = $this->service->update($requestModel, $request->validated());

		return $this->successResponse(
			__('errors.' . ResponseError::NO_ERROR, locale: $this->language),
			RequestModelResource::make(data_get($result, 'data'))
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
		$result = $this->service->delete($request->input('ids', []), auth('sanctum')->id());

		if (!data_get($result, 'status')) {
			return $this->onErrorResponse([
				'code'    => data_get($result, 'code'),
				'message' => data_get($result, 'message')
			]);
		}

		return $this->successResponse(
			__('errors.' . ResponseError::RECORD_WAS_SUCCESSFULLY_DELETED, locale: $this->language)
		);
	}

}
