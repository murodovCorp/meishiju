<?php

namespace App\Services\Booking\BookingService;

use App\Helpers\ResponseError;
use App\Models\Booking\UserBooking;
use App\Models\Language;
use App\Models\PushNotification;
use App\Models\Translation;
use App\Services\CoreService;
use App\Traits\Notification;
use DB;
use Exception;
use Throwable;

class UserBookingService extends CoreService
{
    use Notification;

    protected function getModelClass(): string
    {
        return UserBooking::class;
    }

    public function create(array $data): array
    {
        try {

            $model = DB::transaction(function () use ($data) {

                $where = [
                    ['table_id', data_get($data, 'table_id')],
                    ['start_date', '>=', data_get($data, 'start_date')],
                    ['status', UserBooking::NEW],
                ];

                if (data_get($data, 'end_date')) {
                    $where[] = ['end_date', '<=', data_get($data, 'end_date')];
                }

                $userBooking = UserBooking::where($where)->first();

                if ($userBooking) {
                    throw new Exception(__('errors.' . ResponseError::TABLE_BOOKING_EXISTS, [
                        'start_date' => data_get($data, 'start_date'),
                        'end_date'   => data_get($data, 'end_date'),
                    ], $this->language));
                }

                /** @var UserBooking $model */

                $model  = $this->model()->create($data)->load([
                    'booking:id,shop_id',
                    'booking.shop:id,user_id',
                    'booking.shop.seller:id,firebase_token',

                    'table:shop_section_id,id',
                    'table.shopSection:id,shop_id',
                    'table.shopSection.shop:id,user_id',
                    'table.shopSection.shop.seller:id,firebase_token',
                ]);

                $seller = $model->booking?->shop?->seller;

                if (empty($seller)) {
                    $seller = $model->table->shopSection->shop->seller;
                }

                $this->sendNotification(
                    is_array($seller?->firebase_token) ? $seller->firebase_token : [],
                    __('errors.' . ResponseError::NEW_BOOKING, locale: $this->language),
                    $model->id,
                    [
                        'id'     => $model->id,
                        'status' => UserBooking::NEW,
                        'type'   => PushNotification::BOOKING_STATUS
                    ],
                    [$seller->id]
                );

                return $model;
            });

            return [
                'status'    => true,
                'code'      => ResponseError::NO_ERROR,
                'data'      => $model,
            ];
        } catch (Throwable $e) {
            $this->error($e);

            return [
                'status'    => false,
                'code'      => ResponseError::ERROR_501,
                'message'   => $e->getMessage()
            ];
        }
    }

    public function update(UserBooking $model, array $data): array
    {
        try {
            $where = [
                ['id', '!=', $model->id],
                ['table_id', data_get($data, 'table_id')],
                ['start_date', '>=', data_get($data, 'start_date')],
                ['status', UserBooking::NEW],
            ];

            if (data_get($data, 'end_date')) {
                $where[] = ['end_date', '<=', data_get($data, 'end_date')];
            }

            $userBooking = UserBooking::where($where)->exists();

            if ($userBooking) {
                throw new Exception(__('errors.' . ResponseError::TABLE_BOOKING_EXISTS, [
                    'start_date' => data_get($data, 'start_date'),
                    'end_date'   => data_get($data, 'end_date'),
                ], $this->language));
            }

            $model->update($data);

            return [
                'status' => true,
                'code'   => ResponseError::NO_ERROR,
                'data'   => $model,
            ];
        } catch (Throwable $e) {
            $this->error($e);
            return [
                'status'    => false,
                'code'      => ResponseError::ERROR_502,
                'message'   => $e->getMessage()
            ];
        }
    }

    public function statusUpdate(int $id, array $data): array
    {
        try {
            $model = UserBooking::find($id);

            if (!$model) {
                return [
                    'status' => false,
                    'code'   => ResponseError::ERROR_404,
                    'data'   => $model,
                ];
            }

            $where = [
                ['id', '!=', $model->id],
                ['table_id', $model->table_id],
                ['start_date', '>=', $model->start_date],
                ['status', UserBooking::ACCEPTED],
            ];

            if (data_get($data, 'end_date')) {
                $where[] = ['end_date', '<=', $model->end_date];
            }

            $userBooking = UserBooking::where($where)->exists();

            if ($userBooking) {
                throw new Exception(__('errors.' . ResponseError::TABLE_BOOKING_EXISTS, [
                    'start_date' => $model->start_date,
                    'end_date'   => $model->end_date,
                ], $this->language));
            }

            $model->update($data);


            $tokens = $model->user?->firebase_token;

            $default = data_get(Language::languagesList()->where('default', 1)->first(), 'locale');

            $tStatus = Translation::where(function ($q) use ($default) {
                $q->where('locale', $this->language)->orWhere('locale', $default);
            })
                ->where('key', $model->status)
                ->first()?->value;

            $this->sendNotification(
                is_array($tokens) ? $tokens : [],
                __('errors.' . ResponseError::BOOKING_STATUS_CHANGED, ['status' => !empty($tStatus) ? $tStatus : $model->status], $this->language),
                $model->id,
                [
                    'id'     => $model->id,
                    'status' => $model->status,
                    'type'   => PushNotification::BOOKING_STATUS
                ],
                [$model->user_id]
            );

            return [
                'status' => true,
                'code'   => ResponseError::NO_ERROR,
                'data'   => $model,
            ];
        } catch (Throwable $e) {
            $this->error($e);
            return [
                'status'    => false,
                'code'      => ResponseError::ERROR_502,
                'message'   => $e->getMessage()
            ];
        }
    }

    public function delete(?array $ids = [], ?int $userId = null): array
    {
        $models = UserBooking::whereIn('id', is_array($ids) ? $ids : [])
            ->when($userId, fn($q) => $q->where('user_id', $userId))
            ->get();

        $errorIds = [];

        foreach ($models as $model) {
            /** @var UserBooking $model */
            try {
                $model->delete();
            } catch (Throwable $e) {
                $this->error($e);
                $errorIds[] = $model->id;
            }
        }

        if (count($errorIds) === 0) {
            return ['status' => true, 'code' => ResponseError::NO_ERROR];
        }

        return [
            'status'  => false,
            'code'    => ResponseError::ERROR_503,
            'message' => __('errors.' . ResponseError::CANT_DELETE_IDS, ['ids' => implode(', ', $errorIds)], $this->language)
        ];
    }

}
