<?php

namespace App\Http\Requests\LandingPage;

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
        return [
            'data'      => ['required', 'array'],
            'images'    => ['array'],
            'images.*'  => ['string'],
        ];
    }
}
