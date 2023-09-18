<?php

namespace App\Http\Requests\ParcelOrder;

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
            'user_id'                   => [
                'required',
                'integer',
                Rule::exists('users', 'id')->whereNull('deleted_at')
            ],
            'currency_id'               => 'required|integer|exists:currencies,id',
            'type_id'                   => ['required', Rule::exists('parcel_order_settings', 'id')],
            'rate'                      => 'numeric',
            'deliveryman_id'            => [
                'integer',
                Rule::exists('users', 'id')->whereNull('deleted_at')
            ],
            'phone_from'                => 'required|string',
            'username_from'             => 'required|string',
            'address_from'              => 'required|array',
            'address_from.longitude'    => 'required|numeric',
            'address_from.latitude'     => 'required|numeric',
            'address_from.address'      => 'string',
            'address_from.house'        => 'string',
            'address_from.stage'        => 'string',
            'address_from.room'         => 'string',

            'phone_to'                  => 'required|string',
            'username_to'               => 'required|string',
            'address_to'                => 'required|array',
            'address_to.latitude'       => 'required|numeric',
            'address_to.longitude'      => 'required|numeric',
            'address_to.address'        => 'string',
            'address_to.house'          => 'string',
            'address_to.stage'          => 'string',
            'address_to.room'           => 'string',

            'delivery_date'             => 'date|date_format:Y-m-d',
            'delivery_time'             => 'required|string',
            'note'                      => 'nullable|string|max:191',
            'images'                    => 'array',
            'images.*'                  => 'string',
            'qr_value'                  => 'string|max:255',
            'instruction'               => 'string|max:255',
            'description'               => 'string',
            'notify'                    => 'in:0,1'
        ];
    }
}
