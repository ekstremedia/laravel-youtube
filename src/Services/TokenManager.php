<?php

namespace EkstreMedia\LaravelYouTube\Services;

use Carbon\Carbon;
use EkstreMedia\LaravelYouTube\Exceptions\TokenException;
use EkstreMedia\LaravelYouTube\Models\YouTubeToken;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Crypt;

class TokenManager
{
    /**
     * Storage configuration
     */
    protected array $config;

    /**
     * Cache repository
     */
    protected $cache;

    /**
     * Database connection
     */
    protected $db;

    /**
     * Create a new TokenManager instance
     *
     * @param  array  $config  Storage configuration
     * @param  mixed  $cache  Cache repository
     * @param  mixed  $db  Database connection
     */
    public function __construct(array $config, $cache, $db)
    {
        $this->config = $config;
        $this->cache = $cache;
        $this->db = $db;
    }

    /**
     * Store OAuth tokens in the database
     *
     * @param  array  $tokenData  Token data from OAuth
     * @param  array  $channelInfo  Channel information
     * @param  int|null  $userId  User ID
     *
     * @throws TokenException
     */
    public function storeToken(array $tokenData, array $channelInfo, ?int $userId = null): YouTubeToken
    {
        try {
            // Check if token already exists for this channel
            $existingToken = YouTubeToken::where('channel_id', $channelInfo['id'])
                ->when($userId, fn ($q) => $q->where('user_id', $userId))
                ->first();

            $tokenModel = $existingToken ?: new YouTubeToken;

            // Set token data
            $tokenModel->user_id = $userId;
            $tokenModel->channel_id = $channelInfo['id'];
            $tokenModel->channel_title = $channelInfo['title'];
            $tokenModel->channel_handle = $channelInfo['handle'] ?? null;
            $tokenModel->channel_thumbnail = $channelInfo['thumbnail'] ?? null;

            // Encrypt tokens before storage
            $tokenModel->access_token = Crypt::encryptString($tokenData['access_token']);

            if (isset($tokenData['refresh_token'])) {
                $tokenModel->refresh_token = Crypt::encryptString($tokenData['refresh_token']);
            } elseif (! $existingToken) {
                throw new TokenException('No refresh token provided for new token');
            }

            $tokenModel->token_type = $tokenData['token_type'] ?? 'Bearer';
            $tokenModel->expires_in = $tokenData['expires_in'] ?? 3600;

            // Calculate expiration time
            $expiresAt = isset($tokenData['expires_in'])
                ? Carbon::now()->addSeconds($tokenData['expires_in'])
                : Carbon::now()->addHour();
            $tokenModel->expires_at = $expiresAt;

            $tokenModel->scopes = $tokenData['scope'] ?? $this->config['scopes'] ?? null;
            $tokenModel->channel_metadata = $channelInfo;
            $tokenModel->is_active = true;
            $tokenModel->last_refreshed_at = Carbon::now();

            if ($existingToken) {
                $tokenModel->refresh_count = ($existingToken->refresh_count ?? 0) + 1;
            } else {
                $tokenModel->refresh_count = 0;
            }

            $tokenModel->error = null;
            $tokenModel->error_at = null;

            $tokenModel->save();

            // Clear cache for this user
            if ($userId) {
                $this->clearTokenCache($userId);
            }

            // Log the successful token storage
            logger()->info('YouTube token stored successfully', [
                'user_id' => $userId,
                'channel_id' => $channelInfo['id'],
                'expires_at' => $expiresAt->toDateTimeString(),
            ]);

            return $tokenModel;
        } catch (\Exception $e) {
            logger()->error('Failed to store YouTube token', [
                'error' => $e->getMessage(),
                'user_id' => $userId,
            ]);
            throw new TokenException('Failed to store token: ' . $e->getMessage(), 0, $e);
        }
    }

    /**
     * Get active token for a user
     *
     * @param  int  $userId  User ID
     * @param  string|null  $channelId  Optional channel ID
     */
    public function getActiveToken(int $userId, ?string $channelId = null): ?YouTubeToken
    {
        $cacheKey = $this->getCacheKey($userId, $channelId);

        return $this->cache->remember($cacheKey, $this->config['cache_ttl'] ?? 3600, function () use ($userId, $channelId) {
            $query = YouTubeToken::where('user_id', $userId)
                ->where('is_active', true);

            if ($channelId) {
                $query->where('channel_id', $channelId);
            }

            return $query->orderBy('last_refreshed_at', 'desc')->first();
        });
    }

    /**
     * Get all active tokens for a user
     *
     * @param  int  $userId  User ID
     */
    public function getUserTokens(int $userId): \Illuminate\Support\Collection
    {
        return YouTubeToken::where('user_id', $userId)
            ->where('is_active', true)
            ->orderBy('last_refreshed_at', 'desc')
            ->get();
    }

    /**
     * Get decrypted access token
     *
     * @param  YouTubeToken  $token  Token model
     *
     * @throws TokenException
     */
    public function getAccessToken(YouTubeToken $token): string
    {
        try {
            return Crypt::decryptString($token->access_token);
        } catch (\Exception $e) {
            throw new TokenException('Failed to decrypt access token', 0, $e);
        }
    }

