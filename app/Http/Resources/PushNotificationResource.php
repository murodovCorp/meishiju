<?php

namespace App\Http\Resources;

use App\Http\Resources\Booking\TableResource;
use App\Http\Resources\Booking\UserBookingResource;
use App\Models\Blog;
use App\Models\Booking\Table;
use App\Models\Booking\UserBooking;
use App\Models\Order;
use App\Models\OrderRefund;
use App\Models\PushNotification;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PushNotificationResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  Request  $request
     * @return array
     */
    public function toArray($request): array
    {
        /** @var PushNotification|JsonResource $this */

        $order = null;

        if (!empty($this->title) && in_array($this->type, [PushNotification::NEW_ORDER, PushNotification::STATUS_CHANGED])) {
            /** @var Order $order */
            $order = Order::with('user:id,firstname,lastname,active,password,img')
                ->select(['id', 'user_id'])
                ->where('id', $this->title)
                ->first();
        }

        $blog = null;

        if ($this->type === PushNotification::NEWS_PUBLISH) {
            $blog = Blog::find($this->title);
        }

        $userBooking = null;

        if (in_array($this->type, [PushNotification::NEW_BOOKING, PushNotification::BOOKING_STATUS])) {
            $userBooking = UserBooking::find($this->title);
        }

        $table = null;

        if ($this->type === PushNotification::NEW_IN_TABLE) {
            $table = Table::find($this->title);
        }

        $referral = null;

        if ($this->type === PushNotification::NEW_USER_BY_REFERRAL) {
            $referral = User::find($this->title);
        }

        $seller = null;

        if ($this->type === PushNotification::SHOP_APPROVED) {
			$seller = User::find($this->title);
        }

        $orderRefund = null;

        if ($this->type === PushNotification::ORDER_REFUNDED) {
			$orderRefund = OrderRefund::find($this->title);
        }

        return [
            'id'            => $this->when($this->id,          $this->id),
            'type'          => $this->when($this->type,        $this->type),
            'title'         => $this->when($this->title,       $this->title),
            'body'          => $this->when($this->body,        $this->body),
            'data'          => $this->when($this->data,        $this->data),
            'user_id'       => $this->when($this->user_id,     $this->user_id),
            'created_at'    => $this->when($this->created_at,  $this->created_at?->format('Y-m-d H:i:s') . 'Z'),
            'updated_at'    => $this->when($this->updated_at,  $this->updated_at?->format('Y-m-d H:i:s') . 'Z'),
            'read_at'       => $this->when($this->read_at,     $this->read_at . 'Z'),

            'user'          => UserResource::make($this->whenLoaded('user')),
            'seller'        => UserResource::make($seller),
            'client'        => $this->when(!empty($order), UserResource::make($order?->user)),
            'order'         => $this->when(!empty($order), OrderResource::make($order)),
            'order_refund'	=> $this->when(!empty($orderRefund), OrderRefundResource::make($orderRefund)),
            'blog'          => $this->when(!empty($blog), BlogResource::make($blog)),
            'user_booking'  => $this->when(!empty($userBooking), UserBookingResource::make($userBooking)),
            'referral'      => $this->when(!empty($referral), UserResource::make($referral)),
            'table'         => $this->when(!empty($table), TableResource::make($table)),
        ];
    }
}
