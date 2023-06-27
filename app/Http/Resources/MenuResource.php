<?php

namespace App\Http\Resources;

use App\Models\Menu;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class MenuResource extends JsonResource
{
    /**
     * Transform the resource collection into an array.
     *
     * @param  Request  $request
     * @return array
     */
    public function toArray($request): array
    {
        /** @var Menu|JsonResource $this */
        $locales = $this->relationLoaded('translations') ?
            $this->translations->pluck('locale')->toArray() : null;

        return [
            'id'            => $this->when($this->id,         $this->id),
            'category_id'   => $this->when($this->category_id,$this->category_id),
            'shop_id'       => $this->when($this->shop_id,    $this->shop_id),
            'created_at'    => $this->when($this->created_at, $this->created_at?->format('Y-m-d H:i:s') . 'Z'),
            'updated_at'    => $this->when($this->updated_at, $this->updated_at?->format('Y-m-d H:i:s') . 'Z'),
            'deleted_at'    => $this->when($this->deleted_at, $this->deleted_at?->format('Y-m-d H:i:s') . 'Z'),

            //Relations
            'shop'          => ShopResource::make($this->whenLoaded('shop')),
            'category'      => CategoryResource::make($this->whenLoaded('category')),
            'products'      => ProductResource::collection($this->whenLoaded('products')),
            'translation'   => TranslationResource::make($this->whenLoaded('translation')),
            'translations'  => TranslationResource::collection($this->whenLoaded('translations')),
            'locales'       => $this->when($locales, $locales),
        ];
    }
}
