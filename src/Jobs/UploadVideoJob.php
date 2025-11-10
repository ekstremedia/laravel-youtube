<?php

namespace EkstreMedia\LaravelYouTube\Jobs;

use EkstreMedia\LaravelYouTube\Facades\YouTube;
use EkstreMedia\LaravelYouTube\Models\YouTubeVideo;
use Exception;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class UploadVideoJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    /**
     * The number of times the job may be attempted.
     */
    public $tries = 3;

    /**
     * The maximum number of unhandled exceptions to allow before failing.
     */
    public $maxExceptions = 3;

    /**
     * The number of seconds the job can run before timing out.
     */
    public $timeout = 7200; // 2 hours

    /**
     * Create a new job instance.
     */
    public function __construct(
        public int $userId,
        public string $videoPath,
        public array $metadata,
        public ?string $channelId = null,
        public ?string $playlistId = null,
        public ?string $thumbnailPath = null,
        public ?string $notifyUrl = null
    ) {
        $this->onQueue('media');
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        try {
            Log::info("Starting YouTube upload job for user {$this->userId}", [
                'video_path' => $this->videoPath,
                'metadata' => $this->metadata,
            ]);

            // Check if file exists
            if (! file_exists($this->videoPath)) {
                throw new Exception("Video file not found: {$this->videoPath}");
            }

            // Initialize YouTube service for the user
            $youtube = YouTube::forUser($this->userId, $this->channelId);

            // Upload the video with progress tracking
            $uploadedVideo = $youtube->uploadVideo(
                $this->videoPath,
                $this->metadata,
                [
                    'notify_url' => $this->notifyUrl,
                    'progress_callback' => function ($bytesUploaded, $totalBytes) {
                        $progress = round(($bytesUploaded / $totalBytes) * 100);
                        Log::debug("Upload progress: {$progress}%", [
                            'bytes_uploaded' => $bytesUploaded,
                            'total_bytes' => $totalBytes,
                        ]);
                    },
                ]
            );

            Log::info('Video uploaded successfully', [
                'video_id' => $uploadedVideo->video_id,
                'title' => $uploadedVideo->title,
            ]);

            // Set thumbnail if provided
            if ($this->thumbnailPath && file_exists($this->thumbnailPath)) {
                try {
                    $youtube->setThumbnail($uploadedVideo->video_id, $this->thumbnailPath);
                    Log::info("Thumbnail set successfully for video {$uploadedVideo->video_id}");
                } catch (Exception $e) {
                    Log::error('Failed to set thumbnail: ' . $e->getMessage());
                    // Don't fail the job if thumbnail upload fails
                }
            }

            // Add to playlist if specified
            if ($this->playlistId) {
                try {
                    $youtube->addToPlaylist($this->playlistId, $uploadedVideo->video_id);
                    Log::info("Video added to playlist {$this->playlistId}");
                } catch (Exception $e) {
                    Log::error('Failed to add video to playlist: ' . $e->getMessage());
                    // Don't fail the job if playlist addition fails
                }
            }

            // Notify webhook if provided
            if ($this->notifyUrl) {
                $this->notifyWebhook($uploadedVideo);
            }

            // Clean up temporary file if it's in temp directory
            if (str_contains($this->videoPath, 'temp') || str_contains($this->videoPath, 'tmp')) {
                @unlink($this->videoPath);
                if ($this->thumbnailPath) {
                    @unlink($this->thumbnailPath);
                }
            }

        } catch (Exception $e) {
            Log::error('YouTube upload job failed', [
                'user_id' => $this->userId,
                'video_path' => $this->videoPath,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            // Rethrow to trigger retry mechanism
            throw $e;
        }
    }

    /**
     * Notify webhook about upload completion.
     */
    protected function notifyWebhook(YouTubeVideo $video): void
    {
        try {
            $payload = [
                'status' => 'completed',
                'video_id' => $video->video_id,
                'title' => $video->title,
                'watch_url' => $video->watch_url,
                'privacy_status' => $video->privacy_status,
                'uploaded_at' => $video->created_at->toIso8601String(),
            ];

            $response = Http::timeout(10)
                ->retry(3, 100)
                ->post($this->notifyUrl, $payload);

            if ($response->successful()) {
                Log::info('Webhook notified successfully', ['url' => $this->notifyUrl]);
            } else {
                Log::warning('Webhook notification failed', [
                    'url' => $this->notifyUrl,
                    'status' => $response->status(),
                ]);
            }
        } catch (Exception $e) {
            Log::error('Failed to notify webhook', [
                'url' => $this->notifyUrl,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Handle a job failure.
     */
    public function failed(?Exception $exception): void
    {
        Log::error('YouTube upload job permanently failed', [
            'user_id' => $this->userId,
            'video_path' => $this->videoPath,
            'metadata' => $this->metadata,
            'error' => $exception ? $exception->getMessage() : 'Unknown error',
        ]);

        // Notify webhook about failure if provided
        if ($this->notifyUrl) {
            try {
                Http::timeout(10)->post($this->notifyUrl, [
                    'status' => 'failed',
                    'error' => $exception ? $exception->getMessage() : 'Unknown error',
                    'video_path' => basename($this->videoPath),
                ]);
            } catch (Exception $e) {
                Log::error('Failed to notify webhook about failure', [
                    'url' => $this->notifyUrl,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        // Clean up temporary files
        if (file_exists($this->videoPath) &&
            (str_contains($this->videoPath, 'temp') || str_contains($this->videoPath, 'tmp'))) {
            @unlink($this->videoPath);
            if ($this->thumbnailPath) {
                @unlink($this->thumbnailPath);
            }
        }
    }

    /**
     * Calculate the number of seconds to wait before retrying the job.
     */
    public function backoff(): array
    {
        return [60, 300, 900]; // 1 minute, 5 minutes, 15 minutes
    }

    /**
     * Get the tags that should be assigned to the job.
     */
    public function tags(): array
    {
        return ['youtube', 'upload', "user:{$this->userId}"];
    }
}
