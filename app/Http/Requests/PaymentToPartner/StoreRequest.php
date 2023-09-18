<?php

namespace App\Http\Requests\PaymentToPartner;

use App\Http\Requests\BaseRequest;
use App\Models\Order;
use App\Models\PaymentToPartner;
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
            'data'   => 'array|required',
            'data.*' => [
				'required',
				'integer',
				Rule::exists('orders', 'id')
					->where('status', Order::STATUS_DELIVERED)
			],
			'payment_id' => [
				'required',
				'integer',
				Rule::exists('payments', 'id')
					->where('active', true)
					->whereNull('deleted_at')
			],
			'type' => [
				'required',
				'string',
				Rule::in(PaymentToPartner::TYPES)
			]
        ];
    }
}
