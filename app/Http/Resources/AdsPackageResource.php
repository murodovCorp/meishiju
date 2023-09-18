<?php

namespace App\Http\Resources;

use App\Models\AdsPackage;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AdsPackageResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  Request  $request
     * @return array
     */
    public function toArray($request): array
    {
        /** @var AdsPackage|JsonResource $this */
        $locales = $this->relationLoaded('translations') ?
            $this->translations->pluck('locale')->toArray() : null;

        return [
            'id'            => $this->when($this->id, $this->id),
            'active'        => (bool)$this->active,
            'banner_id'     => $this->when($this->banner_id, $this->banner_id),
            'time_type'     => $this->when($this->time_type, $this->time_type),
            'time'          => $this->when($this->time, $this->time),
            'price'         => $this->when($this->price, $this->price),
            'created_at'    => $this->when($this->created_at, $this->created_at?->format('Y-m-d H:i:s') . 'Z'),
            'updated_at'    => $this->when($this->updated_at, $this->updated_at?->format('Y-m-d H:i:s') . 'Z'),
            'deleted_at'    => $this->when($this->deleted_at, $this->deleted_at?->format('Y-m-d H:i:s') . 'Z'),

            // Relations
            'banner'            => BannerResource::make($this->whenLoaded('banner')),
            'translation'       => TranslationResource::make($this->whenLoaded('translation')),
            'translations'      => TranslationResource::collection($this->whenLoaded('translations')),
            'locales'           => $this->when($locales, $locales),
            'shop_ads_package'  => ShopAdsPackageResource::make($this->whenLoaded('shopAdsPackage')),
            'shop_ads_packages' => ShopAdsPackageResource::collection($this->whenLoaded('shopAdsPackages')),
        ];
    }
}
