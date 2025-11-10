<?php

namespace Ekstremedia\LaravelYouTube\Http\Controllers\Admin;

use Ekstremedia\LaravelYouTube\Http\Controllers\Controller;
use Ekstremedia\LaravelYouTube\Models\YouTubeToken;
use Ekstremedia\LaravelYouTube\Services\YouTubeService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class PlaylistsController extends Controller
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
     * Display a listing of playlists
     */
    public function index(Request $request): View
    {
        $tokens = YouTubeToken::where('user_id', Auth::id())
            ->where('is_active', true)
            ->get();

        $allPlaylists = [];

        foreach ($tokens as $token) {
            try {
                $response = $this->youtubeService
                    ->withToken($token)
                    ->getPlaylists(['maxResults' => 50]);

                foreach ($response['playlists'] as $playlist) {
                    $playlist['channel_title'] = $token->channel_title;
                    $playlist['channel_id'] = $token->channel_id;
                    $allPlaylists[] = $playlist;
                }
            } catch (\Exception $e) {
                // Skip failed channel loads
                continue;
            }
        }

        return view('youtube::admin.playlists.index', [
            'playlists' => $allPlaylists,
            'tokens' => $tokens,
        ]);
    }

    /**
     * Show the form for creating a new playlist
     */
    public function create(): View
    {
        $tokens = YouTubeToken::where('user_id', Auth::id())
            ->where('is_active', true)
            ->get();

        return view('youtube::admin.playlists.create', [
            'tokens' => $tokens,
        ]);
    }

    /**
     * Store a newly created playlist
     */
    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'title' => 'required|string|max:150',
            'description' => 'sometimes|string|max:5000',
            'privacy_status' => 'sometimes|in:private,unlisted,public',
            'tags' => 'sometimes|string', // Comma-separated tags
            'channel_id' => 'sometimes|string',
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
                    ->with('error', 'No active YouTube channel connected.');
            }

            // Prepare data
            $data = [
                'title' => $request->input('title'),
                'description' => $request->input('description', ''),
                'privacy_status' => $request->input('privacy_status', 'private'),
            ];

            // Parse tags
            if ($request->has('tags')) {
                $tags = array_map('trim', explode(',', $request->input('tags')));
                $data['tags'] = array_filter($tags);
            }

            // Create the playlist
            $playlist = $this->youtubeService
                ->withToken($token)
                ->createPlaylist($data);

            return redirect()
                ->route('youtube.admin.playlists.show', $playlist['id'])
                ->with('success', 'Playlist created successfully');
        } catch (\Exception $e) {
            return redirect()
                ->back()
                ->withInput()
                ->with('error', 'Failed to create playlist: ' . $e->getMessage());
        }
    }

    /**
     * Display a specific playlist
     */
    public function show(string $id): View
    {
        $tokens = YouTubeToken::where('user_id', Auth::id())
            ->where('is_active', true)
            ->get();

        $playlist = null;
        $token = null;
        $videos = [];

        // Find which token owns this playlist
        foreach ($tokens as $t) {
            try {
                $playlists = $this->youtubeService
                    ->withToken($t)
                    ->getPlaylists(['maxResults' => 50]);

                $found = collect($playlists['playlists'] ?? [])
                    ->firstWhere('id', $id);

                if ($found) {
                    $playlist = $found;
                    $token = $t;

                    // Get playlist videos
                    // TODO: Implement getPlaylistVideos() in YouTubeService
                    $videos = [];
                    break;
                }
            } catch (\Exception $e) {
                continue;
            }
        }

        if (! $playlist) {
            abort(404, 'Playlist not found');
        }

        return view('youtube::admin.playlists.show', [
            'playlist' => $playlist,
            'videos' => $videos,
            'token' => $token,
        ]);
    }

    /**
     * Show the form for editing a playlist
     */
    public function edit(string $id): View
    {
        $tokens = YouTubeToken::where('user_id', Auth::id())
            ->where('is_active', true)
            ->get();

        $playlist = null;
        $token = null;

        // Find which token owns this playlist
        foreach ($tokens as $t) {
            try {
                $playlists = $this->youtubeService
                    ->withToken($t)
                    ->getPlaylists(['maxResults' => 50]);

                $found = collect($playlists['playlists'] ?? [])
                    ->firstWhere('id', $id);

                if ($found) {
                    $playlist = $found;
                    $token = $t;
                    break;
                }
            } catch (\Exception $e) {
                continue;
            }
        }

        if (! $playlist) {
            abort(404, 'Playlist not found');
        }

        return view('youtube::admin.playlists.edit', [
            'playlist' => $playlist,
            'token' => $token,
        ]);
    }

    /**
     * Update a playlist
     */
    public function update(Request $request, string $id): RedirectResponse
    {
        $request->validate([
            'title' => 'sometimes|string|max:150',
            'description' => 'sometimes|string|max:5000',
            'privacy_status' => 'sometimes|in:private,unlisted,public',
            'tags' => 'sometimes|string', // Comma-separated tags
        ]);

        try {
            // TODO: Implement updatePlaylist() in YouTubeService
            return redirect()
                ->back()
                ->withInput()
                ->with('error', 'Playlist update functionality is not yet implemented');
        } catch (\Exception $e) {
            return redirect()
                ->back()
                ->withInput()
                ->with('error', 'Failed to update playlist: ' . $e->getMessage());
        }
    }

    /**
     * Delete a playlist
     */
    public function destroy(string $id): RedirectResponse
    {
        try {
            // TODO: Implement deletePlaylist() in YouTubeService
            return redirect()
                ->back()
                ->with('error', 'Playlist deletion functionality is not yet implemented');
        } catch (\Exception $e) {
            return redirect()
                ->back()
                ->with('error', 'Failed to delete playlist: ' . $e->getMessage());
        }
    }
}
