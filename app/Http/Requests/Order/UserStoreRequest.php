<?php

namespace App\Http\Requests\Order;

use App\Http\Requests\BaseRequest;

class UserStoreRequest extends BaseRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules(): array
    {
        $rules = (new StoreRequest)->rules();
        unset($rules['user_id']);

        return $rules;
    }
}
