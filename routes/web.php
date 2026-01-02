<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\PaymentTestRazorPayController;


Route::get('/', function () {
    return view('welcome');
});

Route::get('hello', function () {
    return view('hello');
});



Route::get('/buy-plan', [PaymentTestRazorPayController::class, 'createOrder']);
Route::post('/payment-success', [PaymentTestRazorPayController::class, 'paymentSuccess']);
