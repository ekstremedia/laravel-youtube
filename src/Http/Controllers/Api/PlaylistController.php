<?php

namespace Ekstremedia\LaravelYouTube\Http\Controllers\Api;

use Ekstremedia\LaravelYouTube\Http\Controllers\Controller;
use Ekstremedia\LaravelYouTube\Services\YouTubeService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class PlaylistController extends Controller
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
     * Get authenticated user's playlists
     */
    public function index(Request $request): JsonResponse
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

    /**
     * Create a new playlist
     */
    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'title' => 'required|string|max:150',
            'description' => 'sometimes|string|max:5000',
            'privacy_status' => 'sometimes|in:private,unlisted,public',
            'tags' => 'sometimes|array',
        ]);

        try {
            $playlist = $this->youtubeService
                ->forUser(Auth::id())
                ->createPlaylist($request->only([
                    'title', 'description', 'privacy_status', 'tags',
                ]));

            return response()->json([
                'success' => true,
                'data' => $playlist,
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get a specific playlist
     */
    public function show(string $id): JsonResponse
    {
        try {
            // Note: This requires implementing getPlaylist() in YouTubeService
            $service = $this->youtubeService->forUser(Auth::id());

            // For now, we'll get all playlists and filter
            // TODO: Implement getPlaylist() method in YouTubeService for better performance
            $playlists = $service->getPlaylists(['maxResults' => 50]);

            $playlist = collect($playlists['playlists'] ?? [])
                ->firstWhere('id', $id);

            if (! $playlist) {
                return response()->json([
                    'success' => false,
                    'message' => 'Playlist not found',
                ], 404);
            }

            return response()->json([
                'success' => true,
                'data' => $playlist,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Update a playlist
     */
    public function update(Request $request, string $id): JsonResponse
    {
        $request->validate([
            'title' => 'sometimes|string|max:150',
            'description' => 'sometimes|string|max:5000',
            'privacy_status' => 'sometimes|in:private,unlisted,public',
            'tags' => 'sometimes|array',
        ]);

        try {
            // TODO: Implement updatePlaylist() in YouTubeService
            return response()->json([
                'success' => false,
                'message' => 'Playlist update functionality is not yet implemented',
            ], 501);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Delete a playlist
     */
    public function destroy(string $id): JsonResponse
    {
        try {
            // TODO: Implement deletePlaylist() in YouTubeService
            return response()->json([
                'success' => false,
                'message' => 'Playlist deletion functionality is not yet implemented',
            ], 501);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Add a video to a playlist
     */
    public function addVideo(Request $request, string $id): JsonResponse
    {
        $request->validate([
            'video_id' => 'required|string',
            'position' => 'sometimes|integer|min:0',
        ]);

        try {
            $this->youtubeService
                ->forUser(Auth::id())
                ->addToPlaylist(
                    $id,
                    $request->input('video_id'),
                    $request->input('position')
                );

            return response()->json([
                'success' => true,
                'message' => 'Video added to playlist successfully',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Remove a video from a playlist
     */
    public function removeVideo(string $id, string $videoId): JsonResponse
    {
        try {
            // TODO: Implement removeFromPlaylist() in YouTubeService
            return response()->json([
                'success' => false,
                'message' => 'Remove video from playlist functionality is not yet implemented',
            ], 501);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }
}
