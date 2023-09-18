<?php

namespace App\Services\ParcelOrderService;

use App\Helpers\ResponseError;
use App\Models\ParcelOrder;
use App\Services\CoreService;
use App\Traits\Notification;

class ParcelOrderReviewService extends CoreService
{
    use Notification;

    protected function getModelClass(): string
    {
        return ParcelOrder::class;
    }

    public function addDeliverymanReview($id, $collection): array
    {
        /** @var ParcelOrder $model */
        $model = $this->model()
            ->with([
                'deliveryman',
                'review',
                'reviews',
            ])->find($id);

        if (!$model || !$model->deliveryman?->id) {
            return [
                'status'    => false,
                'code'      => ResponseError::ERROR_400,
                'message'   => __('errors.' . ResponseError::ORDER_OR_DELIVERYMAN_IS_EMPTY, locale: $this->language)
            ];
        }

        $model->addOrderReview($collection, $model->deliveryman);

        return [
            'status'    => true,
            'code'      => ResponseError::NO_ERROR,
            'data'      => ParcelOrder::with(['reviews.assignable'])->find($model->id)
        ];
    }
    public function addReviewByDeliveryman($id, $collection): array
    {
        /** @var ParcelOrder $model */
        $model = $this->model()
            ->with([
                'review',
                'reviews',
            ])->find($id);

        if (!$model || $model->deliveryman?->id !== auth('sanctum')->id() || !$model->user) {
            return [
                'status'    => false,
                'code'      => ResponseError::ERROR_404,
                'message'   =>  __('errors.' . ResponseError::ORDER_NOT_FOUND, locale: $this->language)
            ];
        }

        $model->addOrderReview($collection, $model->user);

        return [
            'status'    => true,
            'code'      => ResponseError::NO_ERROR,
            'data'      => ParcelOrder::with(['reviews.assignable'])->find($model->id)
        ];
    }

}
