<?php

namespace App\Http\Requests\LandingPage;

use App\Http\Requests\BaseRequest;
use App\Models\LandingPage;
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
            'type'      => [
                'required',
                Rule::in(LandingPage::TYPES), Rule::unique('landing_pages', 'type')->whereNull('deleted_at')
            ],
            'data'      => ['required', 'array'],
            'images'    => ['array'],
            'images.*'  => ['string'],
        ];
    }
}
