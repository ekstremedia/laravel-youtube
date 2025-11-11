<?php

use Ekstremedia\LaravelYouTube\Http\Controllers\AuthController;
use Illuminate\Support\Facades\Route;

Route::group([
    'middleware' => ['web'],
    'as' => 'youtube.',
], function () {
    // Authorization page (main entry point)
    Route::get(
        config('youtube.routes.auth_page.path', 'youtube-authorize'),
        [AuthController::class, 'index']
    )->name('authorize');

    // OAuth flow
    Route::get('/youtube/auth', [AuthController::class, 'redirect'])->name('auth');
    Route::get('/youtube/callback', [AuthController::class, 'callback'])->name('callback');
    Route::post('/youtube/revoke', [AuthController::class, 'revoke'])->name('revoke');
});
