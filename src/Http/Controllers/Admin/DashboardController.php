<?php

namespace EkstreMedia\LaravelYouTube\Http\Controllers\Admin;

use EkstreMedia\LaravelYouTube\Http\Controllers\Controller;
use EkstreMedia\LaravelYouTube\Models\YouTubeVideo;
use EkstreMedia\LaravelYouTube\Services\TokenManager;
use EkstreMedia\LaravelYouTube\Services\YouTubeService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class DashboardController extends Controller
{
    /**
     * Token manager instance
     */
    protected TokenManager $tokenManager;

    /**
     * YouTube service instance
     */
    protected YouTubeService $youtubeService;

    /**
     * Create a new controller instance
     */
    public function __construct(TokenManager $tokenManager, YouTubeService $youtubeService)
    {
        $this->tokenManager = $tokenManager;
        $this->youtubeService = $youtubeService;
    }

    /**
     * Display the admin dashboard
     */
    public function index(Request $request): View
    {
        $userId = Auth::id();

        // Get all active tokens for the user
        $tokens = $this->tokenManager->getUserTokens($userId);

        // Get statistics
        $stats = [
            'total_channels' => $tokens->count(),
            'active_channels' => $tokens->where('is_active', true)->count(),
            'total_videos' => YouTubeVideo::where('user_id', $userId)->count(),
            'public_videos' => YouTubeVideo::where('user_id', $userId)->where('privacy_status', 'public')->count(),
            'private_videos' => YouTubeVideo::where('user_id', $userId)->where('privacy_status', 'private')->count(),
            'unlisted_videos' => YouTubeVideo::where('user_id', $userId)->where('privacy_status', 'unlisted')->count(),
            'total_views' => YouTubeVideo::where('user_id', $userId)->sum('view_count'),
            'total_likes' => YouTubeVideo::where('user_id', $userId)->sum('like_count'),
        ];

        // Get recent videos
        $recentVideos = YouTubeVideo::where('user_id', $userId)
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get();

        // Get channel details for each token
        $channels = [];
        foreach ($tokens as $token) {
            try {
                if ($token->is_active && ! $token->has_error) {
                    $channelData = [
                        'token_id' => $token->id,
                        'id' => $token->channel_id,
                        'title' => $token->channel_title,
                        'handle' => $token->channel_handle,
                        'thumbnail' => $token->channel_thumbnail,
                        'is_active' => $token->is_active,
                        'expires_at' => $token->expires_at,
                        'last_refreshed' => $token->last_refreshed_at,
                        'metadata' => $token->channel_metadata,
                    ];

                    // Try to get fresh channel stats if token is valid
                    if (! $this->tokenManager->needsRefresh($token)) {
                        try {
                            $freshData = $this->youtubeService
                                ->withToken($token)
                                ->getChannel(['statistics']);

                            $channelData['stats'] = [
                                'view_count' => $freshData['view_count'] ?? 0,
                                'subscriber_count' => $freshData['subscriber_count'] ?? 0,
                                'video_count' => $freshData['video_count'] ?? 0,
                            ];
                        } catch (\Exception $e) {
                            // Use cached data if available
                            $channelData['stats'] = $token->channel_metadata['stats'] ?? null;
                        }
                    } else {
                        // Use cached data for expiring tokens
                        $channelData['stats'] = $token->channel_metadata['stats'] ?? null;
                    }

                    $channels[] = $channelData;
                }
            } catch (\Exception $e) {
                // Skip channels with errors
                continue;
            }
        }

        // Get upload activity for chart (last 30 days)
        $uploadActivity = YouTubeVideo::where('user_id', $userId)
            ->where('created_at', '>=', now()->subDays(30))
            ->selectRaw('DATE(created_at) as date, COUNT(*) as count')
            ->groupBy('date')
            ->orderBy('date')
            ->get()
            ->pluck('count', 'date')
            ->toArray();

        // Fill missing dates with 0
        $chartData = [];
        for ($i = 29; $i >= 0; $i--) {
            $date = now()->subDays($i)->format('Y-m-d');
            $chartData[$date] = $uploadActivity[$date] ?? 0;
        }

        // Check if OAuth credentials are configured
        $clientId = config('youtube.credentials.client_id');
        $clientSecret = config('youtube.credentials.client_secret');
        $isConfigured = ! empty($clientId) && ! empty($clientSecret);

        return view('youtube::admin.dashboard', [
            'channels' => $channels,
            'stats' => $stats,
            'recentVideos' => $recentVideos,
            'chartData' => $chartData,
            'tokens' => $tokens,
            'isConfigured' => $isConfigured,
            'configurationWarning' => ! $isConfigured ? 'YouTube OAuth credentials are not configured. Please set YOUTUBE_CLIENT_ID and YOUTUBE_CLIENT_SECRET in your .env file.' : null,
        ]);
    }
}
