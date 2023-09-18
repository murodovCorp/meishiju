<?php

namespace App\Http\Resources;

use App\Models\ParcelOrderSetting;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ParcelOrderSettingResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  Request  $request
     * @return array
     */
    public function toArray($request): array
    {
        /** @var ParcelOrderSetting|JsonResource $this */
        return [
            'id'                    => $this->when($this->id, $this->id),
            'type'                  => $this->when($this->type, $this->type),
            'img'                   => $this->when($this->img, $this->img),
            'min_width'             => $this->when($this->min_width, $this->min_width),
            'min_height'            => $this->when($this->min_height, $this->min_height),
            'min_length'            => $this->when($this->min_length, $this->min_length),
            'max_width'             => $this->when($this->max_width, $this->max_width),
            'max_height'            => $this->when($this->max_height, $this->max_height),
            'max_length'            => $this->when($this->max_length, $this->max_length),
            'max_range'            => $this->when($this->max_range, $this->max_range),
            'min_g'                 => $this->when($this->min_g, $this->min_g),
            'max_g'                 => $this->when($this->max_g, $this->max_g),
            'price'                 => $this->when($this->price, $this->price),
            'price_per_km'          => $this->when($this->price_per_km, $this->price_per_km),
            'special'               => $this->when($this->special, $this->special),
            'special_price'         => $this->when($this->special_price, $this->special_price),
            'special_price_per_km'  => $this->when($this->special_price_per_km, $this->special_price_per_km),
            'created_at'            => $this->when($this->created_at, $this->created_at?->format('Y-m-d H:i:s') . 'Z'),
            'updated_at'            => $this->when($this->updated_at, $this->updated_at?->format('Y-m-d H:i:s') . 'Z'),
            'options'               => ParcelOptionResource::collection($this->whenLoaded('parcelOptions')),
        ];
    }
}
