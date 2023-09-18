<?php

namespace App\Http\Requests\DeliveryManSetting;

use App\Http\Requests\BaseRequest;
use App\Models\DeliveryManSetting;
use Illuminate\Validation\Rule;

class AdminRequest extends BaseRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules(): array
    {
        return [
            'user_id' => [
                'required',
                'integer',
                Rule::unique('deliveryman_settings', 'user_id')
                    ->ignore(data_get(DeliveryManSetting::find(request()->route('deliveryman_setting')), 'user_id'), 'user_id'),
                Rule::exists('users', 'id')
                    ->whereNull('deleted_at')
            ],
        ] + (new DeliveryManRequest)->rules();
    }
}
