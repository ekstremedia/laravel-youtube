<?php

use EkstreMedia\LaravelYouTube\Http\Controllers\Admin\ChannelsController;
use EkstreMedia\LaravelYouTube\Http\Controllers\Admin\DashboardController;
use EkstreMedia\LaravelYouTube\Http\Controllers\Admin\PlaylistsController;
use EkstreMedia\LaravelYouTube\Http\Controllers\Admin\TokensController;
use EkstreMedia\LaravelYouTube\Http\Controllers\Admin\UploadController;
use EkstreMedia\LaravelYouTube\Http\Controllers\Admin\VideosController;
use Illuminate\Support\Facades\Route;

Route::group([
    'prefix' => config('youtube.admin.prefix', 'youtube-admin'),
    'middleware' => array_merge(
        config('youtube.admin.middleware', ['web']),
        config('youtube.admin.auth_middleware', ['auth'])
    ),
    'as' => 'youtube.admin.',
], function () {
    // Dashboard
    Route::get('/', [DashboardController::class, 'index'])->name('dashboard');

    // Channels management
    Route::get('/channels', [ChannelsController::class, 'index'])->name('channels.index');
    Route::get('/channels/{id}', [ChannelsController::class, 'show'])->name('channels.show');
    Route::post('/channels/sync', [ChannelsController::class, 'sync'])->name('channels.sync');

    // Videos management
    Route::get('/videos', [VideosController::class, 'index'])->name('videos.index');
    Route::get('/videos/{id}', [VideosController::class, 'show'])->name('videos.show');
    Route::get('/videos/{id}/edit', [VideosController::class, 'edit'])->name('videos.edit');
    Route::put('/videos/{id}', [VideosController::class, 'update'])->name('videos.update');
    Route::delete('/videos/{id}', [VideosController::class, 'destroy'])->name('videos.destroy');
    Route::post('/videos/{id}/sync', [VideosController::class, 'sync'])->name('videos.sync');

    // Upload interface
    Route::get('/upload', [UploadController::class, 'index'])->name('upload.index');
    Route::post('/upload', [UploadController::class, 'store'])->name('upload.store');
    Route::get('/upload/progress/{id}', [UploadController::class, 'progress'])->name('upload.progress');

    // Playlists management
    Route::get('/playlists', [PlaylistsController::class, 'index'])->name('playlists.index');
    Route::get('/playlists/create', [PlaylistsController::class, 'create'])->name('playlists.create');
    Route::post('/playlists', [PlaylistsController::class, 'store'])->name('playlists.store');
    Route::get('/playlists/{id}', [PlaylistsController::class, 'show'])->name('playlists.show');
    Route::get('/playlists/{id}/edit', [PlaylistsController::class, 'edit'])->name('playlists.edit');
    Route::put('/playlists/{id}', [PlaylistsController::class, 'update'])->name('playlists.update');
    Route::delete('/playlists/{id}', [PlaylistsController::class, 'destroy'])->name('playlists.destroy');

    // Token management
    Route::get('/tokens', [TokensController::class, 'index'])->name('tokens.index');
    Route::delete('/tokens/{id}', [TokensController::class, 'destroy'])->name('tokens.destroy');
    Route::post('/tokens/{id}/refresh', [TokensController::class, 'refresh'])->name('tokens.refresh');
    Route::post('/tokens/{id}/activate', [TokensController::class, 'activate'])->name('tokens.activate');
    Route::post('/tokens/{id}/deactivate', [TokensController::class, 'deactivate'])->name('tokens.deactivate');
});
