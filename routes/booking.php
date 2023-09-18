<?php

use App\Http\Controllers\API\v1\Dashboard\{Admin, Seller, User, Waiter};
use App\Http\Controllers\API\v1\Rest\Booking\{BookingController,
    ShopController,
    ShopSectionController,
    TableController};
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/
Route::group(['prefix' => 'v1', 'middleware' => ['block.ip']], function () {

    Route::group(['prefix' => 'rest/booking'], function () {

        /* Shops */
        Route::get('shops/recommended',             [ShopController::class, 'recommended']);
        Route::get('shops/paginate',                [ShopController::class, 'paginate']);
        Route::get('shops/select-paginate',         [ShopController::class, 'selectPaginate']);
        Route::get('shops/search',                  [ShopController::class, 'shopsSearch']);
        Route::get('shops/{uuid}',                  [ShopController::class, 'show']);
        Route::get('shops',                         [ShopController::class, 'shopsByIDs']);
        Route::get('shops-takes',                   [ShopController::class, 'takes']);
        Route::get('products-avg-prices',           [ShopController::class, 'productsAvgPrices']);

        Route::get('shops/{id}/categories',         [ShopController::class, 'categories'])
            ->where('id', '[0-9]+');

        Route::get('shops/{id}/products',           [ShopController::class, 'products'])
            ->where('id', '[0-9]+');

        Route::get('shops/{id}/galleries',          [ShopController::class, 'galleries'])
            ->where('id', '[0-9]+');

        Route::get('shops/{id}/reviews',            [ShopController::class, 'reviews'])
            ->where('id', '[0-9]+');

        Route::get('shops/{id}/reviews-group-rating', [ShopController::class, 'reviewsGroupByRating'])
            ->where('id', '[0-9]+');

        Route::get('shops/{id}/products/paginate',  [ShopController::class, 'productsPaginate'])
            ->where('id', '[0-9]+');

        Route::get('shops/{id}/products/recommended/paginate',  [
            ShopController::class,
            'productsRecommendedPaginate'
        ])->where('id', '[0-9]+');

        Route::get('shop-payments/{id}', [ShopController::class, 'shopPayments']);
        Route::get('shop-working-check/{id}', [ShopController::class, 'shopWorkingCheck']);

        /* Bookings */
        Route::apiResource('bookings', BookingController::class)->only(['index', 'show']);

        /* Shop Section */
        Route::apiResource('shop-sections', ShopSectionController::class)->only(['index', 'show']);

        /* Tables */
        Route::apiResource('tables', TableController::class)->only(['index', 'show']);
        Route::get('disable-dates/table/{id}',  [TableController::class, 'disableDates']);

    });

    Route::group(['prefix' => 'dashboard'], function () {

        // USER BLOCK
        Route::group(['prefix' => 'user', 'middleware' => ['sanctum.check'], 'as' => 'user.'], function () {

            /* User Bookings */
            Route::apiResource('my-bookings',   User\Booking\UserBookingController::class);
            Route::delete('my-bookings/delete', [User\Booking\UserBookingController::class, 'destroy']);

            /* Bookings */
            Route::apiResource('bookings', User\Booking\BookingController::class)->only(['index', 'show']);

            /* Shop Section */
            Route::apiResource('shop-sections', User\Booking\ShopSectionController::class)->only(['index', 'show']);

            /* Tables */
            Route::apiResource('tables', User\Booking\TableController::class)->only(['index', 'show']);
        });

        // SELLER BLOCK
        Route::group(['prefix' => 'seller', 'middleware' => ['sanctum.check', 'role:seller|moderator'], 'as' => 'seller.'], function () {

            /* Bookings */
            Route::apiResource('bookings', Seller\Booking\BookingController::class);
            Route::delete('bookings/delete',        [Seller\Booking\BookingController::class, 'destroy']);

            /* User Bookings */
            Route::apiResource('user-bookings', Seller\Booking\UserBookingController::class);
            Route::post('user-booking/status/{id}', [Seller\Booking\UserBookingController::class, 'statusUpdate']);
            Route::delete('user-bookings/delete',   [Seller\Booking\UserBookingController::class, 'destroy']);

            /* Shop Section */
            Route::apiResource('shop-sections', Seller\Booking\ShopSectionController::class);
            Route::delete('shop-sections/delete',   [Seller\Booking\ShopSectionController::class, 'destroy']);

            /* Tables */
            Route::apiResource('tables',  Seller\Booking\TableController::class);
            Route::get('table/statistic',           [Seller\Booking\TableController::class, 'statistic']);
            Route::get('disable-dates/table/{id}',  [Seller\Booking\TableController::class, 'disableDates']);
            Route::delete('tables/delete',          [Seller\Booking\TableController::class, 'destroy']);

            Route::group(['prefix' => 'booking'], function () {

                /* Shop Working Days */
                Route::apiResource('shop-working-days', Seller\Booking\ShopWorkingDayController::class);
                Route::delete('shop-working-days/delete', [Seller\Booking\ShopWorkingDayController::class, 'destroy']);

                /* Shop Closed Days */
                Route::apiResource('shop-closed-dates', Seller\Booking\ShopClosedDateController::class);
                Route::delete('shop-closed-dates/delete', [Seller\Booking\ShopClosedDateController::class, 'destroy']);

            });

        });

        // WAITER BLOCK
        Route::group(['prefix' => 'waiter', 'middleware' => ['sanctum.check', 'role:waiter'], 'as' => 'waiter.'], function () {

            /* Bookings */
            Route::apiResource('bookings', Waiter\Booking\BookingController::class);
            Route::post('user-booking/status/{id}', [Waiter\Booking\UserBookingController::class, 'statusUpdate']);

            Route::delete('bookings/delete',        [Waiter\Booking\BookingController::class, 'destroy']);

            /* User Bookings */
            Route::apiResource('user-bookings', Waiter\Booking\UserBookingController::class);
            Route::delete('user-bookings/delete',   [Waiter\Booking\UserBookingController::class, 'destroy']);

            /* Shop Section */
            Route::apiResource('shop-sections', Waiter\Booking\ShopSectionController::class);
            Route::delete('shop-sections/delete',   [Waiter\Booking\ShopSectionController::class, 'destroy']);

            /* Tables */
            Route::apiResource('tables',  Waiter\Booking\TableController::class);
            Route::get('table/statistic',           [Waiter\Booking\TableController::class, 'statistic']);
            Route::get('disable-dates/table/{id}',  [Waiter\Booking\TableController::class, 'disableDates']);
            Route::delete('tables/delete',          [Waiter\Booking\TableController::class, 'destroy']);

            Route::group(['prefix' => 'booking'], function () {

                /* Shop Working Days */
                Route::apiResource('shop-working-days', Waiter\Booking\ShopWorkingDayController::class);
                Route::delete('shop-working-days/delete', [Waiter\Booking\ShopWorkingDayController::class, 'destroy']);

                /* Shop Closed Days */
                Route::apiResource('shop-closed-dates', Waiter\Booking\ShopClosedDateController::class);
                Route::delete('shop-closed-dates/delete', [Waiter\Booking\ShopClosedDateController::class, 'destroy']);

            });

        });

        // ADMIN BLOCK
        Route::group(['prefix' => 'admin', 'middleware' => ['sanctum.check', 'role:admin|manager'], 'as' => 'admin.'], function () {

            /* Bookings */
            Route::apiResource('bookings',      Admin\Booking\BookingController::class);
            Route::post('user-booking/status/{id}', [Admin\Booking\UserBookingController::class, 'statusUpdate']);
            Route::delete('bookings/delete',        [Admin\Booking\BookingController::class, 'destroy']);
            Route::get('bookings/drop/all',         [Admin\Booking\BookingController::class, 'dropAll']);
            Route::get('bookings/restore/all',      [Admin\Booking\BookingController::class, 'restoreAll']);
            Route::get('bookings/truncate/db',      [Admin\Booking\BookingController::class, 'truncate']);

            /* User Bookings */
            Route::apiResource('user-bookings', Admin\Booking\UserBookingController::class);
            Route::post('user-booking/status/{id}', [Admin\Booking\UserBookingController::class, 'statusUpdate']);
            Route::delete('user-bookings/delete',   [Admin\Booking\UserBookingController::class, 'destroy']);
            Route::get('user-bookings/drop/all',    [Admin\Booking\UserBookingController::class, 'dropAll']);
            Route::get('user-bookings/restore/all', [Admin\Booking\UserBookingController::class, 'restoreAll']);
            Route::get('user-bookings/truncate/db', [Admin\Booking\UserBookingController::class, 'truncate']);

            /* Shop Section */
            Route::apiResource('shop-sections', Admin\Booking\ShopSectionController::class);
            Route::delete('shop-sections/delete',   [Admin\Booking\ShopSectionController::class, 'destroy']);
            Route::get('shop-sections/drop/all',    [Admin\Booking\ShopSectionController::class, 'dropAll']);
            Route::get('shop-sections/restore/all', [Admin\Booking\ShopSectionController::class, 'restoreAll']);
            Route::get('shop-sections/truncate/db', [Admin\Booking\ShopSectionController::class, 'truncate']);

            /* Tables */
            Route::apiResource('tables',        Admin\Booking\TableController::class);
            Route::get('table/statistic',           [Admin\Booking\TableController::class, 'statistic']);
            Route::get('disable-dates/table/{id}',  [Admin\Booking\TableController::class, 'disableDates']);
            Route::delete('tables/delete',          [Admin\Booking\TableController::class, 'destroy']);
            Route::get('tables/drop/all',           [Admin\Booking\TableController::class, 'dropAll']);
            Route::get('tables/restore/all',        [Admin\Booking\TableController::class, 'restoreAll']);
            Route::get('tables/truncate/db',        [Admin\Booking\TableController::class, 'truncate']);

            Route::group(['prefix' => 'booking'], function () {

                /* Shop Booking Working Days */
                Route::get('shop-working-days/paginate',    [Admin\Booking\ShopBookingWorkingDayController::class, 'paginate']);

                Route::apiResource('shop-working-days', Admin\Booking\ShopBookingWorkingDayController::class)
                    ->except('index', 'store');

                Route::delete('shop-working-days/delete',   [Admin\Booking\ShopBookingWorkingDayController::class, 'destroy']);
                Route::get('shop-working-days/drop/all',    [Admin\Booking\ShopBookingWorkingDayController::class, 'dropAll']);
                Route::get('shop-working-days/restore/all', [Admin\Booking\ShopBookingWorkingDayController::class, 'restoreAll']);
                Route::get('shop-working-days/truncate/db', [Admin\Booking\ShopBookingWorkingDayController::class, 'truncate']);

                /* Shop Booking Closed Days */
                Route::get('shop-closed-dates/paginate',    [Admin\Booking\ShopBookingClosedDateController::class, 'paginate']);

                Route::apiResource('shop-closed-dates', Admin\Booking\ShopBookingClosedDateController::class)
                    ->except('index', 'store');
                Route::delete('shop-closed-dates/delete',   [Admin\Booking\ShopBookingClosedDateController::class, 'destroy']);
                Route::get('shop-closed-dates/drop/all',    [Admin\Booking\ShopBookingClosedDateController::class, 'dropAll']);
                Route::get('shop-closed-dates/restore/all', [Admin\Booking\ShopBookingClosedDateController::class, 'restoreAll']);
                Route::get('shop-closed-dates/truncate/db', [Admin\Booking\ShopBookingClosedDateController::class, 'truncate']);

            });

        });

    });

});
