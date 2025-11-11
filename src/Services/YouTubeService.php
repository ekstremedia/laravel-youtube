<?php

namespace Ekstremedia\LaravelYouTube\Services;

use Ekstremedia\LaravelYouTube\Exceptions\QuotaExceededException;
use Ekstremedia\LaravelYouTube\Exceptions\TokenException;
use Ekstremedia\LaravelYouTube\Exceptions\UploadException;
use Ekstremedia\LaravelYouTube\Exceptions\YouTubeException;
use Ekstremedia\LaravelYouTube\Models\YouTubeToken;
use Ekstremedia\LaravelYouTube\Models\YouTubeVideo;
use Google_Http_MediaFileUpload;
use Google_Service_YouTube;
use Google_Service_YouTube_Video;
use Google_Service_YouTube_VideoSnippet;
use Google_Service_YouTube_VideoStatus;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Log;

class YouTubeService
{
    /**
     * Token manager instance
     */
    protected TokenManager $tokenManager;

    /**
     * Auth service instance
     */
    protected AuthService $authService;

    /**
     * Configuration array
     */
    protected array $config;

    /**
     * Current YouTube service instance
     */
    protected ?Google_Service_YouTube $youtube = null;

    /**
     * Current active token
     */
    protected ?YouTubeToken $activeToken = null;

    /**
     * Create a new YouTube service instance
     */
    public function __construct(TokenManager $tokenManager, AuthService $authService, array $config)
    {
        $this->tokenManager = $tokenManager;
        $this->authService = $authService;
        $this->config = $config;
    }

    /**
     * Set the active token for operations
     *
     * @return $this
     *
     * @throws TokenException
     */
    public function forUser(int $userId, ?string $channelId = null): self
    {
        $token = $this->tokenManager->getActiveToken($userId, $channelId);

        if (! $token) {
            throw new TokenException('No active YouTube token found for user');
        }

        $this->setActiveToken($token);

        return $this;
    }

    /**
     * Set a specific token as active
     *
     * @return $this
     *
     * @throws TokenException
     */
    public function withToken(YouTubeToken $token): self
    {
        $this->setActiveToken($token);

        return $this;
    }

    /**
     * Use the default token (useful for API/background jobs without user context)
     *
     * @return $this
     *
     * @throws TokenException
     */
    public function usingDefault(?string $channelId = null): self
    {
        $query = YouTubeToken::where('is_active', true);

        if ($channelId) {
            $query->where('channel_id', $channelId);
        }

        $token = $query->orderBy('last_refreshed_at', 'desc')->first();

        if (! $token) {
            throw new TokenException('No active YouTube token found');
        }

        $this->setActiveToken($token);

        return $this;
    }

    /**
     * Use a specific channel by channel ID (without requiring user ID)
     *
     * @return $this
     *
     * @throws TokenException
     */
    public function forChannel(string $channelId): self
    {
        $token = YouTubeToken::where('channel_id', $channelId)
            ->where('is_active', true)
            ->orderBy('last_refreshed_at', 'desc')
            ->first();

        if (! $token) {
            throw new TokenException("No active YouTube token found for channel: {$channelId}");
        }

        $this->setActiveToken($token);

        return $this;
    }

    /**
     * Set the active token and initialize YouTube service
     *
     * @throws TokenException
     */
    protected function setActiveToken(YouTubeToken $token): void
    {
        // Check if token needs refresh
        if ($this->tokenManager->needsRefresh($token)) {
            $this->refreshToken($token);
        }

        $this->activeToken = $token;

        // Get decrypted access token
        $accessToken = $this->tokenManager->getAccessToken($token);

        // Create YouTube service with the token
        $this->youtube = $this->authService->createYouTubeService([
            'access_token' => $accessToken,
            'token_type' => $token->token_type,
            'expires_in' => $token->expires_in,
        ]);
    }

    /**
     * Refresh an expired token
     *
     * @throws TokenException
     */
    protected function refreshToken(YouTubeToken $token): void
    {
        try {
            $refreshToken = $this->tokenManager->getRefreshToken($token);
            $newTokenData = $this->authService->refreshAccessToken($refreshToken);
            $this->tokenManager->updateToken($token, $newTokenData);

            Log::info('YouTube token refreshed', [
                'token_id' => $token->id,
                'channel_id' => $token->channel_id,
            ]);
        } catch (\Exception $e) {
            $this->tokenManager->markTokenFailed($token, $e->getMessage());
            throw new TokenException('Failed to refresh token: ' . $e->getMessage(), 0, $e);
        }
    }

