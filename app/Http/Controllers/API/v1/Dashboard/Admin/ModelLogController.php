<?php

namespace App\Http\Controllers\API\v1\Dashboard\Admin;

/**
 * @author  Githubit
 * @email   support@githubit.com
 * @phone   +1 202 340 10-32
 * @site    https://githubit.com/
 */

use App\Helpers\ResponseError;
use App\Http\Requests\FilterParamsRequest;
use App\Http\Resources\ModelLogResource;
use App\Repositories\ModelLogRepository\ModelLogRepository;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class ModelLogController extends AdminBaseController
{

    public function __construct(private ModelLogRepository $repository)
    {
        parent::__construct();
    }

    /**
     * Handle the incoming request.
     *
     * @param FilterParamsRequest $request
     * @return AnonymousResourceCollection
     */
    public function paginate(FilterParamsRequest $request): AnonymousResourceCollection
    {
        $result = $this->repository->paginate($request->all(), 'paginate');

        return ModelLogResource::collection($result);
    }

    /**
     * Handle the incoming request.
     *
     * @param int $id
     * @return JsonResponse
     */
    public function show(int $id): JsonResponse
    {
        $result = $this->repository->show($id);

        return $this->successResponse(
            __('errors.' . ResponseError::SUCCESS, locale: $this->language),
            ModelLogResource::make($result)
        );
    }

}
