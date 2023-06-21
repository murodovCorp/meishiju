<?php

namespace App\Http\Requests\Yandex;

use App\Http\Requests\BaseRequest;

class DeliveryMethodRequest extends BaseRequest
{
    /**
     * Get the validation rules that apply to the request.
     * @return array
     */
    public function rules(): array
	{
		return [
            'address'            => 'string',
            'location'           => 'required|array',
            'location.latitude'  => 'required|string',
            'location.longitude' => 'required|string',
		];
	}
}