    /**
     * Get decrypted refresh token
     *
     * @param  YouTubeToken  $token  Token model
     *
     * @throws TokenException
     */
    public function getRefreshToken(YouTubeToken $token): string
    {
        try {
            return Crypt::decryptString($token->refresh_token);
        } catch (\Exception $e) {
            throw new TokenException('Failed to decrypt refresh token', 0, $e);
        }
    }

    /**
     * Check if token needs refresh
     *
     * @param  YouTubeToken  $token  Token model
     */
    public function needsRefresh(YouTubeToken $token): bool
    {
        // Refresh if expires within 5 minutes
        return $token->expires_at->subMinutes(5)->isPast();
    }

    /**
     * Update token after refresh
     *
     * @param  YouTubeToken  $token  Token model
     * @param  array  $newTokenData  New token data
     *
     * @throws TokenException
     */
    public function updateToken(YouTubeToken $token, array $newTokenData): YouTubeToken
    {
        try {
            // Update access token
            $token->access_token = Crypt::encryptString($newTokenData['access_token']);

            // Update refresh token if provided (usually not in refresh response)
            if (isset($newTokenData['refresh_token'])) {
                $token->refresh_token = Crypt::encryptString($newTokenData['refresh_token']);
            }

            // Update expiration
            $token->expires_in = $newTokenData['expires_in'] ?? 3600;
            $token->expires_at = Carbon::now()->addSeconds($token->expires_in);

            // Update metadata
            $token->last_refreshed_at = Carbon::now();
            $token->refresh_count = ($token->refresh_count ?? 0) + 1;
            $token->error = null;
            $token->error_at = null;

            $token->save();

            // Clear cache
            $this->clearTokenCache($token->user_id, $token->channel_id);

            logger()->info('YouTube token refreshed successfully', [
                'token_id' => $token->id,
                'user_id' => $token->user_id,
                'channel_id' => $token->channel_id,
                'refresh_count' => $token->refresh_count,
            ]);

            return $token;
        } catch (\Exception $e) {
            logger()->error('Failed to update YouTube token', [
                'token_id' => $token->id,
                'error' => $e->getMessage(),
            ]);
            throw new TokenException('Failed to update token: ' . $e->getMessage(), 0, $e);
        }
    }

    /**
     * Mark token as failed
     *
     * @param  YouTubeToken  $token  Token model
     * @param  string  $error  Error message
     */
    public function markTokenFailed(YouTubeToken $token, string $error): void
    {
        $token->error = $error;
        $token->error_at = Carbon::now();
        $token->is_active = false;
        $token->save();

        $this->clearTokenCache($token->user_id, $token->channel_id);

        logger()->warning('YouTube token marked as failed', [
            'token_id' => $token->id,
            'error' => $error,
        ]);
    }

    /**
     * Deactivate token
     *
     * @param  YouTubeToken  $token  Token model
     */
    public function deactivateToken(YouTubeToken $token): void
    {
        $token->is_active = false;
        $token->save();

        $this->clearTokenCache($token->user_id, $token->channel_id);

        logger()->info('YouTube token deactivated', [
            'token_id' => $token->id,
            'user_id' => $token->user_id,
            'channel_id' => $token->channel_id,
        ]);
    }

    /**
     * Delete expired tokens
     *
     * @param  int  $daysOld  Delete tokens older than this many days
     * @return int Number of deleted tokens
     */
    public function deleteExpiredTokens(int $daysOld = 30): int
    {
        $cutoffDate = Carbon::now()->subDays($daysOld);

        $count = YouTubeToken::where('is_active', false)
            ->where('updated_at', '<', $cutoffDate)
            ->delete();

        if ($count > 0) {
            logger()->info('Deleted expired YouTube tokens', [
                'count' => $count,
                'cutoff_date' => $cutoffDate->toDateTimeString(),
            ]);
        }

        return $count;
    }

    /**
     * Refresh all expiring tokens
     *
     * @return int Number of refreshed tokens
     */
    public function refreshExpiringTokens(): int
    {
        $count = 0;
        $expiringTokens = YouTubeToken::where('is_active', true)
            ->where('expires_at', '<=', Carbon::now()->addMinutes(10))
            ->get();

        foreach ($expiringTokens as $token) {
            try {
                // This will be handled by the main YouTube service
                // We just mark them for refresh here
                logger()->info('Token needs refresh', [
                    'token_id' => $token->id,
                    'expires_at' => $token->expires_at->toDateTimeString(),
                ]);
                $count++;
            } catch (\Exception $e) {
                logger()->error('Failed to mark token for refresh', [
                    'token_id' => $token->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        return $count;
    }

    /**
     * Get cache key for token
     *
     * @param  int  $userId  User ID
     * @param  string|null  $channelId  Channel ID
     */
    protected function getCacheKey(int $userId, ?string $channelId = null): string
    {
        $key = $this->config['cache_key'] . ':' . $userId;
        if ($channelId) {
            $key .= ':' . $channelId;
        }

        return $key;
    }

    /**
     * Clear token cache
     *
     * @param  int|null  $userId  User ID
     * @param  string|null  $channelId  Channel ID
     */
    protected function clearTokenCache(?int $userId = null, ?string $channelId = null): void
    {
        if ($userId) {
            $this->cache->forget($this->getCacheKey($userId, $channelId));
            if ($channelId) {
                $this->cache->forget($this->getCacheKey($userId));
            }
        }
    }
}
