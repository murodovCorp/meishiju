<?php

namespace App\Http\Requests\ParcelOrder;

use App\Http\Requests\BaseRequest;
use App\Models\ParcelOrder;
use Illuminate\Validation\Rule;

class StatusUpdateRequest extends BaseRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules(): array
    {
        return [
            'status' => [
                'string',
                'required',
                Rule::in(ParcelOrder::STATUSES)
            ],
        ];
    }
}
