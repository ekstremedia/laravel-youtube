<?php

namespace EkstreMedia\LaravelYouTube\Http\Controllers\Admin;

use EkstreMedia\LaravelYouTube\Http\Controllers\Controller;
use EkstreMedia\LaravelYouTube\Models\YouTubeToken;
use EkstreMedia\LaravelYouTube\Models\YouTubeVideo;
use EkstreMedia\LaravelYouTube\Services\YouTubeService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
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
    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'video' => 'required|file|mimes:mp4,avi,mov,wmv,flv,webm|max:' . (config('youtube.upload.max_file_size', 128000) * 1024),
            'title' => 'required|string|max:100',
            'description' => 'sometimes|string|max:5000',
            'tags' => 'sometimes|string', // Comma-separated tags
            'privacy_status' => 'sometimes|in:private,unlisted,public',
            'category_id' => 'sometimes|string',
            'channel_id' => 'sometimes|string',
            'playlist_id' => 'sometimes|string',
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

            // Upload the video
            $video = $this->youtubeService
                ->withToken($token)
                ->uploadVideo(
                    $request->file('video'),
                    $metadata,
                    $options
                );

            return redirect()
                ->route('youtube.admin.videos.show', $video->id)
                ->with('success', 'Video uploaded successfully! It may take a few minutes for YouTube to process it.');
        } catch (\Exception $e) {
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
            $video = YouTubeVideo::where('user_id', Auth::id())
                ->findOrFail($id);

            $progress = [
                'id' => $video->id,
                'video_id' => $video->video_id,
                'title' => $video->title,
                'upload_status' => $video->upload_status,
                'privacy_status' => $video->privacy_status,
                'processing_status' => $video->processing_details['processingStatus'] ?? 'unknown',
                'processing_progress' => $video->processing_details['processingProgress'] ?? null,
                'created_at' => $video->created_at?->toIso8601String(),
                'updated_at' => $video->updated_at?->toIso8601String(),
            ];

            // Add watch URL if available
            if ($video->video_id) {
                $progress['watch_url'] = "https://www.youtube.com/watch?v={$video->video_id}";
                $progress['embed_url'] = "https://www.youtube.com/embed/{$video->video_id}";
            }

            // Determine if processing is complete
            $progress['is_complete'] = in_array(
                $video->processing_details['processingStatus'] ?? '',
                ['succeeded', 'terminated']
            );

            return response()->json([
                'success' => true,
                'data' => $progress,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }
}
