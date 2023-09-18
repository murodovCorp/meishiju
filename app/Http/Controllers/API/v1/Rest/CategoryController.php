<?php

namespace App\Http\Controllers\API\v1\Rest;

use App\Helpers\ResponseError;
use App\Http\Requests\CategoryFilterRequest;
use App\Http\Resources\CategoryResource;
use App\Models\Category;
use App\Repositories\CategoryRepository\RestCategoryRepository;
use App\Repositories\Interfaces\CategoryRepoInterface;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class CategoryController extends RestBaseController
{

	public function __construct(
		private CategoryRepoInterface $repository,
		private RestCategoryRepository $restRepository
	)
	{
		parent::__construct();
	}

	public function parentCategory(CategoryFilterRequest $request): JsonResponse
	{
		$categories = $this->restRepository->parentCategories($request->merge(['active' => 1])->all());

		return $this->successResponse(
			__('errors.' . ResponseError::NO_ERROR),
			CategoryResource::collection($categories)
		);
	}

	public function childrenCategory(int $id): JsonResponse
	{
		$childrenCategories = $this->repository->childrenCategory($id);

		if (!$childrenCategories) {
			return $this->onErrorResponse(['code' => ResponseError::ERROR_404]);
		}

		return $this->successResponse(
			__('errors.' . ResponseError::NO_ERROR),
			CategoryResource::make($childrenCategories)
		);
	}

	/**
	 * Display a listing of the resource.
	 *
	 * @param CategoryFilterRequest $request
	 * @return AnonymousResourceCollection
	 */

	public function paginate(CategoryFilterRequest $request): AnonymousResourceCollection
	{
		$filter = $request
			->merge([
				'active' => 1,
				'has_products' => in_array($request->input('type'), ['main', 'sub_main'])
			])
			->all();

		$categories = $this->restRepository->parentCategories($filter);

		return CategoryResource::collection($categories);
	}

	/**
	 * Display a listing of the resource.
	 *
	 * @param CategoryFilterRequest $request
	 * @return AnonymousResourceCollection
	 */

	public function selectPaginate(CategoryFilterRequest $request): AnonymousResourceCollection
	{
		$categories = $this->repository->selectPaginate($request->merge(['active' => 1])->all());

		return CategoryResource::collection($categories);
	}

	/**
	 * Search Model by tag name.
	 *
	 * @param CategoryFilterRequest $request
	 * @return AnonymousResourceCollection
	 */
	public function categoriesSearch(CategoryFilterRequest $request): AnonymousResourceCollection
	{
		$categories = $this->repository->categoriesSearch($request->merge(['active' => 1])->all());

		return CategoryResource::collection($categories);
	}

	/**
	 * Display the specified resource.
	 *
	 * @param string $uuid
	 * @return JsonResponse
	 */
	public function show(string $uuid): JsonResponse
	{
		$category = $this->repository->categoryByUuid($uuid);

		if (!$category) {
			return $this->onErrorResponse(['code' => ResponseError::ERROR_404]);
		}

		return $this->successResponse(__('errors.' . ResponseError::NO_ERROR), CategoryResource::make($category));
	}

	public function shopCategoryProduct(Request $request): AnonymousResourceCollection
	{
		$categories = $this->repository->shopCategoryProduct($request->all());

		return CategoryResource::collection($categories);
	}

	public function shopCategory(Request $request): AnonymousResourceCollection
	{
		$categories = $this->repository->shopCategory($request->all());

		return CategoryResource::collection($categories);
	}

	public function types(): JsonResponse
	{
		return $this->successResponse(
			__('errors.' . ResponseError::NO_ERROR),
			array_keys(Category::TYPES)
		);
	}
}
