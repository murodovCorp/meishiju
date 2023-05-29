<?php

namespace App\Http\Requests\Shop;

use App\Http\Requests\BaseRequest;

class CheckWorkingDayRequest extends BaseRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules(): array
    {
        return [
            'date' => 'required|date_format:Y-m-d H:i',
        ];
    }

}
