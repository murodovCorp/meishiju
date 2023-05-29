<?php

namespace App\Http\Requests\Currency;

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
            'title'     => 'required|string',
            'symbol'    => 'required|string',
            'position'  => 'string|in:before,after',
            'rate'      => 'numeric',
            'active'    => 'boolean',
        ];
    }
}

