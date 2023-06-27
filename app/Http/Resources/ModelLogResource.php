<?php

namespace App\Http\Resources;

use App\Models\ModelLog;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ModelLogResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  Request  $request
     * @return array
     */
    public function toArray($request): array
    {
        /** @var ModelLog|JsonResource $this */
        return [
            'id'            => $this->when($this->id, $this->id),
            'model_type'    => $this->when($this->model_type, $this->model_type),
            'model_id'      => $this->when($this->model_id, $this->model_id),
            'data'          => $this->when($this->data, ModelLogDataResource::toArray($this->data)),
            'type'          => $this->when($this->type, $this->type),
            'created_at'    => $this->when($this->created_at, $this->created_at?->format('Y-m-d H:i:s') . 'Z'),
            'created_by'    => $this->when($this->created_by, $this->created_by),
            'created_user'  => UserResource::make($this->whenLoaded('createdBy')),
        ];
    }
}
