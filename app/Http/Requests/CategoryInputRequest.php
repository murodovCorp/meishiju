<?php

namespace App\Http\Requests;

class CategoryInputRequest extends BaseRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules(): array
    {
        return [
            'input' => 'required|integer|max:32767',
        ];
    }

}
