<?php
namespace App\Http\Controllers\API\v1\Rest;

set_time_limit(1200);

use App\Helpers\ResponseError;
use App\Http\Requests\FilterParamsRequest;
use App\Http\Resources\LandingPageResource;
use App\Repositories\LandingPageRepository\LandingPageRepository;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class LandingPageController extends RestBaseController
{
    public function __construct(private LandingPageRepository $repository)
    {
        parent::__construct();
    }

    /**
     * Display a listing of the resource.
     *
     * @param FilterParamsRequest $request
     * @return AnonymousResourceCollection
     */
    public function paginate(FilterParamsRequest $request): AnonymousResourceCollection
    {
        $filter = $request->all();

        $models = $this->repository->paginate($filter);

        return LandingPageResource::collection($models);
    }

    /**
     * Display the specified resource.
     *
     * @param string $type
     * @return JsonResponse
     */
    public function show(string $type): JsonResponse
    {
        $model = $this->repository->show($type);

        if (empty($model)) {
            return $this->onErrorResponse([
                'code'    => ResponseError::ERROR_404,
                'message' => __('errors.' . ResponseError::ERROR_404, locale: $this->language)
            ]);
        }

        return $this->successResponse(__('errors.' . ResponseError::SUCCESS, locale: $this->language),
            LandingPageResource::make($model)
        );
    }

}
