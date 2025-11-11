<?php

namespace Ekstremedia\LaravelYouTube\Http\Controllers\Admin;

use Ekstremedia\LaravelYouTube\Http\Controllers\Controller;
use Ekstremedia\LaravelYouTube\Jobs\UploadVideoJob;
use Ekstremedia\LaravelYouTube\Models\YouTubeToken;
use Ekstremedia\LaravelYouTube\Models\YouTubeUpload;
use Ekstremedia\LaravelYouTube\Models\YouTubeVideo;
use Ekstremedia\LaravelYouTube\Services\YouTubeService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class UploadController extends Controller
{
    /**
     * YouTube service instance
     */
    protected YouTubeService $youtubeService;

    /**
     * Create a new controller instance
     */
    public function __construct(YouTubeService $youtubeService)
    {
        $this->youtubeService = $youtubeService;
    }

    /**
     * Display the upload interface
     */
    public function index(): View
    {
        // Get active tokens for channel selection
        $tokens = YouTubeToken::where('user_id', Auth::id())
            ->where('is_active', true)
            ->orderBy('created_at', 'desc')
            ->get();

        // Get recent uploads
        $recentUploads = YouTubeVideo::where('user_id', Auth::id())
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get();

        return view('youtube::admin.upload.index', [
            'tokens' => $tokens,
            'recentUploads' => $recentUploads,
            'maxFileSize' => config('youtube.upload.max_file_size', 128000), // in MB
            'defaultPrivacy' => config('youtube.upload.default_privacy', 'private'),
        ]);
    }

    /**
     * Handle the video upload
     */
    public function store(Request $request)
    {
        $request->validate([
            'video' => 'required|file|mimes:mp4,avi,mov,wmv,flv,webm|max:' . (config('youtube.upload.max_file_size', 128000) * 1024),
            'title' => 'required|string|max:100',
            'description' => 'nullable|string|max:5000',
            'tags' => 'nullable|string', // Comma-separated tags
            'privacy_status' => 'nullable|in:private,unlisted,public',
            'category_id' => 'nullable|string',
            'channel_id' => 'nullable|string',
            'playlist_id' => 'nullable|string',
        ]);

        try {
            // Get the token for the selected channel, or use the first active token
            $token = null;
            if ($request->has('channel_id')) {
                $token = YouTubeToken::where('user_id', Auth::id())
                    ->where('channel_id', $request->input('channel_id'))
                    ->where('is_active', true)
                    ->first();
            }

            if (! $token) {
                $token = YouTubeToken::where('user_id', Auth::id())
                    ->where('is_active', true)
                    ->first();
            }

            if (! $token) {
                return redirect()
                    ->back()
                    ->withInput()
                    ->with('error', 'No active YouTube channel connected. Please connect a channel first.');
            }

            // Prepare metadata
            $metadata = [
                'title' => $request->input('title'),
                'description' => $request->input('description', ''),
                'privacy_status' => $request->input('privacy_status', config('youtube.upload.default_privacy', 'private')),
            ];

            // Parse tags
            if ($request->has('tags')) {
                $tags = array_map('trim', explode(',', $request->input('tags')));
                $metadata['tags'] = array_filter($tags);
            }

            if ($request->has('category_id')) {
                $metadata['category_id'] = $request->input('category_id');
            }

            // Prepare options
            $options = [];
            if ($request->has('playlist_id')) {
                $options['playlist_id'] = $request->input('playlist_id');
            }

            // Store the video file temporarily
            $videoFile = $request->file('video');
            $tempPath = $videoFile->store('youtube-uploads', 'local');

            // Create upload record
            $upload = YouTubeUpload::create([
                'user_id' => Auth::id(),
                'token_id' => $token->id,
                'file_path' => $tempPath,
                'file_name' => $videoFile->getClientOriginalName(),
                'file_size' => $videoFile->getSize(),
                'title' => $metadata['title'],
                'description' => $metadata['description'] ?? '',
                'tags' => isset($metadata['tags']) ? json_encode($metadata['tags']) : null,
                'privacy_status' => $metadata['privacy_status'],
                'category_id' => $metadata['category_id'] ?? null,
                'playlist_id' => $options['playlist_id'] ?? null,
                'upload_status' => 'pending',
            ]);

            // Dispatch job to queue
            UploadVideoJob::dispatch($upload)
                ->onQueue(config('youtube.queue.name', 'default'));

            // Return appropriate response
            if ($request->wantsJson()) {
                return response()->json([
                    'success' => true,
                    'job_id' => $upload->id,
                    'message' => 'Video upload started',
                ]);
            }

            return redirect()
                ->route('youtube.admin.videos.index')
                ->with('success', 'Video upload started! Check back in a few minutes.');
        } catch (\Exception $e) {
            if ($request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to upload video: ' . $e->getMessage(),
                ], 422);
            }

            return redirect()
                ->back()
                ->withInput()
                ->with('error', 'Failed to upload video: ' . $e->getMessage());
        }
    }

    /**
     * Get upload progress for a video (AJAX endpoint)
     */
    public function progress(string $id): JsonResponse
    {
        try {
            $upload = YouTubeUpload::where('user_id', Auth::id())
                ->findOrFail($id);

            $response = [
                'id' => $upload->id,
                'status' => $upload->upload_status,
                'message' => $this->getStatusMessage($upload),
                'progress' => $this->calculateProgress($upload),
            ];

            // Add details based on status
            if ($upload->upload_status === 'completed' && $upload->youtube_video_id) {
                $response['video_id'] = $upload->youtube_video_id;
                $response['watch_url'] = "https://www.youtube.com/watch?v={$upload->youtube_video_id}";
                $response['redirect'] = route('youtube.admin.videos.index');
            } elseif ($upload->upload_status === 'failed') {
                $response['error'] = $upload->error_message;
            }

            // Determine overall status for frontend
            $response['status'] = match ($upload->upload_status) {
                'pending' => 'processing',
                'uploading' => 'uploading',
                'processing' => 'uploading',
                'completed' => 'completed',
                'failed' => 'failed',
                default => 'processing',
            };

            return response()->json($response);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'status' => 'failed',
                'message' => 'Failed to get upload progress',
                'error' => config('app.debug') ? $e->getMessage() : 'An error occurred',
            ], 500);
        }
    }

    /**
     * Get status message for upload
     */
    private function getStatusMessage(YouTubeUpload $upload): string
    {
        return match ($upload->upload_status) {
            'pending' => 'Preparing upload...',
            'uploading' => 'Uploading to YouTube...',
            'processing' => 'Processing video...',
            'completed' => 'Upload complete!',
            'failed' => $upload->error_message ?? 'Upload failed',
            default => 'Processing...',
        };
    }

    /**
     * Calculate upload progress percentage
     */
    private function calculateProgress(YouTubeUpload $upload): int
    {
        return match ($upload->upload_status) {
            'pending' => 25,
            'uploading' => 50,
            'processing' => 75,
            'completed' => 100,
            'failed' => 0,
            default => 0,
        };
    }
}
