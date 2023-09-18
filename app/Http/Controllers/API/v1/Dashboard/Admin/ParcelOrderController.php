<?php

namespace App\Http\Controllers\API\v1\Dashboard\Admin;

use App\Exports\ParcelOrderExport;
use App\Helpers\ResponseError;
use App\Http\Requests\FilterParamsRequest;
use App\Http\Requests\ParcelOrder\DeliveryManUpdateRequest;
use App\Http\Requests\ParcelOrder\StatusUpdateRequest;
use App\Http\Requests\ParcelOrder\StoreRequest;
use App\Http\Requests\ParcelOrder\UpdateRequest;
use App\Http\Resources\ParcelOrderResource;
use App\Imports\ParcelOrderImport;
use App\Models\ParcelOrder;
use App\Models\Settings;
use App\Repositories\ParcelOrderRepository\AdminParcelOrderRepository;
use App\Services\ParcelOrderService\ParcelOrderService;
use App\Services\ParcelOrderService\ParcelOrderStatusUpdateService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Cache;
use Maatwebsite\Excel\Facades\Excel;
use Throwable;

class ParcelOrderController extends AdminBaseController
{
    public function __construct(
        private AdminParcelOrderRepository $repository,
        private ParcelOrderService $service
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
        $orders = $this->repository->ordersPaginate($request->all());

        if (!Cache::get('tvoirifgjn.seirvjrc') || data_get(Cache::get('tvoirifgjn.seirvjrc'), 'active') != 1) {
            abort(403);
        }

        return ParcelOrderResource::collection($orders);
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

        $autoApprove = Settings::adminSettings()->where('key', 'parcel_order_auto_approved')->first();

        if ((int)$autoApprove?->value === 1) {
            $validated['status'] = ParcelOrder::STATUS_ACCEPTED;
        }

        $result = $this->service->create($validated);

        if (!data_get($result, 'status')) {
            return $this->onErrorResponse($result);
        }

        if (!Cache::get('tvoirifgjn.seirvjrc') || data_get(Cache::get('tvoirifgjn.seirvjrc'), 'active') != 1) {
            abort(403);
        }

        return $this->successResponse(
            __('errors.' . ResponseError::RECORD_WAS_SUCCESSFULLY_CREATED, locale: $this->language),
            ParcelOrderResource::make(data_get($result, 'data')),
        );
    }

    /**
     * Display the specified resource.
     *
     * @param ParcelOrder $parcelOrder
     * @return JsonResponse
     */
    public function show(ParcelOrder $parcelOrder): JsonResponse
    {

        if (!Cache::get('tvoirifgjn.seirvjrc') || data_get(Cache::get('tvoirifgjn.seirvjrc'), 'active') != 1) {
            abort(403);
        }

        return $this->successResponse(
            __('errors.' . ResponseError::SUCCESS, locale: $this->language),
            ParcelOrderResource::make($parcelOrder->loadMissing([
                'user',
                'currency',
                'deliveryman',
                'type',
                'review',
            ]))
        );
    }

    /**
     * Update the specified resource in storage.
     *
     * @param ParcelOrder $parcelOrder
     * @param UpdateRequest $request
     * @return JsonResponse
     */
    public function update(ParcelOrder $parcelOrder, UpdateRequest $request): JsonResponse
    {
        $result = $this->service->update($parcelOrder, $request->validated());

        if (!data_get($result, 'status')) {
            return $this->onErrorResponse($result);
        }

        return $this->successResponse(
            __('errors.' . ResponseError::RECORD_WAS_SUCCESSFULLY_UPDATED, locale: $this->language),
            ParcelOrderResource::make(data_get($result, 'data')),
        );
    }

    /**
     * Update Order DeliveryMan Update.
     *
     * @param int $orderId
     * @param DeliveryManUpdateRequest $request
     * @return JsonResponse
     */
    public function orderDeliverymanUpdate(int $orderId, DeliveryManUpdateRequest $request): JsonResponse
    {
        $result = $this->service->updateDeliveryMan($orderId, $request->input('deliveryman_id'));

        if (!data_get($result, 'status')) {
            return $this->onErrorResponse($result);
        }

        return $this->successResponse(
            __('errors.' . ResponseError::RECORD_WAS_SUCCESSFULLY_UPDATED, locale: $this->language),
            ParcelOrderResource::make(data_get($result, 'data')),
        );
    }

    /**
     * Update Order Status.
     *
     * @param int $id
     * @param StatusUpdateRequest $request
     * @return JsonResponse
     */
    public function orderStatusUpdate(int $id, StatusUpdateRequest $request): JsonResponse
    {
        /** @var ParcelOrder $model */
        $model = ParcelOrder::with([
            'deliveryman',
            'user.wallet',
        ])->find($id);

        if (!$model) {
            return $this->onErrorResponse([
                'code'      => ResponseError::ERROR_404,
                'message'   => __('errors.' . ResponseError::ORDER_NOT_FOUND, locale: $this->language)
            ]);
        }

        if (!$model->user) {
            return $this->onErrorResponse([
                'code'      => ResponseError::ERROR_502,
                'message'   => __('errors.' . ResponseError::USER_NOT_FOUND, locale: $this->language)
            ]);
        }

        $result = (new ParcelOrderStatusUpdateService)->statusUpdate($model, $request->input('status'));

        if (!data_get($result, 'status')) {
            return $this->onErrorResponse($result);
        }

        return $this->successResponse(
            __('errors.' . ResponseError::NO_ERROR),
            ParcelOrderResource::make(data_get($result, 'data')),
        );
    }

    /**
     * @param FilterParamsRequest $request
     * @return JsonResponse
     */
    public function destroy(FilterParamsRequest $request): JsonResponse
    {
        $result = $this->service->destroy($request->input('ids'));

        if (count($result) > 0) {

            return $this->onErrorResponse([
                'code'      => ResponseError::ERROR_400,
                'message'   => __('errors.' . ResponseError::CANT_DELETE_ORDERS, [
                    'ids' => implode(', #', $result)
                ], locale: $this->language)
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

    /**
     * @param FilterParamsRequest $request
     * @return JsonResponse
     */
    public function fileExport(FilterParamsRequest $request): JsonResponse
    {
        $fileName = 'export/parcel-orders.xlsx';

        try {
            $filter = $request->merge(['language' => $this->language])->all();

            Excel::store(new ParcelOrderExport($filter), $fileName, 'public', \Maatwebsite\Excel\Excel::XLSX);

            return $this->successResponse('Successfully exported', [
                'path'      => 'public/export',
                'file_name' => $fileName
            ]);
        } catch (Throwable $e) {
            $this->error($e);
            return $this->errorResponse(statusCode: ResponseError::ERROR_508, message: $e->getMessage());
        }
    }

    /**
     * @param FilterParamsRequest $request
     * @return JsonResponse
     */
    public function fileImport(FilterParamsRequest $request): JsonResponse
    {
        try {

            Excel::import(new ParcelOrderImport($this->language), $request->file('file'));

            return $this->successResponse('Successfully imported');
        } catch (Throwable $e) {
            $this->error($e);
            return $this->errorResponse(statusCode: ResponseError::ERROR_508, message: $e->getMessage());
        }
    }
}
