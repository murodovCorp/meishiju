<?php

namespace App\Http\Resources;

use App\Models\Branch;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class BranchResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  Request  $request
     * @return array
     */
    public function toArray($request): array
    {
        /** @var Branch|JsonResource $this */

        $locales = $this->relationLoaded('translations') ?
            $this->translations->pluck('locale')->toArray() : null;

        return [
            'id'            => $this->when($this->id,           $this->id),
            'shop_id'       => $this->when($this->shop_id,      $this->shop_id),
            'address'       => $this->when($this->address,      $this->address),
            'location'      => $this->when($this->location,     $this->location),
            'created_at'    => $this->when($this->created_at,   $this->created_at?->format('Y-m-d H:i:s') . 'Z'),
            'updated_at'    => $this->when($this->updated_at,   $this->updated_at?->format('Y-m-d H:i:s') . 'Z'),
            'deleted_at'    => $this->when($this->deleted_at,   $this->deleted_at?->format('Y-m-d H:i:s') . 'Z'),

            'shop'          => ShopResource::make($this->whenLoaded('shop')),
            'translation'   => TranslationResource::make($this->whenLoaded('translation')),
            'translations'  => TranslationResource::collection($this->whenLoaded('translations')),
            'locales'       => $this->when($locales, $locales),
        ];
    }
}
