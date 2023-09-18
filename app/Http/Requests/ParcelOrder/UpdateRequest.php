<?php

namespace App\Http\Requests\ParcelOrder;

use App\Http\Requests\BaseRequest;

class UpdateRequest extends BaseRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules(): array
    {
        return (new StoreRequest)->rules();
    }

}
