<?php

namespace App\Http\Requests;

use App\Models\Category;
use Illuminate\Validation\Rule;

class CategoryCreateRequest extends BaseRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules(): array
    {
        return [
            'keywords'              => 'string',
            // Проверяем если в таблице продуктов есть продукт с таким parent_id то запрещаем добавление
            'parent_id'             => [
                'numeric',
                Rule::exists('categories', 'id')
                    ->when(request('type') === 'sub_shop', fn($q) => $q->where('type', Category::SHOP))
                    ->when(request('type') === 'main', fn($q) => $q->where('type', Category::SUB_SHOP))
                    ->when(request('type') === 'sub_main', fn($q) => $q->where('type', Category::MAIN))
                    ->whereNull('deleted_at')
            ] + (in_array(request('type'), ['shop', 'sub_shop']) ? ['required'] : []),
            'type'                  => ['required', Rule::in(array_keys(Category::TYPES))],
            'active'                => ['numeric', Rule::in(1,0)],
            'status'                => ['string', Rule::in(Category::STATUSES)],
            'shop_id'               => ['integer', Rule::exists('shops', 'id')->whereNull('deleted_at')],
            'input'                 => 'integer|max:32767',
            'title'                 => 'required|array',
            'title.*'               => 'required|string|min:2|max:191',
            'images'                => 'array',
            'images.*'              => 'string',
            'description'           => 'array',
            'description.*'         => 'string|min:2',
            'meta'                  => 'array',
            'meta.*'                => 'array',
            'meta.*.path'           => 'string',
            'meta.*.title'          => 'required|string',
            'meta.*.keywords'       => 'string',
            'meta.*.description'    => 'string',
            'meta.*.h1'             => 'string',
            'meta.*.seo_text'       => 'string',
            'meta.*.canonical'      => 'string',
            'meta.*.robots'         => 'string',
            'meta.*.change_freq'    => 'string',
            'meta.*.priority'       => 'string',

        ];
    }

}
