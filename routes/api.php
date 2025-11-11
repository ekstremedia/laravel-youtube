<?php

use Ekstremedia\LaravelYouTube\Http\Controllers\Api\ChannelController;
use Ekstremedia\LaravelYouTube\Http\Controllers\Api\UploadController;
use Ekstremedia\LaravelYouTube\Http\Controllers\Api\VideoController;
use Illuminate\Support\Facades\Route;

Route::group([
    'prefix' => 'api/' . config('youtube.routes.prefix', 'youtube'),
    'middleware' => array_merge(
        config('youtube.routes.api_middleware', ['api']),
        ['youtube.auth', 'youtube.ip', 'youtube.ratelimit'] // Add security middleware
    ),
    'as' => 'youtube.api.',
], function () {
    // Channel endpoints
    Route::get('/channel', [ChannelController::class, 'show'])->name('channel.show');
    Route::get('/channel/videos', [ChannelController::class, 'videos'])->name('channel.videos');

    // Video endpoints
    Route::get('/videos', [VideoController::class, 'index'])->name('videos.index');
    Route::get('/videos/{id}', [VideoController::class, 'show'])->name('videos.show');
    Route::put('/videos/{id}', [VideoController::class, 'update'])->name('videos.update');
    Route::delete('/videos/{id}', [VideoController::class, 'destroy'])->name('videos.destroy');
    Route::post('/videos/{id}/thumbnail', [VideoController::class, 'updateThumbnail'])->name('videos.thumbnail');

    // Upload endpoints
    Route::post('/upload', [UploadController::class, 'upload'])->name('upload');
    Route::get('/upload/status/{id}', [UploadController::class, 'status'])->name('upload.status');
});
