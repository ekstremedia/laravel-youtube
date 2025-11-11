<?php

use Ekstremedia\LaravelYouTube\Http\Controllers\AuthController;
use Illuminate\Support\Facades\Route;

Route::group([
    'middleware' => config('youtube.routes.middleware', ['web']),
    'as' => 'youtube.',
], function () {
    // Authorization page (protected by auth middleware)
    Route::get(
        config('youtube.routes.auth_page.path', 'youtube-authorize'),
        [AuthController::class, 'index']
    )
        ->middleware(config('youtube.routes.auth_page.middleware', ['web', 'auth']))
        ->name('authorize');

    // OAuth flow (also protected)
    Route::get('/youtube/auth', [AuthController::class, 'redirect'])
        ->middleware(config('youtube.routes.auth_page.middleware', ['web', 'auth']))
        ->name('auth');

    // Callback is NOT protected (Google redirects here)
    Route::get('/youtube/callback', [AuthController::class, 'callback'])
        ->name('callback');

    // Revoke is protected
    Route::post('/youtube/revoke', [AuthController::class, 'revoke'])
        ->middleware(config('youtube.routes.auth_page.middleware', ['web', 'auth']))
        ->name('revoke');
});
