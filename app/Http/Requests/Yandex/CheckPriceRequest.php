<?php

namespace App\Http\Requests\Yandex;

use App\Http\Requests\BaseRequest;

class CheckPriceRequest extends BaseRequest
{
    /**
     * Get the validation rules that apply to the request.
     * @return array
     */
    public function rules(): array
	{
		return [
            'address_from'              => 'string',

            'location_from'             => 'required|array',
            'location_from.latitude'    => 'required|string',
            'location_from.longitude'   => 'required|string',

            'address_to'                => 'string',

            'location_to'               => 'required|array',
            'location_to.latitude'      => 'required|string',
            'location_to.longitude'     => 'required|string',
		];
	}
}
