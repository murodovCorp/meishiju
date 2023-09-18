<?php

namespace App\Http\Requests\RequestModel;

use App\Http\Requests\BaseRequest;
use App\Models\RequestModel;
use Illuminate\Validation\Rule;

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
            'id' 	=> 'int',
            'type'	=> ['string', Rule::in(RequestModel::BY_TYPES)],
            'data'	=> 'array',
        ];
    }
}
