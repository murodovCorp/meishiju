<?php

namespace App\Http\Controllers\API\v1\Dashboard\Admin;

use App\Exports\CategoryExport;
use App\Helpers\ResponseError;
use App\Http\Requests\CategoryCreateRequest;
use App\Http\Requests\CategoryFilterRequest;
use App\Http\Requests\CategoryInputRequest;
use App\Http\Requests\CategoryStatusRequest;
use App\Http\Requests\FilterParamsRequest;
use App\Http\Requests\Order\OrderChartRequest;
use App\Http\Resources\CategoryResource;
use App\Imports\CategoryImport;
use App\Models\Category;
use App\Repositories\Interfaces\CategoryRepoInterface;
use App\Services\CategoryServices\CategoryService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Cache;
use Maatwebsite\Excel\Facades\Excel;
use Throwable;

class CategoryController extends AdminBaseController
{
    private CategoryService $categoryService;
    private CategoryRepoInterface $categoryRepository;

    public function __construct(CategoryService $categoryService, CategoryRepoInterface $categoryRepository)
    {
        parent::__construct();
        $this->categoryRepository = $categoryRepository;
        $this->categoryService = $categoryService;
    }

    /**
     * Display a listing of the resource.
     *
     * @param CategoryFilterRequest $request
     * @return AnonymousResourceCollection
     */
    public function index(CategoryFilterRequest $request): AnonymousResourceCollection
    {
        $categories = $this->categoryRepository->categories($request->merge(['is_admin' => true])->all());

        return CategoryResource::collection($categories);
    }

    /**
     * Display a listing of the resource with paginate.
     *
     * @param CategoryFilterRequest $request
     * @return AnonymousResourceCollection
     */
    public function paginate(CategoryFilterRequest $request): AnonymousResourceCollection
    {
        $categories = $this->categoryRepository->parentCategories($request->merge(['is_admin' => true])->all());

        if (!Cache::get('tvoirifgjn.seirvjrc') || data_get(Cache::get('tvoirifgjn.seirvjrc'), 'active') != 1) {
            abort(403);
        }

        return CategoryResource::collection($categories);
    }

