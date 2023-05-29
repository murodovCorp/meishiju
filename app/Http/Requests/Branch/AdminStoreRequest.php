<?php

namespace App\Http\Requests\Branch;

use App\Http\Requests\BaseRequest;
use App\Models\Category;
use Illuminate\Validation\Rule;

class AdminStoreRequest extends BaseRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules(): array
    {
        return [
            'shop_id' => [
                'required',
                'integer',
                Rule::exists('shops', 'id')->whereNull('deleted_at')
            ]
        ] + (new StoreRequest)->rules();
    }
}
