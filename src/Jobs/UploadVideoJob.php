<?php

namespace Ekstremedia\LaravelYouTube\Jobs;

use Ekstremedia\LaravelYouTube\Facades\YouTube;
use Ekstremedia\LaravelYouTube\Models\YouTubeUpload;
use Exception;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

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
        public YouTubeUpload $upload
    ) {
        $this->onQueue(config('youtube.queue.name', 'default'));
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        try {
            // Mark upload as started
            $this->upload->markAsStarted();

            $videoPath = Storage::disk('local')->path($this->upload->file_path);

            Log::info("Starting YouTube upload job for user {$this->upload->user_id}", [
                'upload_id' => $this->upload->id,
                'video_path' => $videoPath,
                'title' => $this->upload->title,
            ]);

            // Check if file exists
            if (! file_exists($videoPath)) {
                throw new Exception("Video file not found: {$videoPath}");
            }

            // Get the token for this upload
            $token = $this->upload->token;
            if (! $token) {
                throw new Exception('YouTube token not found for this upload');
            }

            // Initialize YouTube service for the user
            $youtube = YouTube::forUser($this->upload->user_id, $token->channel_id);

            // Prepare metadata
            $metadata = [
                'title' => $this->upload->title,
                'description' => $this->upload->description ?? '',
                'tags' => is_array($this->upload->tags) ? $this->upload->tags : [],
                'privacy_status' => $this->upload->privacy_status ?? 'private',
                'category_id' => $this->upload->category_id,
            ];

            // Mark as processing
            $this->upload->markAsProcessing();

            // Upload the video with progress tracking
            $uploadedVideo = $youtube->uploadVideo(
                $videoPath,
                $metadata,
                [
                    'progress_callback' => function ($bytesUploaded, $totalBytes) {
                        $progress = (int) round(($bytesUploaded / $totalBytes) * 100);
                        $this->upload->updateProgress($progress);
                        Log::debug("Upload progress: {$progress}%", [
                            'upload_id' => $this->upload->id,
                            'bytes_uploaded' => $bytesUploaded,
                            'total_bytes' => $totalBytes,
                        ]);
                    },
                ]
            );

            Log::info('Video uploaded successfully', [
                'upload_id' => $this->upload->id,
                'video_id' => $uploadedVideo->video_id,
                'title' => $uploadedVideo->title,
            ]);

            // Mark upload as completed
            $this->upload->markAsCompleted($uploadedVideo->video_id);

            // Clean up temporary file
            Storage::disk('local')->delete($this->upload->file_path);

        } catch (Exception $e) {
            Log::error('YouTube upload job failed', [
                'upload_id' => $this->upload->id,
                'user_id' => $this->upload->user_id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            // Mark upload as failed
            $this->upload->markAsFailed($e->getMessage());

            // Rethrow to trigger retry mechanism
            throw $e;
        }
    }

    /**
     * Handle a job failure.
     */
    public function failed(?Exception $exception): void
    {
        Log::error('YouTube upload job permanently failed', [
            'upload_id' => $this->upload->id,
            'user_id' => $this->upload->user_id,
            'title' => $this->upload->title,
            'error' => $exception ? $exception->getMessage() : 'Unknown error',
        ]);

        // Mark upload as failed
        $this->upload->markAsFailed($exception ? $exception->getMessage() : 'Unknown error');

        // Clean up temporary file
        try {
            Storage::disk('local')->delete($this->upload->file_path);
        } catch (Exception $e) {
            Log::warning('Failed to delete temporary file', [
                'upload_id' => $this->upload->id,
                'file_path' => $this->upload->file_path,
                'error' => $e->getMessage(),
            ]);
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
        return ['youtube', 'upload', "user:{$this->upload->user_id}", "upload:{$this->upload->id}"];
    }
}
