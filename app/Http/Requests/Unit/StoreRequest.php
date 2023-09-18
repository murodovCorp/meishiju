<?php

namespace App\Http\Requests\Unit;

use App\Http\Requests\BaseRequest;

class StoreRequest extends BaseRequest
{
    /**
     * @return string[]
     */
    public function rules(): array
    {
        return [
            'active'    => 'required|boolean',
            'position'  => 'required|string|in:before,after',
        ];
    }
}
