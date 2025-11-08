<?php

namespace EkstreMedia\LaravelYouTube\Http\Controllers\Admin;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;
use Illuminate\Http\RedirectResponse;
use EkstreMedia\LaravelYouTube\Http\Controllers\Controller;
use EkstreMedia\LaravelYouTube\Services\YouTubeService;
use EkstreMedia\LaravelYouTube\Models\YouTubeToken;

class ChannelsController extends Controller
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
     * Display a listing of connected channels
     */
    public function index(): View
    {
        $tokens = YouTubeToken::where('user_id', Auth::id())
            ->where('is_active', true)
            ->with('user')
            ->orderBy('created_at', 'desc')
            ->get();

        $channels = [];
        foreach ($tokens as $token) {
            try {
                $channelData = $this->youtubeService
                    ->withToken($token)
                    ->getChannel(['snippet', 'statistics', 'contentDetails']);

                $channels[] = array_merge($channelData, [
                    'token_id' => $token->id,
                    'expires_at' => $token->expires_at,
                    'is_expired' => $token->is_expired,
                    'expires_soon' => $token->expires_soon,
                ]);
            } catch (\Exception $e) {
                // Skip failed channel loads
                continue;
            }
        }

        return view('youtube::admin.channels.index', [
            'channels' => $channels,
            'tokens' => $tokens,
        ]);
    }

    /**
     * Display a specific channel
     */
    public function show(string $id): View
    {
        $token = YouTubeToken::where('user_id', Auth::id())
            ->where('channel_id', $id)
            ->firstOrFail();

        try {
            $channel = $this->youtubeService
                ->withToken($token)
                ->getChannel(['snippet', 'statistics', 'contentDetails', 'brandingSettings']);

            $videos = $this->youtubeService
                ->withToken($token)
                ->getVideos(['maxResults' => 10, 'order' => 'date']);

            $playlists = $this->youtubeService
                ->withToken($token)
                ->getPlaylists(['maxResults' => 10]);

            return view('youtube::admin.channels.show', [
                'channel' => $channel,
                'videos' => $videos['videos'] ?? [],
                'playlists' => $playlists['playlists'] ?? [],
                'token' => $token,
            ]);
        } catch (\Exception $e) {
            return view('youtube::admin.channels.show', [
                'error' => $e->getMessage(),
                'token' => $token,
            ]);
        }
    }

    /**
     * Sync channel data from YouTube
     */
    public function sync(Request $request): RedirectResponse
    {
        $request->validate([
            'channel_id' => 'required|string',
        ]);

        try {
            $token = YouTubeToken::where('user_id', Auth::id())
                ->where('channel_id', $request->input('channel_id'))
                ->firstOrFail();

            // Refresh channel data
            $channel = $this->youtubeService
                ->withToken($token)
                ->getChannel(['snippet', 'statistics', 'contentDetails']);

            // Update token with latest channel info
            $token->update([
                'channel_title' => $channel['title'] ?? $token->channel_title,
                'channel_thumbnail' => $channel['thumbnails']['default']['url'] ?? $token->channel_thumbnail,
            ]);

            return redirect()
                ->back()
                ->with('success', 'Channel data synced successfully');
        } catch (\Exception $e) {
            return redirect()
                ->back()
                ->with('error', 'Failed to sync channel: ' . $e->getMessage());
        }
    }
}
