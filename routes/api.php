<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\BankController;
use App\Http\Controllers\Api\PaymentMethodController;
use App\Http\Controllers\Api\Admin\PosterController;
use App\Http\Controllers\Api\Admin\SocialMediaController;
use App\Http\Controllers\Api\Admin\WalletLimitController;
use App\Http\Controllers\Api\Admin\CheaterUserController;
use App\Http\Controllers\Api\User\GetIdController;
use App\Http\Controllers\Api\User\BankAccountController;
use App\Http\Controllers\Api\Agent\BankController as AgentBankController;
use App\Http\Controllers\Api\Agent\NotificationController;
use App\Http\Controllers\Api\Agent\MoniteringReportController;
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
    Route::get('/banks', [BankController::class, 'banks']);
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

    Route::prefix('cheater-user')->group(function () {
        Route::get('/list', [CheaterUserController::class, 'list']);    
        Route::post('/create', [CheaterUserController::class, 'create']);        
        Route::delete('/delete/{id}', [CheaterUserController::class, 'delete']);
    });   
});

Route::prefix('user')->group(function () {
    Route::get('/bank-list', [BankAccountController::class, 'bankList']);
    
    Route::prefix('wallet')->group(function () {
        Route::get('/history', [GetIdController::class, 'walletHistory']);
        Route::post('/withdraw-request', [GetIdController::class, 'withdrawRequest']);
        Route::post('/deposit-request', [GetIdController::class, 'depositRequest']);
    });

    Route::prefix('accounts')->group(function () {
        Route::post('/create', [BankAccountController::class, 'create']);
        Route::post('/create-upi', [BankAccountController::class, 'createUpiAccount']);
        Route::get('/list/{id}', [BankAccountController::class, 'list']);
        Route::post('/delete', [BankAccountController::class, 'delete']);
    });
});

Route::prefix('agent')->group(function () {
    Route::prefix('accounts')->group(function () {
        Route::get('/list', [AgentBankController::class, 'list']);
        Route::post('/create', [AgentBankController::class, 'create']);
        Route::get('/delete/{id}/{user_id}', [AgentBankController::class, 'delete']);
        Route::post('/status', [AgentBankController::class, 'updateStatus']);
    });
    
    Route::post('/notifi-to-me', [NotificationController::class, 'create']);
    Route::get('/notification-send', [NotificationController::class, 'sendPushNotification']);

    Route::prefix('monitering-report')->group(function () {
        Route::get('/user-create-count', [MoniteringReportController::class, 'userCreateCountList']);
        Route::get('/first-deposit-count', [MoniteringReportController::class, 'firstDepositCountList']);
    });
});


