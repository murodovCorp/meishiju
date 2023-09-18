<?php

namespace App\Http\Requests;

use Illuminate\Validation\Rule;

class FilterParamsRequest extends BaseRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules(): array
    {
        return [
            'sort'          => 'string|in:asc,desc',
            'column'        => 'regex:/^[a-zA-Z-_]+$/',
            'status'        => 'string',
            'perPage'       => 'integer|min:1|max:100',
            'pPerPage'      => 'integer|min:1|max:100', //in with product paginate perPage
            'page'		    => 'integer',
            'pPage'		    => 'integer', //in with product paginate page
            'shop_id'       => [
                'integer',
                Rule::exists('shops', 'id')->whereNull('deleted_at')
            ],
            'user_id'       => 'exists:users,id',
            'currency_id'   => 'exists:currencies,id',
            'lang'          => 'exists:languages,locale',
            'category_id'   => 'exists:categories,id',
            'brand_id'      => 'exists:brands,id',
            'price'         => 'numeric',
            'note'          => 'string|max:255',
            'date_from'     => 'date_format:Y-m-d',
            'date_to'       => 'date_format:Y-m-d',
            'free_from'     => 'date_format:Y-m-d H:i',
            'free_to'       => 'date_format:Y-m-d H:i',
            'ids'           => 'array',
            'active'        => 'boolean',
        ];
    }

}
