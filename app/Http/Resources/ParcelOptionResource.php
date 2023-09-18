<?php

namespace App\Http\Resources;

use App\Models\ParcelOption;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ParcelOptionResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  Request  $request
     * @return array
     */
    public function toArray($request): array
    {
        /** @var ParcelOption|JsonResource $this */
        return [
            'id'                => $this->when($this->id, $this->id),
            'created_at'        => $this->when($this->created_at, $this->created_at?->format('Y-m-d H:i:s') . 'Z'),
            'updated_at'        => $this->when($this->updated_at, $this->updated_at?->format('Y-m-d H:i:s') . 'Z'),

            'translation'       => ParcelOptionTranslationResource::make($this->whenLoaded('translation')),
            'translations'      => ParcelOptionTranslationResource::collection($this->whenLoaded('translations')),
        ];
    }
}
