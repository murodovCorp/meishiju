<?php

namespace App\Http\Requests\Menu;

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
        $shopId = request('shop_id');

        if (!auth('sanctum')?->user()?->hasRole('admin')) {
            $shopId = auth('sanctum')->user()?->shop?->id ?? auth('sanctum')->user()?->moderatorShop;
        }

        return [
            'category_id' => [
                'required',
                Rule::exists('categories', 'id')->whereNull('deleted_at')
                    ->where('type', Category::MENU)
                    ->where('active', true),
            ],
            'title'                 => ['required', 'array'],
            'title.*'               => ['required', 'string', 'min:1', 'max:191'],
            'products'              => ['required', 'array'],
            'products.*'            => [
                'required',
                'integer',
                Rule::exists('products', 'id')
                    ->whereNull('deleted_at')
                    ->when(!empty($shopId), fn($q) => $q->where('shop_id', $shopId))
                    ->where('addon', false),
            ],
            'description'           => ['array'],
            'description.*'         => ['string', 'min:1'],
        ];
    }
}
