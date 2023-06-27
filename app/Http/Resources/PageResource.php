<?php

namespace App\Http\Resources;

use App\Models\Page;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PageResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  Request  $request
     * @return array
     */
    public function toArray($request): array
    {
        /** @var Page|JsonResource $this */

        $locales = $this->relationLoaded('translations') ?
            $this->translations->pluck('locale')->toArray() : null;

        return [
            'id'            => $this->when($this->id,         $this->id),
            'type'          => $this->when($this->type,       $this->type),
            'img'           => $this->when($this->img,        $this->img),
            'bg_img'        => $this->when($this->bg_img,     $this->bg_img),
            'active'        => $this->when($this->active,     $this->active),
            'buttons'       => $this->when($this->buttons,    $this->buttons),
            'created_at'    => $this->when($this->created_at, $this->created_at?->format('Y-m-d H:i:s') . 'Z'),
            'updated_at'    => $this->when($this->updated_at, $this->updated_at?->format('Y-m-d H:i:s') . 'Z'),
            'deleted_at'    => $this->when($this->deleted_at, $this->deleted_at?->format('Y-m-d H:i:s') . 'Z'),
            'category'      => CategoryResource::make($this->whenLoaded('category')),
            'translation'   => TranslationResource::make($this->whenLoaded('translation')),
            'translations'  => TranslationResource::collection($this->whenLoaded('translations')),
            'locales'       => $this->when($locales, $locales),
            'galleries'     => GalleryResource::collection($this->whenLoaded('galleries')),

        ];
    }
}