    /**
     * Get channel information
     *
     * @param  array  $parts  Parts to retrieve
     *
     * @throws YouTubeException
     */
    public function getChannel(array $parts = ['snippet', 'statistics', 'contentDetails']): array
    {
        $this->ensureAuthenticated();

        try {
            $response = $this->youtube->channels->listChannels(implode(',', $parts), [
                'mine' => true,
            ]);

            if (! $response->getItems() || count($response->getItems()) === 0) {
                throw new YouTubeException('No channel found for authenticated user');
            }

            $channel = $response->getItems()[0];

            return $this->formatChannelData($channel);
        } catch (\Google_Service_Exception $e) {
            throw $this->handleGoogleException($e);
        }
    }

    /**
     * Get videos from channel
     *
     * @param  array  $options  Query options
     *
     * @throws YouTubeException
     */
    public function getVideos(array $options = []): array
    {
        $this->ensureAuthenticated();

        $defaults = [
            'maxResults' => 50,
            'order' => 'date',
            'type' => 'video',
        ];

        $params = array_merge($defaults, $options);

        try {
            // First get the channel's uploads playlist
            $channelResponse = $this->youtube->channels->listChannels('contentDetails', [
                'mine' => true,
            ]);

            if (! $channelResponse->getItems()) {
                throw new YouTubeException('No channel found');
            }

            $uploadsPlaylistId = $channelResponse->getItems()[0]
                ->getContentDetails()
                ->getRelatedPlaylists()
                ->getUploads();

            // Get videos from uploads playlist
            $response = $this->youtube->playlistItems->listPlaylistItems(
                'snippet,contentDetails,status',
                array_merge($params, ['playlistId' => $uploadsPlaylistId])
            );

            $videos = [];
            foreach ($response->getItems() as $item) {
                $videos[] = $this->formatVideoData($item);
            }

            return [
                'videos' => $videos,
                'nextPageToken' => $response->getNextPageToken(),
                'prevPageToken' => $response->getPrevPageToken(),
                'totalResults' => $response->getPageInfo()->getTotalResults(),
            ];
        } catch (\Google_Service_Exception $e) {
            throw $this->handleGoogleException($e);
        }
    }

    /**
     * Get a single video by ID
     *
     * @param  array  $parts  Parts to retrieve
     *
     * @throws YouTubeException
     */
    public function getVideo(string $videoId, array $parts = ['snippet', 'statistics', 'status', 'contentDetails']): array
    {
        $this->ensureAuthenticated();

        try {
            $response = $this->youtube->videos->listVideos(implode(',', $parts), [
                'id' => $videoId,
            ]);

            if (! $response->getItems() || count($response->getItems()) === 0) {
                throw new YouTubeException("Video not found: {$videoId}");
            }

            return $this->formatVideoData($response->getItems()[0]);
        } catch (\Google_Service_Exception $e) {
            throw $this->handleGoogleException($e);
        }
    }

