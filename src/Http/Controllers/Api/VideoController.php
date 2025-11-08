<?php

namespace EkstreMedia\LaravelYouTube\Http\Controllers\Api;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use EkstreMedia\LaravelYouTube\Http\Controllers\Controller;
use EkstreMedia\LaravelYouTube\Services\YouTubeService;
use EkstreMedia\LaravelYouTube\Models\YouTubeVideo;

class VideoController extends Controller
{
    protected YouTubeService $youtubeService;

    public function __construct(YouTubeService $youtubeService)
    {
        $this->youtubeService = $youtubeService;
        $this->middleware('auth:sanctum');
    }

    public function index(Request $request): JsonResponse
    {
        $videos = YouTubeVideo::where('user_id', Auth::id())
            ->orderBy('created_at', 'desc')
            ->paginate($request->get('per_page', 15));

        return response()->json($videos);
    }

    public function show(string $id): JsonResponse
    {
        try {
            $video = $this->youtubeService
                ->forUser(Auth::id())
                ->getVideo($id);

            return response()->json([
                'success' => true,
                'data' => $video,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    public function update(Request $request, string $id): JsonResponse
    {
        $request->validate([
            'title' => 'sometimes|string|max:100',
            'description' => 'sometimes|string|max:5000',
            'tags' => 'sometimes|array',
            'privacy_status' => 'sometimes|in:private,unlisted,public',
        ]);

        try {
            $video = $this->youtubeService
                ->forUser(Auth::id())
                ->updateVideo($id, $request->only([
                    'title', 'description', 'tags', 'privacy_status', 'category_id'
                ]));

            return response()->json([
                'success' => true,
                'data' => $video,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    public function destroy(string $id): JsonResponse
    {
        try {
            $this->youtubeService
                ->forUser(Auth::id())
                ->deleteVideo($id);

            return response()->json([
                'success' => true,
                'message' => 'Video deleted successfully',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    public function updateThumbnail(Request $request, string $id): JsonResponse
    {
        $request->validate([
            'thumbnail' => 'required|image|max:2048',
        ]);

        try {
            $this->youtubeService
                ->forUser(Auth::id())
                ->setThumbnail($id, $request->file('thumbnail'));

            return response()->json([
                'success' => true,
                'message' => 'Thumbnail updated successfully',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }
}