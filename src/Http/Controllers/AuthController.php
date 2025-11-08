<?php

namespace EkstreMedia\LaravelYouTube\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Log;
use EkstreMedia\LaravelYouTube\Services\AuthService;
use EkstreMedia\LaravelYouTube\Services\TokenManager;
use EkstreMedia\LaravelYouTube\Models\YouTubeToken;

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
     *
     * @param AuthService $authService
     * @param TokenManager $tokenManager
     */
    public function __construct(AuthService $authService, TokenManager $tokenManager)
    {
        $this->authService = $authService;
        $this->tokenManager = $tokenManager;
    }

    /**
     * Redirect user to YouTube OAuth authorization
     *
     * @param Request $request
     * @return RedirectResponse
     */
    public function redirect(Request $request): RedirectResponse
    {
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
     *
     * @param Request $request
     * @return RedirectResponse
     */
    public function callback(Request $request): RedirectResponse
    {
        // Check for errors
        if ($request->has('error')) {
            Log::error('YouTube OAuth error', [
                'error' => $request->input('error'),
                'error_description' => $request->input('error_description'),
            ]);

            return redirect()->route('youtube.admin.dashboard')
                ->with('error', 'Authentication failed: ' . $request->input('error_description', 'Unknown error'));
        }

        // Verify we have authorization code
        if (!$request->has('code')) {
            return redirect()->route('youtube.admin.dashboard')
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

            // Store token in database
            $userId = Auth::id();
            $token = $this->tokenManager->storeToken($tokenData, $channelInfo, $userId);

            Log::info('YouTube authentication successful', [
                'user_id' => $userId,
                'channel_id' => $channelInfo['id'],
                'channel_title' => $channelInfo['title'],
            ]);

            // Clear session data
            $returnUrl = Session::pull('youtube_return_url', route('youtube.admin.dashboard'));
            Session::forget('youtube_target_channel');

            return redirect($returnUrl)
                ->with('success', 'Successfully connected YouTube channel: ' . $channelInfo['title']);
        } catch (\Exception $e) {
            Log::error('YouTube OAuth callback error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return redirect()->route('youtube.admin.dashboard')
                ->with('error', 'Failed to authenticate with YouTube: ' . $e->getMessage());
        }
    }

    /**
     * Revoke YouTube access
     *
     * @param Request $request
     * @return RedirectResponse|JsonResponse
     */
    public function revoke(Request $request)
    {
        $tokenId = $request->input('token_id');
        $userId = Auth::id();

        try {
            // Find the token
            $token = YouTubeToken::where('id', $tokenId)
                ->where('user_id', $userId)
                ->firstOrFail();

            // Revoke token with YouTube
            $accessToken = $this->tokenManager->getAccessToken($token);
            $this->authService->revokeToken($accessToken);

            // Deactivate token in database
            $this->tokenManager->deactivateToken($token);

            Log::info('YouTube token revoked', [
                'user_id' => $userId,
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
                'user_id' => $userId,
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
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function status(Request $request): JsonResponse
    {
        $userId = Auth::id();
        $channelId = $request->input('channel_id');

        try {
            $token = $this->tokenManager->getActiveToken($userId, $channelId);

            if (!$token) {
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