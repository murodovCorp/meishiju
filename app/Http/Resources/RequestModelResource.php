<?php

namespace App\Http\Resources;

use App\Models\Category;
use App\Models\Language;
use App\Models\RequestModel;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class RequestModelResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param Request $request
     * @return array
     */
    public function toArray($request): array
    {
        /** @var RequestModel|JsonResource $this */
		$locale = data_get(Language::languagesList()->where('default', 1)->first(), 'locale');
		$lang = request('lang', $locale);

		$parent = data_get($this, 'data.parent_id');

		/** @var ProductResource|CategoryResource $model */
		$model = 'App\Http\Resources\\' . str_replace('App\Models\\', '', $this->model_type) . 'Resource';

		return [
            'id' 			=> $this->when($this->id, $this->id),
            'model_id' 		=> $this->when($this->model_id, $this->model_id),
            'model_type'	=> $this->when($this->model_type, data_get(RequestModel::BY_TYPES, $this->model_type)),
            'created_by'	=> $this->when($this->created_by, $this->created_by),
            'data' 			=> $this->when($this->data, $this->data),
            'status' 		=> $this->when($this->status, $this->status),
            'status_note'	=> $this->when($this->status_note, $this->status_note),
            'model' 	 	=> $this->when($this->relationLoaded('model'), $model::make($this->model)),
			'createdBy'  	=> UserResource::make($this->whenLoaded('createdBy')),
			'parent'	 	=> $this->when(
				$parent,
				Category::with([
					'translation' => fn($q) => $q->where('locale', $lang)->orWhere('locale', $locale)
				])->find($parent)
			),
            'created_at' => $this->when($this->created_at, $this->created_at->format('Y-m-d H:i:s') . 'Z'),
            'updated_at' => $this->when($this->updated_at, $this->updated_at->format('Y-m-d H:i:s') . 'Z'),
        ];
    }

}