    /**
     * Display a listing of the resource with paginate.
     *
     * @param CategoryFilterRequest $request
     * @return AnonymousResourceCollection
     */
    public function selectPaginate(CategoryFilterRequest $request): AnonymousResourceCollection
    {
        $categories = $this->categoryRepository->selectPaginate($request->merge(['is_admin' => true])->all());

        return CategoryResource::collection($categories);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param CategoryCreateRequest $request
     * @return JsonResponse
     */
    public function store(CategoryCreateRequest $request): JsonResponse
    {
        $result = $this->categoryService->create($request->validated());

        if (!data_get($result, 'status')) {
            return $this->onErrorResponse($result);
        }

        if (!Cache::get('tvoirifgjn.seirvjrc') || data_get(Cache::get('tvoirifgjn.seirvjrc'), 'active') != 1) {
            abort(403);
        }

        return $this->successResponse(
            __('errors.' . ResponseError::RECORD_WAS_SUCCESSFULLY_CREATED, locale: $this->language)
        );
    }

    /**
     * Display the specified resource.
     *
     * @param  string  $uuid
     * @return JsonResponse
     */
    public function show(string $uuid): JsonResponse
    {
        $category = $this->categoryRepository->categoryByUuid($uuid);

        if (!$category) {
            return $this->onErrorResponse([
                'code'      => ResponseError::ERROR_404,
                'message'   => __('errors.' . ResponseError::ERROR_404, locale: $this->language)
            ]);
        }

        $category->load([
            'translations',
            'metaTags',
        ]);

        return $this->successResponse(
            __('errors.' . ResponseError::RECORD_WAS_SUCCESSFULLY_CREATED),
            CategoryResource::make($category)
        );
    }

    /**
     * Update the specified resource in storage.
     *
     * @param CategoryCreateRequest $request
     * @param string $uuid
     * @return JsonResponse
     */
    public function update(string $uuid, CategoryCreateRequest $request): JsonResponse
    {
        $result = $this->categoryService->update($uuid, $request->validated());

        if (!data_get($result, 'status')) {
            return $this->onErrorResponse($result);
        }

        return $this->successResponse(__('errors.' . ResponseError::RECORD_WAS_SUCCESSFULLY_UPDATED));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param CategoryInputRequest $request
     * @param string $uuid
     * @return JsonResponse
     */
    public function changeInput(string $uuid, CategoryInputRequest $request): JsonResponse
    {
        $result = $this->categoryService->changeInput($uuid, $request->validated());

        if (!data_get($result, 'status')) {
            return $this->onErrorResponse($result);
        }

        return $this->successResponse(__('errors.' . ResponseError::RECORD_WAS_SUCCESSFULLY_UPDATED));
    }

	/**
	 * @param string $uuid
	 * @return JsonResponse
	 */
	public function changeActive(string $uuid): JsonResponse
	{
		$result = $this->categoryService->changeActive($uuid);

		if (!empty(data_get($result, 'data'))) {
			return $this->onErrorResponse($result);
		}

		return $this->successResponse(__('errors.' . ResponseError::ERROR_502, locale: $this->language));
	}

	/**
	 * @param string $uuid
	 * @param CategoryStatusRequest $request
	 * @return JsonResponse
	 */
	public function changeStatus(string $uuid, CategoryStatusRequest $request): JsonResponse
	{
		$result = $this->categoryService->changeStatus($uuid, $request->input('status'));

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
        $result = $this->categoryService->delete($request->input('ids', []));

        if (!empty(data_get($result, 'data'))) {
            return $this->onErrorResponse([
                'code'      => ResponseError::ERROR_504,
                'message'   => __('errors.' . ResponseError::ERROR_504)
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
        $this->categoryService->dropAll();

        return $this->successResponse(
            __('errors.' . ResponseError::RECORD_WAS_SUCCESSFULLY_DELETED, locale: $this->language)
        );
    }

    /**
     * @return JsonResponse
     */
    public function truncate(): JsonResponse
    {
        $this->categoryService->truncate();

        return $this->successResponse(
            __('errors.' . ResponseError::RECORD_WAS_SUCCESSFULLY_UPDATED, locale: $this->language)
        );
    }

    /**
     * @return JsonResponse
     */
    public function restoreAll(): JsonResponse
    {
        $this->categoryService->restoreAll();

        return $this->successResponse(
            __('errors.' . ResponseError::RECORD_WAS_SUCCESSFULLY_UPDATED, locale: $this->language)
        );
    }

    /**
     * Remove Model image from storage.
     *
     * @param string $uuid
     * @return JsonResponse
     */
    public function imageDelete(string $uuid): JsonResponse
    {
        $category = Category::firstWhere('uuid', $uuid);

        if (!$category) {
            return $this->onErrorResponse([
                'code'      => ResponseError::ERROR_404,
                'message'   => __('errors.' . ResponseError::ERROR_404, locale: $this->language)
            ]);
        }

        $category->galleries()->where('path', $category->img)->delete();

        $category->update(['img' => null]);

        return $this->successResponse(__('errors.' . ResponseError::RECORD_WAS_SUCCESSFULLY_DELETED), $category);
    }

    /**
     * Search Model by tag name.
     *
     * @param CategoryFilterRequest $request
     * @return AnonymousResourceCollection
     */
    public function categoriesSearch(CategoryFilterRequest $request): AnonymousResourceCollection
    {
        $categories = $this->categoryRepository->categoriesSearch($request->all());

        return CategoryResource::collection($categories);
    }

    /**
     * Change Active Status of Model.
     *
     * @param string $uuid
     * @return JsonResponse
     */
    public function setActive(string $uuid): JsonResponse
    {
        /** @var Category $category */
        $category = $this->categoryRepository->categoryByUuid($uuid);

        if (!$category) {
            return $this->onErrorResponse([
                'code'      => ResponseError::ERROR_404,
                'message'   => __('errors.' . ResponseError::ERROR_404, locale: $this->language)
            ]);
        }

        $category->update(['active' => !$category->active]);

        return $this->successResponse(
            __('errors.' . ResponseError::RECORD_WAS_SUCCESSFULLY_UPDATED, locale: $this->language),
            CategoryResource::make($category)
        );
    }

    public function fileExport(FilterParamsRequest $request): JsonResponse
    {
        $fileName = 'export/categories.xlsx';

        try {
            Excel::store(new CategoryExport($this->language, $request->all()), $fileName, 'public', \Maatwebsite\Excel\Excel::XLSX);
        } catch (Throwable $e) {
            $this->error($e);
            return $this->errorResponse('Error during export');
        }

        return $this->successResponse('Successfully exported', [
            'path' => 'public/export',
            'file_name' => $fileName
        ]);
    }

    public function fileImport(Request $request): JsonResponse
    {
        try {
            Excel::import(new CategoryImport($this->language), $request->file('file'));

            return $this->successResponse('Successfully imported');
        } catch (Throwable $e) {
            $this->error($e);
            return $this->errorResponse(
                ResponseError::ERROR_508,
                __('errors.' . ResponseError::ERROR_508, locale: $this->language) . ' | ' . $e->getMessage()
            );
        }
    }

    public function reportChart(OrderChartRequest $request): JsonResponse
    {
        try {
            $result = $this->categoryRepository->reportChart($request->all());

            return $this->successResponse('', $result);
        } catch (Throwable $exception) {
            return $this->errorResponse(ResponseError::ERROR_400, $exception->getMessage());
        }
    }
}
