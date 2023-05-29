<?php

namespace App\Http\Requests;

use Illuminate\Validation\Rule;

class ProfileUpdateRequest extends BaseRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules(): array
    {
        $uuid = request()->route('user', auth('sanctum')->user()->uuid);

        return [
            'email'             => [
                'email',
                Rule::unique('users', 'email')->ignore($uuid, 'uuid')
            ],
            'phone'             => [
                'numeric',
                Rule::unique('users', 'phone')->ignore($uuid, 'uuid')
            ],
            'lastname'                          => ['string'],
            'birthday'                          => ['date_format:Y-m-d'],
            'firebase_token'                    => ['string'],
            'firstname'                         => ['required', 'string', 'min:2', 'max:100'],
            'gender'                            => ['string', Rule::in('male','female')],
            'active'                            => ['numeric', Rule::in(1,0)],
            'subscribe'                         => 'boolean',
            'notifications'                     => 'array',
            'notifications.*.notification_id'   => ['required', 'int', Rule::exists('notifications', 'id')],
            'notifications.*.active'            => 'boolean',
            'password'                          => ['min:6', 'confirmed'],
            'images'                            => 'array',
            'images.*'                          => 'string',
        ];
    }
}
