<?php

namespace App\Http\Requests\Order;

use App\Http\Requests\BaseRequest;

class UpdateTipsRequest extends BaseRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules(): array
    {
        return [
            'tip_type' => 'required|in:fix,percent',
            'tips'     => 'required|numeric|min:0',
        ];
    }

}
