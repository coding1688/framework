<?php

use App\Http\Controllers\Backend\System\SystemNotificationTemplateController;
use App\Http\Controllers\Backend\System\SystemQueueController;
use App\Http\Controllers\Backend\System\SystemScheduleController;
use App\Http\Controllers\Backend\System\SystemSettingDefinitionController;
use App\Http\Controllers\Backend\System\SystemSettingGroupController;
use App\Http\Controllers\Backend\System\SystemSettingValueController;
use Illuminate\Support\Facades\Route;

Route::prefix('system')->group(function (): void {
    Route::prefix('group')->name('development.settingGroup.')->controller(SystemSettingGroupController::class)->group(function (): void {
        Route::get('list', 'list')->name('list');
        Route::get('options', 'options')->name('options');
        Route::get('detail/{id}', 'detail')->whereNumber('id')->name('detail');
        Route::post('create', 'create')->name('create');
        Route::post('update/{id}', 'update')->whereNumber('id')->name('update');
        Route::post('delete/{id}', 'delete')->whereNumber('id')->name('delete');
    });
    Route::prefix('definition')->name('development.setting.')->controller(SystemSettingDefinitionController::class)->group(function (): void {
        Route::get('option-values', 'optionValues')->name('optionValues');
        Route::get('list', 'list')->name('list');
        Route::get('options', 'options')->name('options');
        Route::get('detail/{id}', 'detail')->whereNumber('id')->name('detail');
        Route::post('create', 'create')->name('create');
        Route::post('update/{id}', 'update')->whereNumber('id')->name('update');
        Route::post('delete/{id}', 'delete')->whereNumber('id')->name('delete');
    });
    Route::prefix('notification-template')->name('system.notificationTemplate.')->controller(SystemNotificationTemplateController::class)->group(function (): void {
        Route::get('list', 'list')->name('list');
        Route::get('detail/{id}', 'detail')->whereNumber('id')->name('detail');
        Route::post('create', 'create')->name('create');
        Route::post('update/{id}', 'update')->whereNumber('id')->name('update');
        Route::post('delete/{id}', 'delete')->whereNumber('id')->name('delete');
    });
    Route::prefix('queue')->name('system.queue.')->controller(SystemQueueController::class)->group(function (): void {
        Route::get('overview', 'overview')->name('overview');
        Route::get('jobs', 'jobs')->name('jobs');
        Route::get('failed', 'failed')->name('failed');
        Route::get('detail/{id}', 'detail')->whereNumber('id')->name('detail');
        Route::post('retry/{uuid}', 'retry')->whereUuid('uuid')->middleware('throttle:10,1')->name('retry');
        Route::post('retry-batch', 'retryBatch')->middleware('throttle:10,1')->name('retryBatch');
        Route::post('delete/{uuid}', 'delete')->whereUuid('uuid')->middleware('throttle:10,1')->name('delete');
    });
    Route::prefix('schedule')->name('system.schedule.')->controller(SystemScheduleController::class)->group(function (): void {
        Route::get('list', 'list')->name('list');
        Route::get('options', 'options')->name('options');
        Route::get('detail/{id}', 'detail')->whereNumber('id')->name('detail');
        Route::post('create', 'create')->name('create');
        Route::post('update/{id}', 'update')->whereNumber('id')->name('update');
        Route::post('status/{id}', 'status')->whereNumber('id')->name('status');
        Route::post('delete/{id}', 'delete')->whereNumber('id')->name('delete');
        Route::get('runs', 'runs')->name('runs');
        Route::get('run-detail/{id}', 'runDetail')->whereNumber('id')->name('runDetail');
        Route::post('run/{id}', 'run')->whereNumber('id')->middleware('throttle:10,1')->name('run');
        Route::get('settings', 'settings')->name('settings');
        Route::post('settings', 'updateSettings')->name('updateSettings');
    });
    Route::prefix('setting')->name('system.setting.')->controller(SystemSettingValueController::class)->group(function (): void {
        Route::get('list', 'list')->name('list');
        Route::get('options', 'options')->name('options');
        Route::post('update', 'update')->middleware('throttle:10,1')->name('update');
        Route::post('status/{id}', 'status')->whereNumber('id')->middleware('throttle:10,1')->name('status');
    });
});
