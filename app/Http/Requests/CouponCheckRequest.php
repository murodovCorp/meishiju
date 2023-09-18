<?php

namespace App\Http\Requests;

use Illuminate\Validation\Rule;

class CouponCheckRequest extends BaseRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules(): array
    {
        return [
            'coupon'    => ['required', 'string', 'min:2'],
            'shop_id'    => ['required', Rule::exists('shops', 'id')->whereNull('deleted_at')],
            'user_id'   => ['integer', Rule::exists('users', 'id')->whereNull('deleted_at')],
        ];
    }
}
