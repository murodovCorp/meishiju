<?php

namespace App\Http\Resources;

use App\Models\ParcelOrder;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ParcelOrderResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  Request  $request
     * @return array
     */
    public function toArray($request): array
    {
        /** @var ParcelOrder|JsonResource $this */
        return [
            'id'            => $this->when($this->id,                $this->id),
            'user_id'       => $this->when($this->user_id,           $this->user_id),
            'total_price'   => $this->when($this->rate_total_price,  $this->rate_total_price),
            'rate'          => $this->when($this->rate,              $this->rate),
            'note'          => $this->when($this->note,              $this->note),
            'tax'           => $this->when($this->rate_tax,          $this->rate_tax),
            'status'        => $this->when($this->status,            $this->status),
            'phone_to'      => $this->when($this->phone_to,          $this->phone_to),
            'username_to'   => $this->when($this->username_to,       $this->username_to),
            'address_from'  => $this->when($this->address_from,      $this->address_from),
            'address_to'    => $this->when($this->address_to,        $this->address_to),
            'type_id'       => $this->when($this->type_id,           $this->type_id),
            'delivery_fee'  => $this->when($this->rate_delivery_fee, $this->rate_delivery_fee),
            'delivery_date' => $this->when($this->delivery_date,     $this->delivery_date),
            'delivery_time' => $this->when($this->delivery_time,     $this->delivery_time),
            'phone_from'    => $this->when($this->phone_from,        $this->phone_from),
            'username_from' => $this->when($this->username_from,     $this->username_from),
            'current'       => (bool)$this->current,
            'img'           => $this->when($this->img,               $this->img),
            'qr_value'      => $this->when($this->qr_value,          $this->qr_value),
            'instruction'   => $this->when($this->instruction,       $this->instruction),
            'description'   => $this->when($this->description,       $this->description),
            'notify'        => $this->notify,
            'option'        => $this->when($this->option,            $this->option),
            'created_at'    => $this->when($this->created_at, $this->created_at?->format('Y-m-d H:i:s') . 'Z'),
            'updated_at'    => $this->when($this->updated_at, $this->updated_at?->format('Y-m-d H:i:s') . 'Z'),
            'km'            => $this->when($this->km, $this->km),

            'deliveryman'   => UserResource::make($this->whenLoaded('deliveryman')),
            'currency'      => CurrencyResource::make($this->whenLoaded('currency')),
            'user'          => UserResource::make($this->whenLoaded('user')),
            'transaction'   => TransactionResource::make($this->whenLoaded('transaction')),
            'transactions'  => TransactionResource::collection($this->whenLoaded('transactions')),
            'galleries'     => GalleryResource::collection($this->whenLoaded('galleries')),
            'logs'          => ModelLogResource::collection($this->whenLoaded('logs')),
            'type'          => ParcelOrderSettingResource::make($this->whenLoaded('type')),
            'review'        => ReviewResource::make($this->whenLoaded('review')),
            'reviews'       => ReviewResource::collection($this->whenLoaded('reviews')),
        ];
    }
}
