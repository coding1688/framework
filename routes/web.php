<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\DocumentController;
use App\Http\Controllers\Api\EmailVerificationController;
use App\Http\Controllers\Api\MemberWalletController;
use App\Http\Controllers\Api\NotificationController;
use App\Http\Controllers\Api\SocialAuthController;
use App\Http\Controllers\Frontend\PageController;
use Illuminate\Support\Facades\Route;

Route::domain(config('platform.backend_domain'))->middleware(['backend.locale', 'backend.log'])->group(__DIR__.'/backend.php');

Route::domain(config('platform.frontend_domain'))->prefix('api/v1/auth')->group(function (): void {
    Route::post('register', [AuthController::class, 'register'])->middleware('throttle:10,1')->name('register');
    Route::post('login', [AuthController::class, 'login'])->middleware('throttle:10,1')->name('login');
    Route::post('password/forgot', [AuthController::class, 'forgotPassword'])->middleware('throttle:6,1')->name('password.forgot');
    Route::post('password/reset', [AuthController::class, 'resetPassword'])->middleware('throttle:6,1')->name('password.reset');
    Route::get('oauth/{provider}/redirect', [SocialAuthController::class, 'redirect'])->middleware('throttle:30,1')->name('oauth.redirect');
    Route::get('oauth/{provider}/callback', [SocialAuthController::class, 'callback'])->middleware('throttle:30,1')->name('oauth.callback');

    Route::middleware('frontend.auth')->group(function (): void {
        Route::post('logout', [AuthController::class, 'logout'])->name('logout');
        Route::get('user', [AuthController::class, 'user'])->name('user');
        Route::get('restrictions/{type}', [AuthController::class, 'restriction'])->name('restriction.check');
        Route::post('profile', [AuthController::class, 'profile'])->name('profile.update');
        Route::post('password', [AuthController::class, 'password'])->middleware('throttle:10,1')->name('password.update');
        Route::post('email', [AuthController::class, 'email'])->middleware('throttle:6,1')->name('email.update');
        Route::post('avatar', [AuthController::class, 'avatar'])->middleware('throttle:10,1')->name('avatar.update');
        Route::post('oauth/{provider}/unlink', [SocialAuthController::class, 'unlink'])->name('oauth.unlink');
        Route::get('oauth/{provider}/bind', [SocialAuthController::class, 'bindRedirect'])->middleware('throttle:30,1')->name('oauth.bind');
        Route::get('oauth/{provider}/bind/callback', [SocialAuthController::class, 'bindCallback'])->middleware('throttle:30,1')->name('oauth.bind.callback');
        Route::post('email/verification-notification', [EmailVerificationController::class, 'send'])->middleware('throttle:6,1')->name('verification.send');
        Route::get('email/verify/{id}/{hash}', [EmailVerificationController::class, 'verify'])->middleware(['signed', 'throttle:6,1'])->name('verification.verify');
    });
});

Route::domain(config('platform.frontend_domain'))->prefix('api/v1/notifications')->name('notifications.')->middleware('frontend.auth')->controller(NotificationController::class)->group(function (): void {
    Route::get('list', 'list')->name('list');
    Route::post('read/{id}', 'read')->whereUuid('id')->name('read');
    Route::post('read-all', 'readAll')->name('readAll');
    Route::post('preferences', 'preferences')->name('preferences');
});

Route::domain(config('platform.frontend_domain'))->prefix('api/v1/wallets')->name('wallets.')->middleware('frontend.auth')->controller(MemberWalletController::class)->group(function (): void {
    Route::get('list', 'list')->name('list');
    Route::get('history', 'history')->name('history');
    Route::get('statistics', 'statistics')->name('statistics');
});

Route::domain(config('platform.frontend_domain'))->prefix('api/v1/documents')->name('documents.')->middleware('frontend.locale')->controller(DocumentController::class)->group(function (): void {
    Route::get('navigation', 'navigation')->name('navigation');
    Route::get('{type}/{slug}', 'show')->where(['type' => 'development|usage', 'slug' => '[a-z0-9-]+'])->name('show');
});

Route::domain(config('platform.frontend_domain'))->get('/', PageController::class)->name('frontend.home');
Route::domain(config('platform.frontend_domain'))->get('/auth/reset', PageController::class)->name('frontend.auth.reset');
Route::domain(config('platform.frontend_domain'))->get('/{path}', PageController::class)->where('path', '^(?!api/).*$')->name('frontend.page');
