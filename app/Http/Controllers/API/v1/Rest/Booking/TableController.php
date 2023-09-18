<?php

namespace App\Http\Controllers\API\v1\Rest\Booking;

use App\Helpers\ResponseError;
use App\Http\Controllers\API\v1\Rest\RestBaseController;
use App\Http\Requests\FilterParamsRequest;
use App\Http\Resources\Booking\TableResource;
use App\Models\Booking\Table;
use App\Repositories\Booking\TableRepository\TableRepository;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class TableController extends RestBaseController
{
    public function __construct(private TableRepository $repository)
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
        $model = $this->repository->paginate($request->all());

        return TableResource::collection($model);
    }

    /**
     * @param Table $table
     * @return JsonResponse
     */
    public function show(Table $table): JsonResponse
    {
        $result = $this->repository->show($table);

        return $this->successResponse(
            __('errors.' . ResponseError::SUCCESS, locale: $this->language),
            TableResource::make($result)
        );
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param int $id
     * @param FilterParamsRequest $request
     * @return array
     */
    public function disableDates(int $id, FilterParamsRequest $request): array
    {
        $filter = $request->merge(['id' => $id])->all();

        return $this->repository->disableDates($filter);
    }
}
