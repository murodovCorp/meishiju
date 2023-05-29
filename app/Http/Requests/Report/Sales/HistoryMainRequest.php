<?php

namespace App\Http\Requests\Report\Sales;

use App\Http\Requests\BaseRequest;

class HistoryMainRequest extends BaseRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules(): array
    {
        return [
            'type'      => 'required|in:day,week,month',
            'date_from' => 'required|date_format:Y-m-d',
            'date_to'   => 'required|date_format:Y-m-d',
        ];
    }
}
