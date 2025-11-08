<?php

use Illuminate\Support\Facades\Route;
use EkstreMedia\LaravelYouTube\Http\Controllers\Api\ChannelController;
use EkstreMedia\LaravelYouTube\Http\Controllers\Api\VideoController;
use EkstreMedia\LaravelYouTube\Http\Controllers\Api\PlaylistController;
use EkstreMedia\LaravelYouTube\Http\Controllers\Api\UploadController;

Route::group([
    'prefix' => 'api/' . config('youtube.routes.prefix', 'youtube'),
    'middleware' => config('youtube.routes.api_middleware', ['api', 'throttle:60,1']),
    'as' => 'youtube.api.',
], function () {
    // Channel endpoints
    Route::get('/channel', [ChannelController::class, 'show'])->name('channel.show');
    Route::get('/channel/videos', [ChannelController::class, 'videos'])->name('channel.videos');
    Route::get('/channel/playlists', [ChannelController::class, 'playlists'])->name('channel.playlists');

    // Video endpoints
    Route::get('/videos', [VideoController::class, 'index'])->name('videos.index');
    Route::get('/videos/{id}', [VideoController::class, 'show'])->name('videos.show');
    Route::put('/videos/{id}', [VideoController::class, 'update'])->name('videos.update');
    Route::delete('/videos/{id}', [VideoController::class, 'destroy'])->name('videos.destroy');
    Route::post('/videos/{id}/thumbnail', [VideoController::class, 'updateThumbnail'])->name('videos.thumbnail');

    // Upload endpoints
    Route::post('/upload', [UploadController::class, 'upload'])->name('upload');
    Route::get('/upload/status/{id}', [UploadController::class, 'status'])->name('upload.status');

    // Playlist endpoints
    Route::get('/playlists', [PlaylistController::class, 'index'])->name('playlists.index');
    Route::post('/playlists', [PlaylistController::class, 'store'])->name('playlists.store');
    Route::get('/playlists/{id}', [PlaylistController::class, 'show'])->name('playlists.show');
    Route::put('/playlists/{id}', [PlaylistController::class, 'update'])->name('playlists.update');
    Route::delete('/playlists/{id}', [PlaylistController::class, 'destroy'])->name('playlists.destroy');
    Route::post('/playlists/{id}/videos', [PlaylistController::class, 'addVideo'])->name('playlists.add-video');
    Route::delete('/playlists/{id}/videos/{videoId}', [PlaylistController::class, 'removeVideo'])->name('playlists.remove-video');
});