    /**
     * Upload a video to YouTube
     *
     * @param  UploadedFile|string  $video  Video file or path
     * @param  array  $metadata  Video metadata
     * @param  array  $options  Upload options
     *
     * @throws UploadException
     */
    public function uploadVideo($video, array $metadata, array $options = []): YouTubeVideo
    {
        $this->ensureAuthenticated();

        // Validate metadata
        $this->validateVideoMetadata($metadata);

        // Prepare video path
        $videoPath = $video instanceof UploadedFile
            ? $video->getPathname()
            : $video;

        if (! file_exists($videoPath)) {
            throw new UploadException("Video file not found: {$videoPath}");
        }

        // Validate file type
        $this->validateVideoFile($videoPath);

        // Check file size
        $fileSize = filesize($videoPath);
        $maxSize = $this->config['upload']['max_file_size'] ?? (128 * 1024 * 1024 * 1024);
        if ($fileSize > $maxSize) {
            throw new UploadException('Video file exceeds maximum allowed size');
        }

        try {
            // Create video resource
            $youtubeVideo = new Google_Service_YouTube_Video;

            // Set snippet
            $snippet = new Google_Service_YouTube_VideoSnippet;
            $snippet->setTitle($metadata['title']);
            $snippet->setDescription($metadata['description'] ?? '');
            $snippet->setTags($metadata['tags'] ?? []);
            $snippet->setCategoryId($metadata['category_id'] ?? $this->config['defaults']['category_id'] ?? '22');

            if (isset($metadata['default_language'])) {
                $snippet->setDefaultLanguage($metadata['default_language']);
            }

            $youtubeVideo->setSnippet($snippet);

            // Set status
            $status = new Google_Service_YouTube_VideoStatus;
            $status->setPrivacyStatus($metadata['privacy_status'] ?? $this->config['defaults']['privacy_status'] ?? 'private');

            if (isset($metadata['embeddable'])) {
                $status->setEmbeddable($metadata['embeddable']);
            }

            if (isset($metadata['made_for_kids'])) {
                $status->setMadeForKids($metadata['made_for_kids']);
            }

            if (isset($metadata['publish_at'])) {
                $status->setPublishAt($metadata['publish_at']);
            }

            $youtubeVideo->setStatus($status);

            // Configure upload
            $chunkSize = $options['chunk_size'] ?? $this->config['upload']['chunk_size'] ?? (1 * 1024 * 1024);
            $client = $this->authService->getClient();
            $client->setDefer(true);

            // Create upload request
            $insertRequest = $this->youtube->videos->insert('snippet,status', $youtubeVideo);

            // Create media upload
            $media = new Google_Http_MediaFileUpload(
                $client,
                $insertRequest,
                'video/*',
                null,
                true,
                $chunkSize
            );
            $media->setFileSize($fileSize);

            // Upload in chunks
            $status = false;
            $handle = fopen($videoPath, 'rb');

            while (! $status && ! feof($handle)) {
                $chunk = fread($handle, $chunkSize);
                $status = $media->nextChunk($chunk);
            }

            fclose($handle);
            $client->setDefer(false);

            if (! $status || ! isset($status['id'])) {
                throw new UploadException('Upload failed - no video ID returned');
            }

            // Store video in database
            $videoModel = new YouTubeVideo;
            $videoModel->fill([
                'user_id' => $this->activeToken->user_id,
                'token_id' => $this->activeToken->id,
                'video_id' => $status['id'],
                'channel_id' => $this->activeToken->channel_id,
                'title' => $metadata['title'],
                'description' => $metadata['description'] ?? '',
                'tags' => $metadata['tags'] ?? [],
                'category_id' => $metadata['category_id'] ?? $this->config['defaults']['category_id'] ?? '22',
                'privacy_status' => $metadata['privacy_status'] ?? $this->config['defaults']['privacy_status'] ?? 'private',
                'made_for_kids' => $metadata['made_for_kids'] ?? false,
                'upload_status' => 'uploaded',
                'processing_status' => 'processing',
            ]);
            $videoModel->save();

            // Set thumbnail if provided
            if (isset($metadata['thumbnail'])) {
                $this->setThumbnail($status['id'], $metadata['thumbnail']);
            }

            Log::info('Video uploaded successfully', [
                'video_id' => $status['id'],
                'user_id' => $this->activeToken->user_id,
                'channel_id' => $this->activeToken->channel_id,
            ]);

            return $videoModel;
        } catch (\Google_Service_Exception $e) {
            throw $this->handleGoogleException($e);
        } catch (\Exception $e) {
            throw new UploadException('Upload failed: ' . $e->getMessage(), 0, $e);
        }
    }

