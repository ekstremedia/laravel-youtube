<?php

namespace EkstreMedia\LaravelYouTube\Http\Controllers\Admin;

use EkstreMedia\LaravelYouTube\Http\Controllers\Controller;
use EkstreMedia\LaravelYouTube\Models\YouTubeVideo;
use EkstreMedia\LaravelYouTube\Services\YouTubeService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class VideosController extends Controller
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
     * Display a listing of videos
     */
    public function index(Request $request): View
    {
        $query = YouTubeVideo::where('user_id', Auth::id())
            ->with('token');

        // Search filter
        if ($request->has('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%")
                    ->orWhere('video_id', 'like', "%{$search}%");
            });
        }

        // Privacy filter
        if ($request->has('privacy')) {
            $query->where('privacy_status', $request->input('privacy'));
        }

        // Upload status filter
        if ($request->has('status')) {
            $query->where('upload_status', $request->input('status'));
        }

        // Sort
        $sortBy = $request->input('sort', 'created_at');
        $sortDirection = $request->input('direction', 'desc');
        $query->orderBy($sortBy, $sortDirection);

        $videos = $query->paginate(20);

        return view('youtube::admin.videos.index', [
            'videos' => $videos,
            'filters' => $request->only(['search', 'privacy', 'status', 'sort', 'direction']),
        ]);
    }

    /**
     * Display a specific video
     */
    public function show(string $id): View
    {
        $video = YouTubeVideo::where('user_id', Auth::id())
            ->with('token')
            ->findOrFail($id);

        try {
            // Get fresh data from YouTube
            $youtubeData = $this->youtubeService
                ->withToken($video->token)
                ->getVideo($video->video_id);

            return view('youtube::admin.videos.show', [
                'video' => $video,
                'youtubeData' => $youtubeData,
            ]);
        } catch (\Exception $e) {
            return view('youtube::admin.videos.show', [
                'video' => $video,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Show the form for editing a video
     */
    public function edit(string $id): View
    {
        $video = YouTubeVideo::where('user_id', Auth::id())
            ->with('token')
            ->findOrFail($id);

        return view('youtube::admin.videos.edit', [
            'video' => $video,
        ]);
    }

    /**
     * Update a video
     */
    public function update(Request $request, string $id): RedirectResponse
    {
        $video = YouTubeVideo::where('user_id', Auth::id())
            ->with('token')
            ->findOrFail($id);

        $request->validate([
            'title' => 'sometimes|string|max:100',
            'description' => 'sometimes|string|max:5000',
            'tags' => 'sometimes|array',
            'privacy_status' => 'sometimes|in:private,unlisted,public',
            'category_id' => 'sometimes|string',
        ]);

        try {
            $updatedData = $this->youtubeService
                ->withToken($video->token)
                ->updateVideo($video->video_id, $request->only([
                    'title', 'description', 'tags', 'privacy_status', 'category_id',
                ]));

            // Update local record
            $video->update([
                'title' => $updatedData['title'] ?? $video->title,
                'description' => $updatedData['description'] ?? $video->description,
                'privacy_status' => $updatedData['privacyStatus'] ?? $video->privacy_status,
                'tags' => $updatedData['tags'] ?? $video->tags,
            ]);

            return redirect()
                ->route('youtube.admin.videos.show', $video->id)
                ->with('success', 'Video updated successfully');
        } catch (\Exception $e) {
            return redirect()
                ->back()
                ->withInput()
                ->with('error', 'Failed to update video: ' . $e->getMessage());
        }
    }

    /**
     * Delete a video
     */
    public function destroy(string $id): RedirectResponse
    {
        $video = YouTubeVideo::where('user_id', Auth::id())
            ->with('token')
            ->findOrFail($id);

        try {
            // Delete from YouTube
            $this->youtubeService
                ->withToken($video->token)
                ->deleteVideo($video->video_id);

            // Delete local record
            $video->delete();

            return redirect()
                ->route('youtube.admin.videos.index')
                ->with('success', 'Video deleted successfully');
        } catch (\Exception $e) {
            return redirect()
                ->back()
                ->with('error', 'Failed to delete video: ' . $e->getMessage());
        }
    }

    /**
     * Sync video data from YouTube
     */
    public function sync(string $id): RedirectResponse
    {
        $video = YouTubeVideo::where('user_id', Auth::id())
            ->with('token')
            ->findOrFail($id);

        try {
            // Get fresh data from YouTube
            $youtubeData = $this->youtubeService
                ->withToken($video->token)
                ->getVideo($video->video_id);

            // Update local record
            $video->update([
                'title' => $youtubeData['title'] ?? $video->title,
                'description' => $youtubeData['description'] ?? $video->description,
                'privacy_status' => $youtubeData['privacyStatus'] ?? $video->privacy_status,
                'tags' => $youtubeData['tags'] ?? $video->tags,
                'statistics' => $youtubeData['statistics'] ?? $video->statistics,
                'processing_details' => $youtubeData['processingDetails'] ?? $video->processing_details,
            ]);

            return redirect()
                ->back()
                ->with('success', 'Video synced successfully');
        } catch (\Exception $e) {
            return redirect()
                ->back()
                ->with('error', 'Failed to sync video: ' . $e->getMessage());
        }
    }
}
