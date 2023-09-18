<?php

namespace App\Http\Requests\Ads;

use App\Http\Requests\BaseRequest;
use App\Models\AdsPackage;
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
            'active'        => 'required|boolean',
            'banner_id'     => ['required', 'int', Rule::exists('banners', 'id')->whereNull('deleted_at')],
            'time_type'     => ['required', 'string', Rule::in(AdsPackage::TIME_TYPES)],
            'time'          => 'required|integer',
            'price'         => 'required|numeric',
            'title'         => 'required|array',
            'title.*'       => 'required|string|max:191',
        ];
    }
}
