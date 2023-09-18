<?php

namespace App\Http\Requests\Product;

use App\Http\Requests\BaseRequest;
use Illuminate\Validation\Rule;

class addInStockRequest extends BaseRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules(): array
    {
        return [
            'extras'            => 'required|array',
            'extras.*.ids'      => 'nullable|array',
            'extras.*.ids.*'    => 'integer|exists:extra_values,id',
            'extras.*.stock_id' => [
                'integer',
                Rule::exists('stocks', 'id')->whereNull('deleted_at')
            ],
            'extras.*.price'    => 'required|numeric|min:0',
            'extras.*.quantity' => 'required|integer|min:0',
            'extras.*.sku'      => 'nullable|string|max:255',
            'extras.*.addons'   => 'array',
            'extras.*.addons.*' => Rule::exists('products', 'id')->where('addon', 1),

            'delete_ids'    => 'array',
            'delete_ids.*'  => [
                'integer',
                Rule::exists('stocks', 'id')->whereNull('deleted_at')
            ],
        ];
    }
}
