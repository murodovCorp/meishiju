<?php

namespace App\Http\Controllers\API\v1\Dashboard\Admin;

use App\Helpers\ResponseError;
use App\Http\Requests\FilterParamsRequest;
use App\Http\Requests\RequestModel\ChangeStatusRequest;
use App\Http\Requests\RequestModel\StoreRequest;
use App\Http\Resources\RequestModelResource;
use App\Models\RequestModel;
use App\Repositories\RequestModelRepository\RequestModelRepository;
use App\Services\RequestModelService\RequestModelService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class RequestModelController extends AdminBaseController
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
        $models = $this->repository->index($request->all());

        return RequestModelResource::collection($models);
    }

	/**
	 * Display the specified resource.
	 * @param RequestModel $requestModel
	 * @return JsonResponse
	 */
    public function show(RequestModel $requestModel): JsonResponse
    {
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
		$result = $this->service->update($requestModel, $request->validated());

		return $this->successResponse(
			__('errors.' . ResponseError::NO_ERROR, locale: $this->language),
			RequestModelResource::make(data_get($result, 'data'))
		);
	}

	/**
	 * Display the specified resource.
	 * @param int $id
	 * @param ChangeStatusRequest $request
	 * @return JsonResponse
	 */
	public function changeStatus(int $id, ChangeStatusRequest $request): JsonResponse
	{
		$result = $this->service->changeStatus($id, $request->validated());

		if (!data_get($result, 'status')) {
			return $this->onErrorResponse($result);
		}

		return $this->successResponse(__('errors.' . ResponseError::NO_ERROR, locale: $this->language));
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
				'code'      => data_get($result, 'code'),
				'message'   => data_get($result, 'message')
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
