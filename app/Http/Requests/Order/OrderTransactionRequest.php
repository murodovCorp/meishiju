<?php

namespace App\Http\Requests\Order;

use App\Http\Requests\BaseRequest;
use App\Models\PaymentToPartner;
use Illuminate\Validation\Rule;

class OrderTransactionRequest extends BaseRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules(): array
    {
        return [
            'date_from' => 'required|date_format:Y-m-d',
            'date_to'   => 'required|date_format:Y-m-d',
            'shop_id'       => [
                'integer',
                Rule::exists('shops', 'id')->whereNull('deleted_at')
            ],
            'user_id'       => [
                'integer',
                Rule::exists('users', 'id')->whereNull('deleted_at')
            ],
			'type'		=> ['required', Rule::in(PaymentToPartner::TYPES)],
            'column'    => 'regex:/^[a-zA-Z-_]+$/',
            'sort'      => 'string|in:asc,desc',
            'perPage'   => 'int|max:100',
        ];
    }
}