    /**
     * Update video metadata
     *
     * @throws YouTubeException
     */
    public function updateVideo(string $videoId, array $metadata): array
    {
        $this->ensureAuthenticated();

        try {
            // Get existing video
            $response = $this->youtube->videos->listVideos('snippet,status', ['id' => $videoId]);

            if (! $response->getItems()) {
                throw new YouTubeException("Video not found: {$videoId}");
            }

            $video = $response->getItems()[0];

            // Update snippet if provided
            if (isset($metadata['title']) || isset($metadata['description']) || isset($metadata['tags']) || isset($metadata['category_id'])) {
                $snippet = $video->getSnippet();

                if (isset($metadata['title'])) {
                    $snippet->setTitle($metadata['title']);
                }
                if (isset($metadata['description'])) {
                    $snippet->setDescription($metadata['description']);
                }
                if (isset($metadata['tags'])) {
                    $snippet->setTags($metadata['tags']);
                }
                if (isset($metadata['category_id'])) {
                    $snippet->setCategoryId($metadata['category_id']);
                }

                $video->setSnippet($snippet);
            }

            // Update status if provided
            if (isset($metadata['privacy_status']) || isset($metadata['embeddable']) || isset($metadata['made_for_kids'])) {
                $status = $video->getStatus();

                if (isset($metadata['privacy_status'])) {
                    $status->setPrivacyStatus($metadata['privacy_status']);
                }
                if (isset($metadata['embeddable'])) {
                    $status->setEmbeddable($metadata['embeddable']);
                }
                if (isset($metadata['made_for_kids'])) {
                    $status->setMadeForKids($metadata['made_for_kids']);
                }

                $video->setStatus($status);
            }

            // Update video
            $updatedVideo = $this->youtube->videos->update('snippet,status', $video);

            // Update database record if exists
            $videoModel = YouTubeVideo::where('video_id', $videoId)->first();
            if ($videoModel) {
                $videoModel->update([
                    'title' => $metadata['title'] ?? $videoModel->title,
                    'description' => $metadata['description'] ?? $videoModel->description,
                    'tags' => $metadata['tags'] ?? $videoModel->tags,
                    'category_id' => $metadata['category_id'] ?? $videoModel->category_id,
                    'privacy_status' => $metadata['privacy_status'] ?? $videoModel->privacy_status,
                    'embeddable' => $metadata['embeddable'] ?? $videoModel->embeddable,
                    'made_for_kids' => $metadata['made_for_kids'] ?? $videoModel->made_for_kids,
                ]);
            }

            Log::info('Video updated successfully', [
                'video_id' => $videoId,
                'updates' => array_keys($metadata),
            ]);

            return $this->formatVideoData($updatedVideo);
        } catch (\Google_Service_Exception $e) {
            throw $this->handleGoogleException($e);
        }
    }

    /**
     * Delete a video
     *
     * @throws YouTubeException
     */
    public function deleteVideo(string $videoId): bool
    {
        $this->ensureAuthenticated();

        try {
            $this->youtube->videos->delete($videoId);

            // Delete from database if exists
            YouTubeVideo::where('video_id', $videoId)->delete();

            Log::info('Video deleted successfully', [
                'video_id' => $videoId,
                'user_id' => $this->activeToken->user_id,
            ]);

            return true;
        } catch (\Google_Service_Exception $e) {
            throw $this->handleGoogleException($e);
        }
    }

    /**
     * Set video thumbnail
     *
     * @param  UploadedFile|string  $thumbnail
     *
     * @throws YouTubeException
     */
    public function setThumbnail(string $videoId, $thumbnail): bool
    {
        $this->ensureAuthenticated();

        $thumbnailPath = $thumbnail instanceof UploadedFile
            ? $thumbnail->getPathname()
            : $thumbnail;

        if (! file_exists($thumbnailPath)) {
            throw new YouTubeException("Thumbnail file not found: {$thumbnailPath}");
        }

        try {
            $client = $this->authService->getClient();
            $client->setDefer(true);

            $setRequest = $this->youtube->thumbnails->set($videoId);

            $media = new Google_Http_MediaFileUpload(
                $client,
                $setRequest,
                'image/png',
                null,
                true,
                1 * 1024 * 1024
            );

            $media->setFileSize(filesize($thumbnailPath));

            $status = false;
            $handle = fopen($thumbnailPath, 'rb');

            while (! $status && ! feof($handle)) {
                $chunk = fread($handle, 1 * 1024 * 1024);
                $status = $media->nextChunk($chunk);
            }

            fclose($handle);
            $client->setDefer(false);

            Log::info('Thumbnail set successfully', ['video_id' => $videoId]);

            return true;
        } catch (\Google_Service_Exception $e) {
            throw $this->handleGoogleException($e);
        }
    }

    /**
     * Ensure user is authenticated
     *
     * @throws YouTubeException
     */
    protected function ensureAuthenticated(): void
    {
        if (! $this->activeToken) {
            throw new YouTubeException('No active token set. Use forUser() or withToken() first.');
        }

        if (! $this->youtube) {
            throw new YouTubeException('YouTube service not initialized');
        }
    }

