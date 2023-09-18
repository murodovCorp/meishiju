<?php

namespace App\Http\Requests\RequestModel;

use App\Http\Requests\BaseRequest;

class DeliveryManRequest extends BaseRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules(): array
    {
        return [
            'data'	=> 'required|array',
        ];
    }
}
