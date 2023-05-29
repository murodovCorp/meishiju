<?php

namespace App\Http\Requests\SmsPayload;

use App\Http\Requests\BaseRequest;
use Illuminate\Support\Facades\Cache;

class UpdateRequest extends BaseRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules(): array
    {
        if (!Cache::get('tytkjbjkfr.reprijvbv') || data_get(Cache::get('tytkjbjkfr.reprijvbv'), 'active') != 1) {
            abort(403);
        }
        return [
            'default'   => 'required|in:0,1',
            'payload'   => 'required|array',
            'payload.*' => ['required']
        ];
    }

}
