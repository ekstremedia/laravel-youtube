<?php

use Ekstremedia\LaravelYouTube\Http\Controllers\AuthController;
use Illuminate\Support\Facades\Route;

Route::group([
    'prefix' => config('youtube.routes.prefix', 'youtube'),
    'middleware' => config('youtube.routes.middleware', ['web']),
    'as' => 'youtube.',
], function () {
    // OAuth authentication routes
    Route::get('/auth', [AuthController::class, 'redirect'])->name('auth');
    Route::get('/callback', [AuthController::class, 'callback'])->name('callback');
    Route::post('/revoke', [AuthController::class, 'revoke'])->name('revoke')->middleware('auth');
    Route::get('/status', [AuthController::class, 'status'])->name('status')->middleware('auth');
});
