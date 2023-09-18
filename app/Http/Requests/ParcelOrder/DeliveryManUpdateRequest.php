<?php

namespace App\Http\Requests\ParcelOrder;

use App\Http\Requests\BaseRequest;
use Illuminate\Validation\Rule;

class DeliveryManUpdateRequest extends BaseRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules(): array
    {
        return [
            'deliveryman_id' => [
                'int',
                'required',
                Rule::exists('users', 'id')->whereNull('deleted_at')
            ],
        ];
    }
}
