<?php

namespace App\Http\Resources\Booking;

use App\Models\Booking\ShopBookingWorkingDay;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ShopBookingWorkingDayResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  Request  $request
     * @return array
     */
    public function toArray($request): array
    {
        /** @var ShopBookingWorkingDay|JsonResource $this */
        return [
            'id'            => $this->when($this->id, $this->id),
            'day'           => $this->when($this->day, $this->day),
            'from'          => $this->when($this->from, $this->from),
            'to'            => $this->when($this->to, $this->to),
            'disabled'      => (boolean)$this->disabled,
            'created_at'    => $this->when($this->created_at, $this->created_at?->format('Y-m-d H:i:s') . 'Z'),
            'updated_at'    => $this->when($this->updated_at, $this->updated_at?->format('Y-m-d H:i:s') . 'Z'),
            'deleted_at'    => $this->when($this->deleted_at, $this->deleted_at?->format('Y-m-d H:i:s') . 'Z'),

            'shop'          => BookingShopResource::make($this->whenLoaded('shop')),
        ];
    }
}
