<?php

namespace EkstreMedia\LaravelYouTube\Http\Controllers\Api;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use EkstreMedia\LaravelYouTube\Http\Controllers\Controller;
use EkstreMedia\LaravelYouTube\Services\YouTubeService;
use EkstreMedia\LaravelYouTube\Models\YouTubeVideo;

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
        $this->middleware('auth:sanctum');
    }

    /**
     * Upload a video to YouTube
     */
    public function upload(Request $request): JsonResponse
    {
        $request->validate([
            'video' => 'required|file|mimes:mp4,avi,mov,wmv,flv,webm|max:' . (config('youtube.upload.max_file_size') * 1024),
            'title' => 'required|string|max:100',
            'description' => 'sometimes|string|max:5000',
            'tags' => 'sometimes|array',
            'privacy_status' => 'sometimes|in:private,unlisted,public',
            'category_id' => 'sometimes|string',
            'playlist_id' => 'sometimes|string',
        ]);

        try {
            $metadata = [
                'title' => $request->input('title'),
                'description' => $request->input('description', ''),
                'privacy_status' => $request->input('privacy_status', config('youtube.upload.default_privacy', 'private')),
            ];

            if ($request->has('tags')) {
                $metadata['tags'] = $request->input('tags');
            }

            if ($request->has('category_id')) {
                $metadata['category_id'] = $request->input('category_id');
            }

            $options = [];
            if ($request->has('playlist_id')) {
                $options['playlist_id'] = $request->input('playlist_id');
            }

            $video = $this->youtubeService
                ->forUser(Auth::id())
                ->uploadVideo(
                    $request->file('video'),
                    $metadata,
                    $options
                );

            return response()->json([
                'success' => true,
                'message' => 'Video uploaded successfully',
                'data' => [
                    'id' => $video->id,
                    'video_id' => $video->video_id,
                    'title' => $video->title,
                    'status' => $video->upload_status,
                    'privacy_status' => $video->privacy_status,
                ],
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get upload status for a video
     */
    public function status(string $id): JsonResponse
    {
        try {
            // Find the video by ID or video_id
            $video = YouTubeVideo::where('user_id', Auth::id())
                ->where(function ($query) use ($id) {
                    $query->where('id', $id)
                        ->orWhere('video_id', $id);
                })
                ->first();

            if (!$video) {
                return response()->json([
                    'success' => false,
                    'message' => 'Video not found',
                ], 404);
            }

            $statusData = [
                'id' => $video->id,
                'video_id' => $video->video_id,
                'title' => $video->title,
                'upload_status' => $video->upload_status,
                'privacy_status' => $video->privacy_status,
                'processing_status' => $video->processing_details['processingStatus'] ?? null,
                'created_at' => $video->created_at?->toIso8601String(),
                'updated_at' => $video->updated_at?->toIso8601String(),
            ];

            // Add processing details if available
            if ($video->processing_details) {
                $statusData['processing'] = [
                    'status' => $video->processing_details['processingStatus'] ?? 'unknown',
                    'progress' => $video->processing_details['processingProgress'] ?? null,
                    'failure_reason' => $video->processing_details['processingFailureReason'] ?? null,
                ];
            }

            // Add statistics if available
            if ($video->statistics) {
                $statusData['statistics'] = [
                    'views' => $video->statistics['viewCount'] ?? 0,
                    'likes' => $video->statistics['likeCount'] ?? 0,
                    'comments' => $video->statistics['commentCount'] ?? 0,
                ];
            }

            // Add watch URL if video is uploaded
            if ($video->video_id) {
                $statusData['watch_url'] = "https://www.youtube.com/watch?v={$video->video_id}";
                $statusData['embed_url'] = "https://www.youtube.com/embed/{$video->video_id}";
            }

            return response()->json([
                'success' => true,
                'data' => $statusData,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }
}
