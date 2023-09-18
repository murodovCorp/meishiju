<?php

namespace App\Http\Requests\ParcelOrderSetting;

use App\Http\Requests\BaseRequest;
use Illuminate\Validation\Rule;

class StoreRequest extends BaseRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules(): array
    {
        return [
            'type'                  => 'required|string',
            'min_width'             => 'required|numeric|max:32678',
            'min_height'            => 'required|numeric|max:32678',
            'min_length'            => 'required|numeric|max:32678',
            'max_width'             => 'required|numeric|max:32678',
            'max_height'            => 'required|numeric|max:32678',
            'max_length'            => 'required|numeric|max:32678',
            'max_range'             => 'required|numeric|max:2147483647',
            'min_g'                 => 'required|numeric',
            'max_g'                 => 'required|numeric',
            'price'                 => 'required|numeric',
            'price_per_km'          => 'required|numeric',
            'special'               => 'required|boolean',
            'special_price'         => 'required|numeric',
            'special_price_per_km'  => 'required|numeric',
            'images'                => 'array',
            'images.*'              => 'string',
            'options'               => 'array',
            'options.*'             => [
                'integer',
                Rule::exists('parcel_options', 'id')
            ],
        ];
    }
}
