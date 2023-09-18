<?php

namespace App\Http\Requests\RequestModel;

use App\Http\Requests\BaseRequest;
use App\Models\RequestModel;
use Illuminate\Validation\Rule;

class ChangeStatusRequest extends BaseRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules(): array
    {
        return [
            'status'		=> ['required', 'string', Rule::in(RequestModel::STATUSES)],
            'status_note' 	=> 'string|max:255|required_if:status,' . RequestModel::STATUS_CANCELED,
        ];
    }
}
