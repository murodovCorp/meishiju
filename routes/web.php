<?php

use App\Http\Controllers\Web\ConvertController;
use App\Http\Controllers\API\v1\Dashboard\Payment\{MercadoPagoController,
    PayStackController,
    RazorPayController,
    StripeController,
    WalletPaymentController};
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Dashboard Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Route::get('wallet-success', [WalletPaymentController::class, 'success']);

Route::get('order-stripe-success', [StripeController::class, 'orderResultTransaction']);
Route::get('parcel-order-stripe-success', [StripeController::class, 'orderResultTransaction']);
Route::get('subscription-stripe-success', [StripeController::class, 'subscriptionResultTransaction']);

//Route::get('order-paypal-success', [PayPalController::class, 'orderResultTransaction']);
//Route::get('subscription-paypal-success', [PayPalController::class, 'subscriptionResultTransaction']);

Route::get('order-razorpay-success', [RazorPayController::class, 'orderResultTransaction']);
Route::get('subscription-razorpay-success', [RazorPayController::class, 'subscriptionResultTransaction']);

Route::get('order-paystack-success', [PayStackController::class, 'orderResultTransaction']);
Route::get('subscription-paystack-success', [PayStackController::class, 'subscriptionResultTransaction']);

Route::get('order-mercado-pago-success', [MercadoPagoController::class, 'orderResultTransaction']);
Route::get('subscription-mercado-pago-success', [MercadoPagoController::class, 'subscriptionResultTransaction']);

Route::get('/', function () {
    return view('welcome');
});

Route::get('convert', [ConvertController::class, 'index'])->name('convert');
Route::post('convert-post', [ConvertController::class, 'getFile'])->name('convertPost');
