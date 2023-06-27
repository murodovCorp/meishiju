<?php

namespace App\Http\Resources;

use App\Models\UserAddress;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserAddressResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  Request  $request
     * @return array
     */
    public function toArray($request): array
    {
        /** @var UserAddress|JsonResource $this */

        return [
            'id'            => $this->when($this->id,           $this->id),
            'title'         => $this->when($this->title,        $this->title),
            'user_id'       => $this->when($this->user_id,      $this->user_id),
            'active'        => $this->active,
            'address'       => $this->when($this->address,      $this->address),
            'location'      => $this->when($this->location,     $this->location),
            'created_at'    => $this->when($this->created_at,   $this->created_at?->format('Y-m-d H:i:s') . 'Z'),
            'updated_at'    => $this->when($this->updated_at,   $this->updated_at?->format('Y-m-d H:i:s') . 'Z'),
            'deleted_at'    => $this->when($this->deleted_at,   $this->deleted_at?->format('Y-m-d H:i:s') . 'Z'),

            'user'          => UserResource::make($this->whenLoaded('user')),
            'orders'        => OrderResource::collection($this->whenLoaded('orders')),

        ];
    }
}
