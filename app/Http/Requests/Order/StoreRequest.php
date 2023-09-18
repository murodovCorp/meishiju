<?php

namespace App\Http\Requests\Order;

use App\Http\Requests\BaseRequest;
use App\Models\Order;
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
            'user_id'               => [
                'integer',
                Rule::exists('users', 'id')->whereNull('deleted_at')
            ],
            'waiter_id'             => [
                'integer',
                Rule::exists('users', 'id')->whereNull('deleted_at')
            ],
            'cook_id'               => [
                'integer',
                Rule::exists('users', 'id')->whereNull('deleted_at')
            ],
            'table_id'              => 'integer',
            'booking_id'            => 'integer',
            'user_booking_id'       => 'integer',
            'currency_id'           => 'required|integer|exists:currencies,id',
            'rate'                  => 'numeric',
            'shop_id'               => [
                'required',
                'integer',
                Rule::exists('shops', 'id')->whereNull('deleted_at')
            ],
            'delivery_fee'          => 'nullable|numeric',
            'waiter_fee'            => 'nullable|numeric',
            'delivery_type'         => ['required', Rule::in(Order::DELIVERY_TYPES)],
            'coupon'                => 'nullable|string',
            'location'              => 'array',
            'location.latitude'     => 'numeric',
            'location.longitude'    => 'numeric',
            'address'               => 'array',
            'address_id'            => ['integer', Rule::exists('user_addresses', 'id')],
            'phone'                 => 'string',
            'username'              => 'string',
            'delivery_date'         => 'date|date_format:Y-m-d',
            'delivery_time'         => 'string',
            'note'                  => 'nullable|string|max:191',
            'cart_id'               => 'integer|exists:carts,id',
            'notes'                 => 'array',
            'notes.*'               => 'string|max:255',
            'images'                => 'array',
            'images.*'              => 'string',
			'bonus'                 => 'boolean',
			'tip_type'              => 'in:fix,percent',
			'tips'                  => 'numeric|min:0',

			'products'              => 'nullable|array',
            'products.*.stock_id'   =>  [
                'integer',
                Rule::exists('stocks', 'id')
					->whereNull('deleted_at')
            ],
            'products.*.quantity'   => 'numeric',
            'products.*.note'       => 'nullable|string|max:255',

			'products.*.addons'     => 'array',
			'products.*.addons.*.stock_id'  => [
				'integer',
				Rule::exists('stocks', 'id')
					->where('addon', 1)
					->whereNull('deleted_at')
			],
			'products.*.addons.*.quantity'  => ['integer'],
        ];
    }
}
