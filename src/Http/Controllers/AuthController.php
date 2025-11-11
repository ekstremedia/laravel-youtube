<?php

namespace Ekstremedia\LaravelYouTube\Http\Controllers;

use Ekstremedia\LaravelYouTube\Models\YouTubeToken;
use Ekstremedia\LaravelYouTube\Services\AuthService;
use Ekstremedia\LaravelYouTube\Services\TokenManager;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Session;

class AuthController extends Controller
{
    /**
     * Auth service instance
     */
    protected AuthService $authService;

    /**
     * Token manager instance
     */
    protected TokenManager $tokenManager;

    /**
     * Create a new controller instance
     */
    public function __construct(AuthService $authService, TokenManager $tokenManager)
    {
        $this->authService = $authService;
        $this->tokenManager = $tokenManager;
    }

    /**
     * Show the authorization page (simplified for single user)
     */
    public function index(Request $request)
    {
        // Check if credentials are configured
        $clientId = config('youtube.credentials.client_id');
        $clientSecret = config('youtube.credentials.client_secret');
        $credentialsConfigured = ! empty($clientId) && ! empty($clientSecret);

        // Get the most recent active token (single-user mode)
        $token = YouTubeToken::where('is_active', true)
            ->orderBy('last_refreshed_at', 'desc')
            ->first();

        // Try to refresh token if it's expiring
        if ($token && $this->tokenManager->needsRefresh($token)) {
            try {
                $newTokenData = $this->authService->refreshAccessToken(
                    $this->tokenManager->getRefreshToken($token)
                );
                $this->tokenManager->updateToken($token, $newTokenData);
                $token->refresh(); // Reload from database
            } catch (\Exception $e) {
                Log::warning('Failed to refresh token on auth page', [
                    'error' => $e->getMessage(),
                ]);
            }
        }

        return view('youtube::auth.authorize', [
            'credentialsConfigured' => $credentialsConfigured,
            'hasToken' => $token !== null,
            'token' => $token,
        ]);
    }

    /**
     * Redirect user to YouTube OAuth authorization
     */
    public function redirect(Request $request): RedirectResponse
    {
        // Validate OAuth credentials are configured
        $clientId = config('youtube.credentials.client_id');
        $clientSecret = config('youtube.credentials.client_secret');

        if (empty($clientId) || empty($clientSecret)) {
            Log::error('YouTube OAuth credentials not configured', [
                'user_id' => Auth::id(),
            ]);

            return redirect()->back()->with(
                'error',
                'YouTube integration is not configured. Please contact the administrator to set up YOUTUBE_CLIENT_ID and YOUTUBE_CLIENT_SECRET in the environment configuration.'
            );
        }

        // Store return URL in session for after authentication
        if ($request->has('return_url')) {
            Session::put('youtube_return_url', $request->input('return_url'));
        }

        // Store channel ID if switching channels
        if ($request->has('channel_id')) {
            Session::put('youtube_target_channel', $request->input('channel_id'));
        }

        // Get authorization URL with state for CSRF protection
        $authUrl = $this->authService->getAuthUrl();

        Log::info('Redirecting user to YouTube OAuth', [
            'user_id' => Auth::id(),
            'return_url' => Session::get('youtube_return_url'),
        ]);

        return redirect()->away($authUrl);
    }

