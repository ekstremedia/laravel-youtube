<?php

namespace Ekstremedia\LaravelYouTube\Services;

use Ekstremedia\LaravelYouTube\Exceptions\YouTubeAuthException;
use Google_Client;
use Google_Service_Oauth2;
use Google_Service_YouTube;
use Illuminate\Support\Facades\Session;

class AuthService
{
    /**
     * Google Client instance
     */
    protected Google_Client $client;

    /**
     * OAuth2 credentials configuration
     */
    protected array $credentials;

    /**
     * API scopes
     */
    protected array $scopes;

    /**
     * Create a new AuthService instance
     *
     * @param  array  $credentials  OAuth2 credentials
     * @param  array  $scopes  API scopes
     */
    public function __construct(array $credentials, array $scopes)
    {
        $this->credentials = $credentials;
        $this->scopes = $scopes;
        $this->initializeClient();
    }

    /**
     * Initialize the Google Client
     */
    protected function initializeClient(): void
    {
        $this->client = new Google_Client;

        // Set OAuth2 credentials
        $this->client->setClientId($this->credentials['client_id']);
        $this->client->setClientSecret($this->credentials['client_secret']);
        $this->client->setRedirectUri($this->buildRedirectUri());

        // Set API scopes
        $this->client->setScopes($this->scopes);

        // Set access type to offline to get refresh token
        $this->client->setAccessType('offline');

        // Force approval prompt to ensure we get refresh token
        $this->client->setApprovalPrompt('force');

        // Set include granted scopes to incremental auth
        $this->client->setIncludeGrantedScopes(true);
    }

    /**
     * Build the redirect URI
     */
    protected function buildRedirectUri(): string
    {
        $redirectPath = $this->credentials['redirect_uri'] ?? '/youtube/callback';

        // If it's already a full URL, return as is
        if (filter_var($redirectPath, FILTER_VALIDATE_URL)) {
            return $redirectPath;
        }

        // Otherwise, build full URL
        return url($redirectPath);
    }

    /**
     * Get the authorization URL
     *
     * @param  string|null  $state  Optional state parameter
     */
    public function getAuthUrl(?string $state = null): string
    {
        if ($state) {
            $this->client->setState($state);
        } else {
            // Generate a random state for CSRF protection
            $state = bin2hex(random_bytes(16));
            $this->client->setState($state);
            Session::put('youtube_oauth_state', $state);
        }

        return $this->client->createAuthUrl();
    }

    /**
     * Exchange authorization code for access token
     *
     * @param  string  $code  Authorization code
     * @param  string|null  $state  State parameter for CSRF protection
     * @return array Token data
     *
     * @throws YouTubeAuthException
     */
    public function exchangeCode(string $code, ?string $state = null): array
    {
        // Verify state for CSRF protection
        if ($state) {
            $sessionState = Session::get('youtube_oauth_state');
            if ($state !== $sessionState) {
                throw new YouTubeAuthException('Invalid state parameter');
            }
            Session::forget('youtube_oauth_state');
        }

        try {
            // Exchange code for tokens
            $token = $this->client->fetchAccessTokenWithAuthCode($code);

            if (isset($token['error'])) {
                throw new YouTubeAuthException(
                    'Failed to exchange authorization code: ' . ($token['error_description'] ?? $token['error'])
                );
            }

            // Ensure we have required token fields
            if (! isset($token['access_token'])) {
                throw new YouTubeAuthException('No access token received');
            }

            if (! isset($token['refresh_token'])) {
                // Log warning but don't fail - refresh token might not be provided on subsequent auths
                logger()->warning('No refresh token received from YouTube OAuth');
            }

            return $token;
        } catch (\Exception $e) {
            throw new YouTubeAuthException('OAuth authentication failed: ' . $e->getMessage(), 0, $e);
        }
    }

