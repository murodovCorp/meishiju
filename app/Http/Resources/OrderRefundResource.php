<?php

namespace App\Http\Resources;

use App\Models\OrderRefund;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class OrderRefundResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  Request  $request
     * @return array
     */
    public function toArray($request): array
    {
        /** @var OrderRefund|JsonResource $this */
        return [
            'id'                    => $this->id,
            'status'                => $this->status,
            'cause'                 => $this->cause,
            'answer'                => $this->answer,
            'created_at'            => $this->when($this->created_at, $this->created_at?->format('Y-m-d H:i:s') . 'Z'),
            'updated_at'            => $this->when($this->updated_at, $this->updated_at?->format('Y-m-d H:i:s') . 'Z'),

            'order'                 => OrderResource::make($this->whenLoaded('order')),
            'galleries'             => GalleryResource::collection($this->whenLoaded('galleries')),
        ];
    }
}
