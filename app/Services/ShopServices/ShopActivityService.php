<?php

namespace App\Services\ShopServices;

use App\Helpers\ResponseError;
use App\Models\PushNotification;
use App\Models\Shop;
use App\Services\CoreService;
use App\Traits\Notification;

class ShopActivityService extends CoreService
{
	use Notification;

    /**
     * @return string
     */
    protected function getModelClass(): string
    {
        return Shop::class;
    }

    public function changeStatus(string $uuid,  $status): array
    {
        /** @var Shop $shop */
        $shop = $this->model()
			->with([
				'seller.roles'
			])
			->whereHas('seller')
			->firstWhere('uuid', $uuid);

        if (!$shop) {
            return [
                'status'  => false,
                'message' => __('errors.' . ResponseError::ERROR_404, locale: $this->language)
            ];
        }

        if ($shop->seller->hasRole('admin')) {
            return [
                'status'  => false,
                'message' => __('errors.' . ResponseError::ERROR_207, locale: $this->language)
            ];
        }

        $shop->update(['status' => $status]);

        if ($status == 'approved') {

            $shop->seller->syncRoles('seller');

			if ($shop->seller->firebase_token) {

				$this->sendNotification(
					$shop->seller->firebase_token,
					__('errors.' . ResponseError::STATUS_CHANGED, locale: $this->language),
					$shop->user_id,
					[
						'id'     => $shop->user_id,
						'status' => 'approved',
						'type'   => PushNotification::SHOP_APPROVED
					],
					[$shop->user_id],
					__('errors.' . ResponseError::SHOP_APPROVED, locale: $this->language),
				);

			}

        }

        return ['status' => true, 'message' => ResponseError::NO_ERROR, 'data' => $shop];
    }

    public function changeOpenStatus(string $uuid)
    {
        $shop = $this->model()->firstWhere('uuid', $uuid);

        $shop->update(['open' => !$shop->open]);
    }

}