    /**
     * Refresh an access token using refresh token
     *
     * @param  string  $refreshToken  Refresh token
     * @return array New token data
     *
     * @throws YouTubeAuthException
     */
    public function refreshAccessToken(string $refreshToken): array
    {
        try {
            $this->client->refreshToken($refreshToken);
            $token = $this->client->getAccessToken();

            if (! $token || isset($token['error'])) {
                throw new YouTubeAuthException(
                    'Failed to refresh access token: ' . ($token['error_description'] ?? $token['error'] ?? 'Unknown error')
                );
            }

            // Add the refresh token back as it's not included in refresh response
            $token['refresh_token'] = $refreshToken;

            return $token;
        } catch (\Exception $e) {
            throw new YouTubeAuthException('Token refresh failed: ' . $e->getMessage(), 0, $e);
        }
    }

    /**
     * Revoke access token
     *
     * @param  string  $token  Access token or refresh token to revoke
     * @return bool Success status
     */
    public function revokeToken(string $token): bool
    {
        try {
            return $this->client->revokeToken($token);
        } catch (\Exception $e) {
            logger()->error('Failed to revoke YouTube token', [
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    /**
     * Set access token for authenticated requests
     *
     * @param  array  $token  Token data
     */
    public function setAccessToken(array $token): void
    {
        $this->client->setAccessToken($token);
    }

    /**
     * Check if current token is expired
     */
    public function isTokenExpired(): bool
    {
        return $this->client->isAccessTokenExpired();
    }

    /**
     * Get user information from OAuth2
     *
     * @param  string  $accessToken  Access token
     * @return array User info
     *
     * @throws YouTubeAuthException
     */
    public function getUserInfo(string $accessToken): array
    {
        try {
            $this->client->setAccessToken(['access_token' => $accessToken]);

            $oauth2 = new Google_Service_Oauth2($this->client);
            $userInfo = $oauth2->userinfo->get();

            return [
                'id' => $userInfo->getId(),
                'email' => $userInfo->getEmail(),
                'name' => $userInfo->getName(),
                'picture' => $userInfo->getPicture(),
                'verified_email' => $userInfo->getVerifiedEmail(),
                'locale' => $userInfo->getLocale(),
            ];
        } catch (\Exception $e) {
            throw new YouTubeAuthException('Failed to fetch user info: ' . $e->getMessage(), 0, $e);
        }
    }

    /**
     * Get channel information for authenticated user
     *
     * @param  string  $accessToken  Access token
     * @return array Channel info
     *
     * @throws YouTubeAuthException
     */
    public function getChannelInfo(string $accessToken): array
    {
        try {
            $this->client->setAccessToken(['access_token' => $accessToken]);

            $youtube = new Google_Service_YouTube($this->client);
            $response = $youtube->channels->listChannels('snippet,contentDetails,statistics', [
                'mine' => true,
            ]);

            if (! $response->getItems() || count($response->getItems()) === 0) {
                throw new YouTubeAuthException('No YouTube channel found for this account');
            }

            $channel = $response->getItems()[0];
            $snippet = $channel->getSnippet();
            $statistics = $channel->getStatistics();

            return [
                'id' => $channel->getId(),
                'title' => $snippet->getTitle(),
                'description' => $snippet->getDescription(),
                'handle' => $snippet->getCustomUrl(),
                'thumbnail' => $snippet->getThumbnails()->getHigh()->getUrl(),
                'published_at' => $snippet->getPublishedAt(),
                'country' => $snippet->getCountry(),
                'view_count' => $statistics->getViewCount(),
                'subscriber_count' => $statistics->getSubscriberCount(),
                'video_count' => $statistics->getVideoCount(),
            ];
        } catch (\Exception $e) {
            throw new YouTubeAuthException('Failed to fetch channel info: ' . $e->getMessage(), 0, $e);
        }
    }

    /**
     * Get the Google Client instance
     */
    public function getClient(): Google_Client
    {
        return $this->client;
    }

    /**
     * Create a YouTube service instance
     *
     * @param  array  $token  Access token data
     */
    public function createYouTubeService(array $token): Google_Service_YouTube
    {
        $this->setAccessToken($token);

        return new Google_Service_YouTube($this->client);
    }
}
