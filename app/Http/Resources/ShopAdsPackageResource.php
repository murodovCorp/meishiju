<?php

namespace App\Http\Resources;

use App\Models\ShopAdsPackage;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ShopAdsPackageResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  Request $request
     * @return array
     */
    public function toArray($request): array
    {
        /** @var ShopAdsPackage|JsonResource $this */
        return [
            'id'                => $this->when($this->id, $this->id),
            'active'            => (bool)$this->active,
            'ads_package_id'    => $this->when($this->ads_package_id, $this->ads_package_id),
            'position_page'     => $this->when($this->position_page, $this->position_page),
            'shop_id'           => $this->when($this->shop_id, $this->shop_id),
            'banner_id'         => $this->when($this->banner_id, $this->banner_id),
            'status'            => $this->when($this->status, $this->status),
            'expired_at'        => $this->when($this->expired_at, $this->expired_at?->format('Y-m-d H:i:s') . 'Z'),
            'deleted_at'        => $this->when($this->deleted_at, $this->deleted_at?->format('Y-m-d H:i:s') . 'Z'),

            'shop'              => ShopResource::make($this->whenLoaded('shop')),
            'ads_package'       => AdsPackageResource::make($this->whenLoaded('adsPackage')),
            'banner'            => BannerResource::make($this->whenLoaded('banner')),
            'transaction'       => TransactionResource::make($this->whenLoaded('transaction')),
            'transactions'      => TransactionResource::collection($this->whenLoaded('transactions')),
        ];
    }
}
