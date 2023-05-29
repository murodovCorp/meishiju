<?php

namespace App\Http\Requests\Order;

use App\Http\Requests\BaseRequest;
use Illuminate\Validation\Rule;

class WaiterUpdateRequest extends BaseRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules(): array
    {
        return [
            'waiter_id' => [
                'int',
                'required',
                Rule::exists('users', 'id')->whereNull('deleted_at')
            ],
        ];
    }
}
