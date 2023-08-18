<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\BankController;
use App\Http\Controllers\Api\PaymentMethodController;
use App\Http\Controllers\Api\Admin\PosterController;
use App\Http\Controllers\Api\Admin\SocialMediaController;
use App\Http\Controllers\Api\Admin\WalletLimitController;
use App\Http\Controllers\Api\Admin\UserNumberController;
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
Route::post('social-media', [AuthController::class, 'socialMedia']);

Route::middleware('auth:api')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/profile', [AuthController::class, 'profile']);
});

Route::prefix('bank')->group(function () {
    Route::post('/create', [BankController::class, 'create']);
    Route::post('/update', [BankController::class, 'update']);
    Route::delete('/delete/{id}', [BankController::class, 'delete']);
    Route::get('/get-bank-list', [BankController::class, 'banks']);
    Route::post('/update-bank-status', [BankController::class, 'updateBankStatus']);
    Route::post('/search', [BankController::class, 'search']);
    Route::post('/delete-all', [BankController::class, 'deleteAll']);
});

Route::prefix('payment-method')->group(function () {
    Route::post('/create', [PaymentMethodController::class, 'create']);
    Route::post('/update', [PaymentMethodController::class, 'update']);
    Route::delete('/delete/{id}', [PaymentMethodController::class, 'delete']);
    Route::get('/list', [PaymentMethodController::class, 'paymentMethods']);        
});

Route::prefix('admin')->group(function () {
    Route::prefix('poster')->group(function () {
        Route::post('/create', [PosterController::class, 'create']);
        Route::post('/update', [PosterController::class, 'update']);
        Route::delete('/delete/{id}', [PosterController::class, 'delete']);
        Route::get('/list', [PosterController::class, 'list']);        
    }); 
    Route::prefix('social-media')->group(function () {
        Route::post('/create', [SocialMediaController::class, 'socialMedia']);        
        Route::get('/list', [SocialMediaController::class, 'socialMediaList']);    
    }); 
    Route::prefix('wallet-limit')->group(function () {
        Route::post('/create', [WalletLimitController::class, 'create']);        
        Route::get('/list', [WalletLimitController::class, 'getWalletLimitData']);    
    });
    
    Route::prefix('user-number')->group(function () {
        Route::post('/create', [UserNumberController::class, 'create']);        
        Route::get('/list', [UserNumberController::class, 'userNumberList']);
        Route::post('/is-saved', [UserNumberController::class, 'isSaved']);    
        Route::post('/is-called', [UserNumberController::class, 'isCalled']);    
        Route::get('/filter-by-date', [UserNumberController::class, 'dateFilter']);    
    });
});