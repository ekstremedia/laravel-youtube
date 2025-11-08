<?php

namespace EkstreMedia\LaravelYouTube\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @method static \EkstreMedia\LaravelYouTube\Services\YouTubeService forUser(int $userId, ?string $channelId = null)
 * @method static \EkstreMedia\LaravelYouTube\Services\YouTubeService withToken(\EkstreMedia\LaravelYouTube\Models\YouTubeToken $token)
 * @method static array getChannel(array $parts = ['snippet', 'statistics', 'contentDetails'])
 * @method static array getVideos(array $options = [])
 * @method static array getVideo(string $videoId, array $parts = ['snippet', 'statistics', 'status', 'contentDetails'])
 * @method static \EkstreMedia\LaravelYouTube\Models\YouTubeVideo uploadVideo($video, array $metadata, array $options = [])
 * @method static array updateVideo(string $videoId, array $metadata)
 * @method static bool deleteVideo(string $videoId)
 * @method static bool setThumbnail(string $videoId, $thumbnail)
 * @method static array getPlaylists(array $options = [])
 * @method static array createPlaylist(array $data)
 * @method static bool addToPlaylist(string $playlistId, string $videoId, ?int $position = null)
 *
 * @see \EkstreMedia\LaravelYouTube\Services\YouTubeService
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
