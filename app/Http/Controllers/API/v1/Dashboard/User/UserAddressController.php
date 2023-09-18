<?php

namespace App\Http\Controllers\API\v1\Dashboard\User;

use App\Helpers\ResponseError;
use App\Http\Requests\UserAddress\StoreRequest;
use App\Http\Requests\FilterParamsRequest;
use App\Http\Resources\UserAddressResource;
use App\Models\UserAddress;
use App\Repositories\UserAddressRepository\UserAddressRepository;
use App\Services\UserAddressService\UserAddressService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class UserAddressController extends UserBaseController
{
	public function __construct(
		private UserAddressService $service,
		private UserAddressRepository $repository
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
		$model = $this->repository->paginate($request->merge(['user_id' => auth('sanctum')->id()])->all());

		return UserAddressResource::collection($model);
	}

	/**
	 * Store a newly created resource in storage.
	 *
	 * @param StoreRequest $request
	 * @return JsonResponse
	 */
	public function store(StoreRequest $request): JsonResponse
	{
		$validated = $request->validated();
		$validated['user_id'] = auth('sanctum')->id();

		$result = $this->service->create($validated);

		if (!data_get($result, 'status')) {
			return $this->onErrorResponse($result);
		}

		return $this->successResponse(
			__('errors.' . ResponseError::RECORD_WAS_SUCCESSFULLY_CREATED, locale: $this->language),
			UserAddressResource::make(data_get($result, 'data'))
		);
	}

	/**
	 * @param int $id
	 * @return JsonResponse
	 */
	public function show(int $id): JsonResponse
	{
		$userAddress = UserAddress::find($id);

		if ($userAddress?->user_id !== auth('sanctum')->id()) {
			return $this->onErrorResponse([
				'code'      => ResponseError::ERROR_404,
				'message'   => __('errors.' . ResponseError::ERROR_404, locale: $this->language)
			]);
		}

		$result = $this->repository->show($userAddress);

		if ($result->user_id !== auth('sanctum')->id()) {
			return $this->onErrorResponse([
				'code'      => ResponseError::ERROR_404,
				'message'   => __('errors.' . ResponseError::ERROR_404, locale: $this->language)
			]);
		}

		return $this->successResponse(
			__('errors.' . ResponseError::SUCCESS, locale: $this->language),
			UserAddressResource::make($result)
		);
	}

	/**
	 * Update the specified resource in storage.
	 *
	 * @param int $id
	 * @param StoreRequest $request
	 * @return JsonResponse
	 */
	public function update(int $id, StoreRequest $request): JsonResponse
	{
		$validated = $request->validated();

		$userAddress = UserAddress::find($id);

		if ($userAddress?->user_id !== auth('sanctum')->id()) {
			return $this->onErrorResponse([
				'code'      => ResponseError::ERROR_404,
				'message'   => __('errors.' . ResponseError::ERROR_404, locale: $this->language)
			]);
		}

		$result = $this->service->update($userAddress, $validated);

		if (!data_get($result, 'status')) {
			return $this->onErrorResponse($result);
		}

		return $this->successResponse(
			__('errors.' . ResponseError::RECORD_WAS_SUCCESSFULLY_UPDATED, locale: $this->language),
			UserAddressResource::make(data_get($result, 'data'))
		);
	}

	/**
	 * Update the specified resource in storage.
	 *
	 * @param int $id
	 * @return JsonResponse
	 */
	public function setActive(int $id): JsonResponse
	{
		$result = $this->service->setActive($id, auth('sanctum')->id());

		if (!data_get($result, 'status')) {
			return $this->onErrorResponse($result);
		}

		return $this->successResponse(
			__('errors.' . ResponseError::RECORD_WAS_SUCCESSFULLY_UPDATED, locale: $this->language),
			UserAddressResource::make(data_get($result, 'data'))
		);
	}

	/**
	 * Update the specified resource in storage.
	 *
	 * @return JsonResponse
	 */
	public function getActive(): JsonResponse
	{
		$model = $this->repository->getActive(auth('sanctum')->id());

		return $this->successResponse(
			__('errors.' . ResponseError::RECORD_WAS_SUCCESSFULLY_UPDATED, locale: $this->language),
			UserAddressResource::make($model)
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
				'code'      => ResponseError::ERROR_404,
				'message'   => __('errors.' . ResponseError::ERROR_404, locale: $this->language)
			]);
		}

		return $this->successResponse(
			__('errors.' . ResponseError::RECORD_WAS_SUCCESSFULLY_DELETED, locale: $this->language)
		);
	}
}
