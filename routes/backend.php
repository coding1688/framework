<?php

use App\Http\Controllers\Backend\Admin\AdminDepartmentController;
use App\Http\Controllers\Backend\Admin\AdminLogController;
use App\Http\Controllers\Backend\Admin\AdminMenuController;
use App\Http\Controllers\Backend\Admin\AdminUserController;
use App\Http\Controllers\Backend\AuthController;
use App\Http\Controllers\Backend\Content\ContentAnnouncementController;
use App\Http\Controllers\Backend\Content\ContentCarouselController;
use App\Http\Controllers\Backend\File\FileController;
use App\Http\Controllers\Backend\File\FileStorageAccountController;
use App\Http\Controllers\Backend\Member\MemberVipLevelController;
use App\Http\Controllers\Backend\Member\UserController;
use App\Http\Controllers\Backend\NotificationController;
use App\Http\Controllers\Backend\PageController;
use Illuminate\Support\Facades\Route;

Route::get('/login', PageController::class)->defaults('section', 'login')->name('backend.login');
Route::post('/auth/login', [AuthController::class, 'login'])->name('backend.auth.login');
Route::post('/auth/forgot-password', [AuthController::class, 'forgotPassword'])->middleware('throttle:6,1')->name('backend.auth.forgotPassword');
Route::post('/auth/reset-password', [AuthController::class, 'resetPassword'])->middleware('throttle:6,1')->name('backend.auth.resetPassword');

