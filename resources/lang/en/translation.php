<?php

use App\Enums\BookingStatusType;
use App\Enums\ResidenceStatusType;

return [

    /*
    |--------------------------------------------------------------------------
    | Pagination Language Lines
    |--------------------------------------------------------------------------
    |
    | The following language lines are used by the paginator library to build
    | the simple pagination links. You are free to change them to anything
    | you want to customize your views to better match your application.
    |
    */


    ResidenceStatusType::Created->name => 'Created',
    ResidenceStatusType::Edited->name => 'Edited',
    ResidenceStatusType::Approved->name => 'Approved',
    ResidenceStatusType::Rejected->name => 'Rejected',
    BookingStatusType::Canceled->name => 'Canceled',
    BookingStatusType::Paid->name => 'Paid',

    'booking_successfully_created' => 'Successfully booked',
    'booking_successfully_updated' => 'Booking successfully updated',
    'booking_found' => 'Booking found',

    'residence_successfully_created' => 'Residence successfully created',
    'residence_successfully_updated' => 'Residence successfully updated',
    'residence_found' => 'Residence found',

    'user_details_successfully_updated' => 'User details successfully updated',

    'guide_successfully_created' => 'Guide successfully created',
    'guide_successfully_updated' => 'Guide successfully updated',
    'guide_found' => 'Guide found',

    'transport_successfully_created' => 'Transport successfully created',
    'transport_successfully_updated' => 'Transport successfully updated',
    'transport_found' => 'Transport found',
];
