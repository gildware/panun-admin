<?php

use Illuminate\Support\Facades\Route;
use Modules\UserManagement\Http\Controllers\Api\V1\OTPVerificationController;
use Modules\UserManagement\Http\Controllers\Api\V1\PasswordResetController;
use Modules\UserManagement\Http\Controllers\Api\V1\Admin\UserController;

//admin
Route::group(['prefix' => 'admin', 'as' => 'admin.', 'namespace' => 'Api\V1\Admin', 'middleware' => ['auth:api', 'admin.api']], function () {
    Route::group(['prefix' => 'user', 'as' => 'user.',], function () {
        Route::get('list', [UserController::class, 'index']);
    });
});


//User
Route::group(['prefix' => 'user', 'namespace' => 'Api\V1'], function () {
    //verification
    Route::group(['prefix' => 'verification'], function () {
        Route::post('send-otp', [OTPVerificationController::class, 'check'])->middleware('throttle:otp-send');
        Route::post('verify-otp', [OTPVerificationController::class, 'verify'])->middleware('throttle:otp-verify');

        Route::post('firebase-auth-verify', [OTPVerificationController::class, 'firebaseAuthVerify'])->middleware('throttle:otp-verify');
        Route::post('login-otp-verify', [OTPVerificationController::class, 'loginVerifyOTP'])->middleware('throttle:otp-verify');
        Route::post('registration-with-otp', [OTPVerificationController::class, 'registrationWithOTP'])->middleware('throttle:otp-verify');
    });

    //forget password
    Route::group(['prefix' => 'forget-password'], function () {
        Route::post('send-otp', [PasswordResetController::class, 'check'])->middleware('throttle:otp-send');
        Route::post('verify-otp', [PasswordResetController::class, 'verify'])->middleware('throttle:otp-verify');
        Route::put('reset', [PasswordResetController::class, 'resetPassword'])->middleware('throttle:otp-verify');
    });

    Route::post('check-existing-customer', [OTPVerificationController::class, 'checkExistingCustomer']);
});

