<?php

namespace App\Http\Requests\Report\Sales;

use App\Http\Requests\BaseRequest;

class HistoryRequest extends BaseRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules(): array
    {
        return [
            'type'      => 'required|in:deliveryman,today,history',
            'column'    => 'string|in:id,total_price,created_at,note,delivery_fee,commission_fee,user_id',
            'sort'      => 'string|in:asc,desc',
        ];
    }
}