    /**
     * Handle Google service exceptions
     */
    protected function handleGoogleException(\Google_Service_Exception $e): YouTubeException
    {
        $errors = $e->getErrors();

        if (! empty($errors)) {
            $error = $errors[0];
            $reason = $error['reason'] ?? '';

            if ($reason === 'quotaExceeded') {
                return new QuotaExceededException(
                    'YouTube API quota exceeded. Please try again later.',
                    $e->getCode(),
                    $e
                );
            }
        }

        return YouTubeException::fromGoogleServiceException($e);
    }

    /**
     * Format channel data
     *
     * @param  mixed  $channel
     */
    protected function formatChannelData($channel): array
    {
        $snippet = $channel->getSnippet();
        $statistics = $channel->getStatistics();
        $contentDetails = $channel->getContentDetails();

        $data = [
            'id' => $channel->getId(),
        ];

        // Add snippet data if available
        if ($snippet) {
            $data['title'] = $snippet->getTitle();
            $data['description'] = $snippet->getDescription();
            $data['custom_url'] = $snippet->getCustomUrl();
            $data['published_at'] = $snippet->getPublishedAt();
            $data['country'] = $snippet->getCountry();

            // Add thumbnails if available
            $thumbnails = $snippet->getThumbnails();
            if ($thumbnails) {
                $data['thumbnails'] = [];
                if ($thumbnails->getDefault()) {
                    $data['thumbnails']['default'] = $thumbnails->getDefault()->getUrl();
                }
                if ($thumbnails->getMedium()) {
                    $data['thumbnails']['medium'] = $thumbnails->getMedium()->getUrl();
                }
                if ($thumbnails->getHigh()) {
                    $data['thumbnails']['high'] = $thumbnails->getHigh()->getUrl();
                }
            }
        }

        // Add statistics data if available
        if ($statistics) {
            $data['view_count'] = $statistics->getViewCount();
            $data['subscriber_count'] = $statistics->getSubscriberCount();
            $data['video_count'] = $statistics->getVideoCount();
        }

        // Add content details if available
        if ($contentDetails) {
            $relatedPlaylists = $contentDetails->getRelatedPlaylists();
            if ($relatedPlaylists) {
                $data['uploads_playlist'] = $relatedPlaylists->getUploads();
            }
        }

        return $data;
    }

    /**
     * Format video data
     *
     * @param  mixed  $video
     */
    protected function formatVideoData($video): array
    {
        $data = ['id' => null];

        // Handle both Video and PlaylistItem objects
        if (method_exists($video, 'getId')) {
            $data['id'] = $video->getId();
        } elseif (method_exists($video, 'getContentDetails')) {
            $data['id'] = $video->getContentDetails()->getVideoId();
        }

        if (method_exists($video, 'getSnippet')) {
            $snippet = $video->getSnippet();
            if ($snippet) {
                $data['title'] = $snippet->getTitle();
                $data['description'] = $snippet->getDescription();
                $data['published_at'] = $snippet->getPublishedAt();
                $data['channel_id'] = $snippet->getChannelId();
                $data['tags'] = $snippet->getTags();
                $data['category_id'] = $snippet->getCategoryId();

                if ($snippet->getThumbnails()) {
                    $data['thumbnails'] = [
                        'default' => $snippet->getThumbnails()->getDefault() ? $snippet->getThumbnails()->getDefault()->getUrl() : null,
                        'medium' => $snippet->getThumbnails()->getMedium() ? $snippet->getThumbnails()->getMedium()->getUrl() : null,
                        'high' => $snippet->getThumbnails()->getHigh() ? $snippet->getThumbnails()->getHigh()->getUrl() : null,
                        'standard' => $snippet->getThumbnails()->getStandard() ? $snippet->getThumbnails()->getStandard()->getUrl() : null,
                        'maxres' => $snippet->getThumbnails()->getMaxres() ? $snippet->getThumbnails()->getMaxres()->getUrl() : null,
                    ];
                }
            }
        }

        if (method_exists($video, 'getStatistics')) {
            $statistics = $video->getStatistics();
            if ($statistics) {
                $data['view_count'] = $statistics->getViewCount();
                $data['like_count'] = $statistics->getLikeCount();
                $data['dislike_count'] = $statistics->getDislikeCount();
                $data['comment_count'] = $statistics->getCommentCount();
            }
        }

        if (method_exists($video, 'getStatus')) {
            $status = $video->getStatus();
            if ($status) {
                $data['privacy_status'] = $status->getPrivacyStatus();
                $data['embeddable'] = $status->getEmbeddable();
                $data['license'] = $status->getLicense();
                $data['made_for_kids'] = $status->getMadeForKids();
                $data['upload_status'] = $status->getUploadStatus();
                $data['failure_reason'] = $status->getFailureReason();
                $data['rejection_reason'] = $status->getRejectionReason();
            }
        }

        if (method_exists($video, 'getContentDetails')) {
            $contentDetails = $video->getContentDetails();
            if ($contentDetails) {
                $data['duration'] = $contentDetails->getDuration();
                $data['definition'] = $contentDetails->getDefinition();
                $data['caption'] = $contentDetails->getCaption();
                $data['licensed_content'] = $contentDetails->getLicensedContent();
                $data['projection'] = $contentDetails->getProjection();
            }
        }

        return $data;
    }

