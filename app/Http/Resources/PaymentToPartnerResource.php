<?php

namespace App\Http\Resources;

use App\Models\PaymentToPartner;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PaymentToPartnerResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  Request  $request
     * @return array
     */
    public function toArray($request): array
    {
        /** @var PaymentToPartner|JsonResource $this */
        return [
            'id'        	=> $this->when($this->id, $this->id),
            'user_id'   	=> $this->when($this->user_id, $this->user_id),
            'order_id'  	=> $this->when($this->order_id, $this->order_id),
            'created_at'	=> $this->when($this->created_at, $this->created_at?->format('Y-m-d H:i:s') . 'Z'),
            'updated_at'	=> $this->when($this->updated_at, $this->updated_at?->format('Y-m-d H:i:s') . 'Z'),
            'deleted_at'	=> $this->when($this->deleted_at, $this->deleted_at?->format('Y-m-d H:i:s') . 'Z'),

            // Relations
            'user' 			=> UserResource::make($this->whenLoaded('user')),
            'order'			=> OrderResource::make($this->whenLoaded('order')),
			'transaction' 	=> TransactionResource::make($this->whenLoaded('transaction')),
			'transactions'	=> TransactionResource::collection($this->whenLoaded('transactions')),
        ];
    }
}
