<?php

namespace App\Http\Controllers\API\v1\Rest;

use App\Helpers\ResponseError;
use App\Http\Requests\FilterParamsRequest;
use App\Http\Resources\PageResource;
use App\Repositories\PageRepository\PageRepository;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class PageController extends RestBaseController
{
    public function __construct(private PageRepository $repository)
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
        $filter = $request->all();

        $model = $this->repository->paginate($filter);

        return PageResource::collection($model);
    }

    /**
     * @param string $type
     * @return JsonResponse
     */
    public function show(string $type): JsonResponse
    {
        $result = $this->repository->showByType($type);

        if (empty($result)) {
            return $this->onErrorResponse([
                'code'      => ResponseError::ERROR_404,
                'message'   => __('errors.' . ResponseError::ERROR_404, locale: $this->language)
            ]);
        }

        return $this->successResponse(
            __('errors.' . ResponseError::SUCCESS, locale: $this->language),
            PageResource::make($result)
        );
    }
}
