<?php

namespace App\Http\Requests\Ads;

use App\Http\Requests\BaseRequest;
use App\Models\ShopAdsPackage;
use Illuminate\Validation\Rule;

class ShopAdsUpdateRequest extends BaseRequest
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
            'banner_id' => [
                'required',
                Rule::exists('banners', 'id')->whereNull('deleted_at')
            ],
            'status' => [
                'required',
                Rule::in(ShopAdsPackage::STATUSES)
            ],
            'position_page' => 'required|integer',
            'active'        => 'boolean',
        ];
    }
}