    /**
     * Validate video metadata before upload
     *
     *
     * @throws UploadException
     */
    protected function validateVideoMetadata(array $metadata): void
    {
        // Title is required
        if (empty($metadata['title'])) {
            throw new UploadException('Video title is required');
        }

        // Validate title length (YouTube limit is 100 characters)
        if (strlen($metadata['title']) > 100) {
            throw new UploadException('Video title cannot exceed 100 characters');
        }

        // Validate description length (YouTube limit is 5000 characters)
        if (isset($metadata['description']) && strlen($metadata['description']) > 5000) {
            throw new UploadException('Video description cannot exceed 5000 characters');
        }

        // Validate tags
        if (isset($metadata['tags'])) {
            if (! is_array($metadata['tags'])) {
                throw new UploadException('Tags must be an array');
            }

            foreach ($metadata['tags'] as $tag) {
                if (strlen($tag) > 500) {
                    throw new UploadException('Individual tags cannot exceed 500 characters');
                }
            }

            if (count($metadata['tags']) > 500) {
                throw new UploadException('Cannot have more than 500 tags');
            }
        }

        // Validate privacy status
        if (isset($metadata['privacy_status'])) {
            $allowedStatuses = ['private', 'unlisted', 'public'];
            if (! in_array($metadata['privacy_status'], $allowedStatuses)) {
                throw new UploadException('Privacy status must be one of: ' . implode(', ', $allowedStatuses));
            }
        }

        // Validate category ID (valid YouTube category IDs)
        if (isset($metadata['category_id'])) {
            $validCategories = [
                '1', '2', '10', '15', '17', '18', '19', '20', '21', '22',
                '23', '24', '25', '26', '27', '28', '29', '30', '31', '32',
                '33', '34', '35', '36', '37', '38', '39', '40', '41', '42', '43', '44',
            ];
            if (! in_array($metadata['category_id'], $validCategories)) {
                throw new UploadException('Invalid category ID');
            }
        }

        // Validate boolean fields
        if (isset($metadata['made_for_kids']) && ! is_bool($metadata['made_for_kids'])) {
            throw new UploadException('made_for_kids must be a boolean value');
        }

        if (isset($metadata['embeddable']) && ! is_bool($metadata['embeddable'])) {
            throw new UploadException('embeddable must be a boolean value');
        }
    }

    /**
     * Validate video file before upload
     *
     *
     * @throws UploadException
     */
    protected function validateVideoFile(string $videoPath): void
    {
        // Validate MIME type
        $mimeType = mime_content_type($videoPath);
        $allowedMimeTypes = $this->config['security']['allowed_upload_mime_types'] ?? [];

        if (! empty($allowedMimeTypes) && ! in_array($mimeType, $allowedMimeTypes)) {
            throw new UploadException("Invalid file type: {$mimeType}. Only video files are allowed.");
        }

        // Validate file extension
        $extension = strtolower(pathinfo($videoPath, PATHINFO_EXTENSION));
        $allowedExtensions = $this->config['security']['allowed_upload_extensions'] ?? [];

        if (! empty($allowedExtensions) && ! in_array($extension, $allowedExtensions)) {
            throw new UploadException("Invalid file extension: {$extension}. Allowed: " . implode(', ', $allowedExtensions));
        }
    }
}
