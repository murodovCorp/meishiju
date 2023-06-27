<?php

namespace App\Http\Resources;

use App\Models\LandingPage;
use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use JsonSerializable;

class LandingPageResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  Request  $request
     * @return array|Arrayable|JsonSerializable
     */
    public function toArray($request): array|JsonSerializable|Arrayable
    {
        /** @var LandingPage|JsonResource $this */

        return [
            'id'            => $this->id,
            'data'          => $this->data,
            'type'          => $this->type,
            'created_at'    => $this->when($this->created_at, $this->created_at?->format('Y-m-d H:i:s') . 'Z'),
            'updated_at'    => $this->when($this->updated_at, $this->updated_at?->format('Y-m-d H:i:s') . 'Z'),
            'deleted_at'    => $this->when($this->deleted_at, $this->deleted_at?->format('Y-m-d H:i:s') . 'Z'),

            // Relations
            'galleries'     => GalleryResource::collection($this->whenLoaded('galleries')),
        ];
    }
}
