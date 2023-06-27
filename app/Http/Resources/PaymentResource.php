<?php

namespace App\Http\Resources;

use App\Models\Payment;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PaymentResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  Request  $request
     * @return array
     */
    public function toArray($request): array
    {
        /** @var Payment|JsonResource $this */
        return [
            'id'            => (int) $this->id,
            'tag'           => (string) $this->tag,
            'input'         => $this->when($this->input, (int) $this->input),
            'sandbox'       => $this->when($this->sandbox, (boolean) $this->sandbox),
            'active'        => (bool)$this->active,
            'created_at'    => $this->when($this->created_at, $this->created_at?->format('Y-m-d H:i:s') . 'Z'),
            'updated_at'    => $this->when($this->updated_at, $this->updated_at?->format('Y-m-d H:i:s') . 'Z'),
            'deleted_at'    => $this->when($this->deleted_at, $this->deleted_at?->format('Y-m-d H:i:s') . 'Z'),

//            // Relations
//            'translation'   => TranslationResource::make($this->whenLoaded('translation')),
//            'translations'  => TranslationResource::collection($this->whenLoaded('translations')),
//            'locales'       => $this->relationLoaded('translations') ? $this->translations->pluck('locale')->toArray() : [],
        ];
    }
}