Route::middleware(['backend.auth', 'backend.securityPassword'])->group(function () {
    Route::get('/', PageController::class)->defaults('section', 'home')->name('backend.home');
    Route::get('/profile', PageController::class)->defaults('section', 'profile')->name('backend.profile');

    Route::prefix('auth')->name('backend.auth.')->controller(AuthController::class)->group(function () {
        Route::get('userinfo', 'userinfo')->name('userinfo');
        Route::post('logout', 'logout')->name('logout');
        Route::post('preferences', 'preferences')->name('preferences');
        Route::post('password', 'password')->middleware('throttle:10,1')->name('password');
        Route::post('security-password', 'securityPassword')->middleware('throttle:10,1')->name('securityPassword');
    });

    Route::prefix('notifications')->name('backend.notifications.')->controller(NotificationController::class)->group(function (): void {
        Route::get('list', 'list')->name('list');
        Route::post('read/{id}', 'read')->whereUuid('id')->name('read');
        Route::post('read-all', 'readAll')->name('readAll');
        Route::post('preferences', 'preferences')->name('preferences');
    });

    Route::prefix('admin')->middleware('backend.permission')->name('admin.')->group(function () {
        Route::prefix('user')->name('user.')->controller(AdminUserController::class)->group(function () {

            Route::get('options', 'options')->name('options');
            Route::get('list', 'list')->name('list');
            Route::post('create', 'create')->name('create');
            Route::get('detail/{id}', 'detail')->whereNumber('id')->name('detail');
            Route::post('update/{id}', 'update')->whereNumber('id')->name('update');
            Route::post('status/{id}', 'status')->whereNumber('id')->name('status');
            Route::post('delete/{id}', 'delete')->whereNumber('id')->name('delete');
            Route::post('password/{id}', 'password')->whereNumber('id')->name('password');
        });

        Route::prefix('department')->name('department.')->controller(AdminDepartmentController::class)->group(function () {

            Route::get('options', 'options')->name('options');
            Route::get('list', 'list')->name('list');
            Route::post('create', 'create')->name('create');
            Route::post('update/{id}', 'update')->whereNumber('id')->name('update');
            Route::post('status/{id}', 'status')->whereNumber('id')->name('status');
            Route::post('delete/{id}', 'delete')->whereNumber('id')->name('delete');
            Route::post('permissions/{id}', 'permissions')->whereNumber('id')->name('permissions');
            Route::get('employees/{id}', 'employees')->whereNumber('id')->name('employees');
            Route::post('leaders/{id}', 'leaders')->whereNumber('id')->name('leaders');
        });

        Route::prefix('menu')->name('menu.')->controller(AdminMenuController::class)->group(function () {
            Route::post('sync', 'sync')->name('sync');

            Route::get('list', 'list')->name('list');
            Route::post('create', 'create')->name('create');
            Route::post('update/{id}', 'update')->whereNumber('id')->name('update');
            Route::post('delete/{id}', 'delete')->whereNumber('id')->name('delete');
        });

        Route::prefix('log')->name('log.')->controller(AdminLogController::class)->group(function () {

            Route::get('list', 'list')->name('list');
        });
    });

    Route::prefix('admin')->middleware('backend.permission')->group(function () {
        Route::group([], base_path('routes/backend/system.php'));

        Route::prefix('frontend/user')->name('frontend.user.')->controller(UserController::class)->group(function (): void {
            Route::get('list', 'list')->name('list');
            Route::get('detail/{id}', 'detail')->whereNumber('id')->name('detail');
            Route::post('update/{id}', 'update')->whereNumber('id')->name('update');
            Route::post('status/{id}', 'status')->whereNumber('id')->name('status');
            Route::post('password/{id}', 'password')->whereNumber('id')->name('password');
            Route::post('verification/{id}', 'verification')->whereNumber('id')->name('verification');
            Route::get('vip-options', 'vipOptions')->name('vipOptions');
            Route::get('vip-history/{id}', 'vipHistory')->whereNumber('id')->name('vipHistory');
            Route::post('vip/{id}', 'vip')->whereNumber('id')->name('vip');
        });

        Route::prefix('member/vip-level')->name('member.vipLevel.')->controller(MemberVipLevelController::class)->group(function (): void {
            Route::get('list', 'list')->name('list');
            Route::get('options', 'options')->name('options');
            Route::get('detail/{id}', 'detail')->whereNumber('id')->name('detail');
            Route::post('create', 'create')->name('create');
            Route::post('update/{id}', 'update')->whereNumber('id')->name('update');
            Route::post('status/{id}', 'status')->whereNumber('id')->name('status');
            Route::post('delete/{id}', 'delete')->whereNumber('id')->name('delete');
        });

        Route::prefix('content')->name('content.')->group(function (): void {
            Route::prefix('announcement')->name('announcement.')->controller(ContentAnnouncementController::class)->group(function (): void {
                Route::get('list', 'list')->name('list');
                Route::get('detail/{id}', 'detail')->whereNumber('id')->name('detail');
                Route::post('create', 'create')->name('create');
                Route::post('update/{id}', 'update')->whereNumber('id')->name('update');
                Route::post('status/{id}', 'status')->whereNumber('id')->name('status');
                Route::post('delete/{id}', 'delete')->whereNumber('id')->name('delete');
            });
            Route::prefix('carousel')->name('carousel.')->controller(ContentCarouselController::class)->group(function (): void {
                Route::get('list', 'list')->name('list');
                Route::get('detail/{id}', 'detail')->whereNumber('id')->name('detail');
                Route::post('create', 'create')->name('create');
                Route::post('update/{id}', 'update')->whereNumber('id')->name('update');
                Route::post('status/{id}', 'status')->whereNumber('id')->name('status');
                Route::post('delete/{id}', 'delete')->whereNumber('id')->name('delete');
                Route::get('options', 'options')->name('options');
                Route::post('upload', 'upload')->name('upload');
            });
        });

        Route::prefix('file')->name('file.')->group(function () {
            Route::prefix('storage')->name('storage.')->controller(FileStorageAccountController::class)->group(function () {
                Route::get('list', 'list')->name('list');
                Route::get('settings', 'settings')->name('settings');
                Route::get('tasks', 'tasks')->name('tasks');
                Route::post('save-settings', 'saveSettings')->name('saveSettings');
                Route::post('create', 'create')->name('create');
                Route::post('update/{id}', 'update')->whereNumber('id')->name('update');
                Route::post('delete/{id}', 'delete')->whereNumber('id')->name('delete');
                Route::post('sync', 'sync')->name('sync');
                Route::post('retry/{id}', 'retry')->whereNumber('id')->name('retry');
            });
            Route::prefix('item')->name('item.')->controller(FileController::class)->group(function () {
                Route::get('list', 'list')->name('list');
                Route::get('options', 'options')->name('options');
                Route::get('detail/{id}', 'detail')->whereNumber('id')->name('detail');
                Route::post('upload', 'upload')->name('upload');
                Route::post('delete/{id}', 'delete')->whereNumber('id')->name('delete');
            });
        });

    });
    Route::get('{path}', [PageController::class, 'menu'])->where('path', 'admin/[a-zA-Z0-9/_-]+')->name('backend.menu');
});
