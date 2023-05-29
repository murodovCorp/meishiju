<?php

namespace App\Http\Requests\Booking\Booking;

use App\Http\Requests\BaseRequest;
use App\Models\Booking\UserBooking;
use Illuminate\Validation\Rule;

class StatusUpdateRequest extends BaseRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules(): array
    {
        return [
            'status' => [
                'required',
                Rule::in(UserBooking::STATUSES)
            ],
        ];
    }
}
