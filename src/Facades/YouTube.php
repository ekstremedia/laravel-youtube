<?php

namespace Ekstremedia\LaravelYouTube\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @method static \Ekstremedia\LaravelYouTube\Services\YouTubeService usingDefault(?string $channelId = null)
 * @method static \Ekstremedia\LaravelYouTube\Services\YouTubeService forChannel(string $channelId)
 * @method static \Ekstremedia\LaravelYouTube\Services\YouTubeService forUser(int $userId, ?string $channelId = null)
 * @method static \Ekstremedia\LaravelYouTube\Services\YouTubeService withToken(\Ekstremedia\LaravelYouTube\Models\YouTubeToken $token)
 * @method static array getChannel(array $parts = ['snippet', 'statistics', 'contentDetails'])
 * @method static array getVideos(array $options = [])
 * @method static array getVideo(string $videoId, array $parts = ['snippet', 'statistics', 'status', 'contentDetails'])
 * @method static \Ekstremedia\LaravelYouTube\Models\YouTubeVideo uploadVideo($video, array $metadata, array $options = [])
 * @method static array updateVideo(string $videoId, array $metadata)
 * @method static bool deleteVideo(string $videoId)
 * @method static bool setThumbnail(string $videoId, $thumbnail)
 *
 * @see \Ekstremedia\LaravelYouTube\Services\YouTubeService
 */
class YouTube extends Facade
{
    /**
     * Get the registered name of the component.
     */
    protected static function getFacadeAccessor(): string
    {
        return 'youtube';
    }
}
