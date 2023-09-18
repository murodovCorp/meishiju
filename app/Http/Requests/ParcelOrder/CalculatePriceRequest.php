<?php

namespace App\Http\Requests\ParcelOrder;

use App\Http\Requests\BaseRequest;
use Illuminate\Validation\Rule;

class CalculatePriceRequest extends BaseRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules(): array
    {
        return [
            'type_id'                   => ['required', Rule::exists('parcel_order_settings', 'id')],
            'address_from'              => 'required|array',
            'address_from.longitude'    => 'required|numeric',
            'address_from.latitude'     => 'required|numeric',
            'address_to'                => 'required|array',
            'address_to.latitude'       => 'required|numeric',
            'address_to.longitude'      => 'required|numeric',
        ];
    }
}
