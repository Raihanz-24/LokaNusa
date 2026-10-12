<?php

use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Auth\ConfirmablePasswordController;
use App\Http\Controllers\Auth\EmailVerificationNotificationController;
use App\Http\Controllers\Auth\EmailVerificationPromptController;
use App\Http\Controllers\Auth\NewPasswordController;
use App\Http\Controllers\Auth\PasswordController;
use App\Http\Controllers\Auth\PasswordResetLinkController;
use App\Http\Controllers\Auth\RegisteredUserController;
use App\Http\Controllers\Auth\VerifyEmailController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Rute Autentikasi Publik (mengadopsi standar Laravel Breeze)
|--------------------------------------------------------------------------
*/

Route::middleware('guest')->group(function (): void {
    Route::get('daftar', [RegisteredUserController::class, 'create'])
        ->name('register');
    Route::post('daftar', [RegisteredUserController::class, 'store']);

    Route::get('masuk', [AuthenticatedSessionController::class, 'create'])
        ->name('login');
    Route::post('masuk', [AuthenticatedSessionController::class, 'store']);

    Route::get('lupa-kata-sandi', [PasswordResetLinkController::class, 'create'])
        ->name('password.request');
    Route::post('lupa-kata-sandi', [PasswordResetLinkController::class, 'store'])
        ->name('password.email');

    Route::get('reset-kata-sandi/{token}', [NewPasswordController::class, 'create'])
        ->name('password.reset');
    Route::post('reset-kata-sandi', [NewPasswordController::class, 'store'])
        ->name('password.store');
});

Route::middleware('auth')->group(function (): void {
    Route::get('konfirmasi-kata-sandi', [ConfirmablePasswordController::class, 'show'])
        ->name('password.confirm');
    Route::post('konfirmasi-kata-sandi', [ConfirmablePasswordController::class, 'store']);

    Route::put('kata-sandi', [PasswordController::class, 'update'])
        ->name('password.update');

    Route::get('verifikasi-email', EmailVerificationPromptController::class)
        ->name('verification.notice');

    Route::get('verifikasi-email/{id}/{hash}', VerifyEmailController::class)
        ->middleware(['signed', 'throttle:6,1'])
        ->name('verification.verify');

    Route::post('email/verifikasi-notifikasi', [EmailVerificationNotificationController::class, 'store'])
        ->middleware('throttle:6,1')
        ->name('verification.send');

    Route::post('keluar', [AuthenticatedSessionController::class, 'destroy'])
        ->name('logout');
});
