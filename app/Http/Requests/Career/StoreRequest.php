<?php

namespace App\Http\Requests\Career;

use App\Http\Requests\BaseRequest;
use App\Models\Category;
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
            'active'                => 'required|in:0,1',
            'title'                 => 'required|array',
            'title.*'               => 'required|string|min:2|max:191',
            'description'           => 'array',
            'description.*'         => 'string|min:3',
            'address'               => 'required|array',
            'address.*'             => 'string|min:2',
            'location'              => 'array',
            'location.latitude'     => 'numeric',
            'location.longitude'    => 'numeric',
            'category_id'           => [
                'required',
                'integer',
                Rule::exists('categories', 'id')
                    ->where('type', Category::CAREER)
                    ->whereNull('deleted_at')
            ]
        ];
    }
}
