<?php

namespace App\Http\Requests;

use Illuminate\Validation\Rule;

class RestPushNotifyRequest extends BaseRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules(): array
    {
        return [
            'shop_id'   => [
                'integer',
                'required',
                Rule::exists('shops', 'id')->whereNull('deleted_at')
            ],
            'table_id'  => [
                'integer',
                'required',
                Rule::exists('tables', 'id')->whereNull('deleted_at')
            ],
        ];
    }

}
