<?php

namespace App\Http\Requests\UserAddress;

use App\Http\Requests\BaseRequest;

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
            'title'                 => 'string|max:255',
            'address'               => 'array',
            'location'              => 'array',
            'active'                => 'boolean',
            'location.latitude'     => 'numeric',
            'location.longitude'    => 'numeric',
        ];
    }
}
