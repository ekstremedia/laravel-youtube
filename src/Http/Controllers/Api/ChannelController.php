<?php

namespace EkstreMedia\LaravelYouTube\Http\Controllers\Api;

use EkstreMedia\LaravelYouTube\Http\Controllers\Controller;
use EkstreMedia\LaravelYouTube\Services\YouTubeService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ChannelController extends Controller
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
     * Get authenticated user's channel information
     */
    public function show(Request $request): JsonResponse
    {
        try {
            $channel = $this->youtubeService
                ->forUser(Auth::id())
                ->getChannel();

            return response()->json([
                'success' => true,
                'data' => $channel,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get channel videos
     */
    public function videos(Request $request): JsonResponse
    {
        try {
            $videos = $this->youtubeService
                ->forUser(Auth::id())
                ->getVideos($request->only(['maxResults', 'pageToken', 'order']));

            return response()->json([
                'success' => true,
                'data' => $videos,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get channel playlists
     */
    public function playlists(Request $request): JsonResponse
    {
        try {
            $playlists = $this->youtubeService
                ->forUser(Auth::id())
                ->getPlaylists($request->only(['maxResults', 'pageToken']));

            return response()->json([
                'success' => true,
                'data' => $playlists,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }
}
