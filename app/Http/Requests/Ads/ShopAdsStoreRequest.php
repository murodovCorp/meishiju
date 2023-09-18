<?php

namespace App\Http\Requests\Ads;

use App\Http\Requests\BaseRequest;
use Illuminate\Validation\Rule;

class ShopAdsStoreRequest extends BaseRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules(): array
    {
        return [
            'ads_package_id' => [
                'required',
                Rule::exists('ads_packages', 'id')
                    ->where('active', true)
                    ->whereNull('deleted_at')
            ],
        ];
    }
}
