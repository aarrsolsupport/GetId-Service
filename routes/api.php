<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\BankController;
use App\Http\Controllers\Api\PaymentMethodController;
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

Route::post('register', [AuthController::class, 'register']);
Route::post('login', [AuthController::class, 'login']);
Route::get('countries', [AuthController::class, 'countryList']);

Route::middleware('auth:api')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/profile', [AuthController::class, 'profile']);
});


Route::prefix('bank')->group(function () {
    Route::post('/create', [BankController::class, 'create']);
    Route::post('/update', [BankController::class, 'update']);
    Route::delete('/delete/{id}', [BankController::class, 'delete']);
    Route::get('/get-bank-list', [BankController::class, 'banks']);
});

Route::prefix('payment-method')->group(function () {
    Route::post('/create', [PaymentMethodController::class, 'create']);
    Route::post('/update', [PaymentMethodController::class, 'update']);
    Route::delete('/delete/{id}', [PaymentMethodController::class, 'delete']);
    Route::get('/list', [PaymentMethodController::class, 'paymentMethods']);        
});
