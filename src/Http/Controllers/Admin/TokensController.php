<?php

namespace EkstreMedia\LaravelYouTube\Http\Controllers\Admin;

use EkstreMedia\LaravelYouTube\Http\Controllers\Controller;
use EkstreMedia\LaravelYouTube\Models\YouTubeToken;
use EkstreMedia\LaravelYouTube\Services\AuthService;
use EkstreMedia\LaravelYouTube\Services\TokenManager;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class TokensController extends Controller
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
     * Display a listing of tokens
     */
    public function index(): View
    {
        $tokens = YouTubeToken::where('user_id', Auth::id())
            ->orderBy('is_active', 'desc')
            ->orderBy('created_at', 'desc')
            ->get();

        return view('youtube::admin.tokens.index', [
            'tokens' => $tokens,
        ]);
    }

    /**
     * Delete a token
     */
    public function destroy(string $id): RedirectResponse
    {
        try {
            $token = YouTubeToken::where('user_id', Auth::id())
                ->findOrFail($id);

            // Revoke the token with Google
            try {
                $this->authService->revokeToken($token->access_token);
            } catch (\Exception $e) {
                // Continue even if revocation fails
            }

            // Delete the token
            $token->delete();

            return redirect()
                ->route('youtube.admin.tokens.index')
                ->with('success', 'Token deleted successfully');
        } catch (\Exception $e) {
            return redirect()
                ->back()
                ->with('error', 'Failed to delete token: ' . $e->getMessage());
        }
    }

    /**
     * Refresh a token
     */
    public function refresh(string $id): RedirectResponse
    {
        try {
            $token = YouTubeToken::where('user_id', Auth::id())
                ->findOrFail($id);

            // Refresh the token
            $newTokenData = $this->authService->refreshAccessToken($token->refresh_token);

            // Update the token
            $this->tokenManager->updateToken($token, $newTokenData);

            return redirect()
                ->back()
                ->with('success', 'Token refreshed successfully');
        } catch (\Exception $e) {
            return redirect()
                ->back()
                ->with('error', 'Failed to refresh token: ' . $e->getMessage());
        }
    }

    /**
     * Activate a token
     */
    public function activate(string $id): RedirectResponse
    {
        try {
            $token = YouTubeToken::where('user_id', Auth::id())
                ->findOrFail($id);

            // Check if token is expired
            if ($token->is_expired) {
                // Try to refresh it first
                try {
                    $newTokenData = $this->authService->refreshAccessToken($token->refresh_token);
                    $this->tokenManager->updateToken($token, $newTokenData);
                } catch (\Exception $e) {
                    return redirect()
                        ->back()
                        ->with('error', 'Cannot activate expired token. Please refresh it first.');
                }
            }

            // Activate the token
            $token->activate();

            return redirect()
                ->back()
                ->with('success', 'Token activated successfully');
        } catch (\Exception $e) {
            return redirect()
                ->back()
                ->with('error', 'Failed to activate token: ' . $e->getMessage());
        }
    }

    /**
     * Deactivate a token
     */
    public function deactivate(string $id): RedirectResponse
    {
        try {
            $token = YouTubeToken::where('user_id', Auth::id())
                ->findOrFail($id);

            // Deactivate the token
            $token->deactivate();

            return redirect()
                ->back()
                ->with('success', 'Token deactivated successfully');
        } catch (\Exception $e) {
            return redirect()
                ->back()
                ->with('error', 'Failed to deactivate token: ' . $e->getMessage());
        }
    }
}