    /**
     * Handle OAuth callback from YouTube
     */
    public function callback(Request $request): RedirectResponse
    {
        // Security check: Verify that an OAuth flow was initiated by checking session
        if (! Session::has('youtube_oauth_state')) {
            Log::warning('YouTube OAuth callback without session state', [
                'ip' => $request->ip(),
                'user_agent' => $request->userAgent(),
            ]);

            return redirect()->route('youtube.authorize')
                ->with('error', 'OAuth session expired or invalid. Please try again.');
        }

        // Check for errors
        if ($request->has('error')) {
            Log::error('YouTube OAuth error', [
                'error' => $request->input('error'),
                'error_description' => $request->input('error_description'),
            ]);

            return redirect()->route('youtube.authorize')
                ->with('error', 'Authentication failed: ' . $request->input('error_description', 'Unknown error'));
        }

        // Verify we have authorization code
        if (! $request->has('code')) {
            return redirect()->route('youtube.authorize')
                ->with('error', 'No authorization code received');
        }

        try {
            // Exchange authorization code for tokens
            $tokenData = $this->authService->exchangeCode(
                $request->input('code'),
                $request->input('state')
            );

            // Get channel information
            $channelInfo = $this->authService->getChannelInfo($tokenData['access_token']);

            // Store token in database (null user_id for single-user mode)
            $token = $this->tokenManager->storeToken($tokenData, $channelInfo, null);

            Log::info('YouTube authentication successful', [
                'channel_id' => $channelInfo['id'],
                'channel_title' => $channelInfo['title'],
            ]);

            // Clear session data
            Session::forget('youtube_target_channel');

            return redirect()->route('youtube.authorize')
                ->with('success', 'Successfully connected YouTube channel: ' . $channelInfo['title']);
        } catch (\Exception $e) {
            Log::error('YouTube OAuth callback error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return redirect()->route('youtube.authorize')
                ->with('error', 'Failed to authenticate with YouTube: ' . $e->getMessage());
        }
    }

    /**
     * Revoke YouTube access
     *
     * @return RedirectResponse|JsonResponse
     */
    public function revoke(Request $request)
    {
        $tokenId = $request->input('token_id');

        try {
            // Find the token
            $token = YouTubeToken::findOrFail($tokenId);

            // Authorization check: In multi-user mode, only token owner can revoke
            // In single-user mode (user_id = null), any authenticated user can revoke
            if ($token->user_id !== null && $token->user_id !== Auth::id()) {
                abort(403, 'Unauthorized to revoke this token');
            }

            // Revoke token with YouTube
            $accessToken = $this->tokenManager->getAccessToken($token);
            $this->authService->revokeToken($accessToken);

            // Deactivate token in database
            $this->tokenManager->deactivateToken($token);

            Log::info('YouTube token revoked', [
                'token_id' => $tokenId,
                'channel_id' => $token->channel_id,
            ]);

            $message = 'Successfully disconnected YouTube channel: ' . $token->channel_title;

            if ($request->wantsJson()) {
                return response()->json(['success' => true, 'message' => $message]);
            }

            return redirect()->back()->with('success', $message);
        } catch (\Exception $e) {
            Log::error('Failed to revoke YouTube token', [
                'error' => $e->getMessage(),
                'token_id' => $tokenId,
            ]);

            $message = 'Failed to disconnect YouTube channel';

            if ($request->wantsJson()) {
                return response()->json(['success' => false, 'message' => $message], 500);
            }

            return redirect()->back()->with('error', $message);
        }
    }

    /**
     * Check authentication status
     */
    public function status(Request $request): JsonResponse
    {
        $userId = Auth::id();
        $channelId = $request->input('channel_id');

        try {
            $token = $this->tokenManager->getActiveToken($userId, $channelId);

            if (! $token) {
                return response()->json([
                    'authenticated' => false,
                    'message' => 'No active YouTube connection found',
                ]);
            }

            // Check if token needs refresh
            $needsRefresh = $this->tokenManager->needsRefresh($token);

            return response()->json([
                'authenticated' => true,
                'channel' => [
                    'id' => $token->channel_id,
                    'title' => $token->channel_title,
                    'handle' => $token->channel_handle,
                    'thumbnail' => $token->channel_thumbnail,
                ],
                'expires_at' => $token->expires_at->toIso8601String(),
                'expires_in_minutes' => $token->expires_in_minutes,
                'needs_refresh' => $needsRefresh,
                'is_active' => $token->is_active,
                'has_error' => $token->has_error,
                'error' => $token->error,
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to check YouTube authentication status', [
                'error' => $e->getMessage(),
                'user_id' => $userId,
            ]);

            return response()->json([
                'authenticated' => false,
                'message' => 'Failed to check authentication status',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
