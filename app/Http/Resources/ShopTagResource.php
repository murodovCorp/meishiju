<?php

namespace App\Http\Resources;

use App\Models\ShopTag;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ShopTagResource extends JsonResource
{
    /**
     * Transform the resource collection into an array.
     *
     * @param  Request  $request
     * @return array
     */
    public function toArray($request): array
    {
        /** @var ShopTag|JsonResource $this */
        $locales = $this->relationLoaded('translations') ?
            $this->translations->pluck('locale')->toArray() : null;

        return [
            'id'            => $this->when($this->id, $this->id),
            'img'           => $this->when($this->img, $this->img),
            'created_at'    => $this->when($this->created_at, $this->created_at?->format('Y-m-d H:i:s') . 'Z'),
            'updated_at'    => $this->when($this->updated_at, $this->updated_at?->format('Y-m-d H:i:s') . 'Z'),
            'deleted_at'    => $this->when($this->deleted_at, $this->deleted_at?->format('Y-m-d H:i:s') . 'Z'),

            //Relations
            'galleries'     => GalleryResource::collection($this->whenLoaded('galleries')),
            'translation'   => TranslationResource::make($this->whenLoaded('translation')),
            'translations'  => TranslationResource::collection($this->whenLoaded('translations')),
            'locales'       => $this->when($locales, $locales),
        ];
    }
}
