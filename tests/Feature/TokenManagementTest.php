<?php

use Ekstremedia\LaravelYouTube\Models\YouTubeToken;
use Ekstremedia\LaravelYouTube\Services\AuthService;
use Ekstremedia\LaravelYouTube\Services\TokenManager;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Crypt;

uses(RefreshDatabase::class);

beforeEach(function () {
    Config::set('youtube.credentials.client_id', 'test-client-id');
    Config::set('youtube.credentials.client_secret', 'test-client-secret');
    Config::set('youtube.token.cache_ttl', 3600);
});

describe('Token Storage and Retrieval', function () {
    it('encrypts tokens when storing', function () {
        $tokenManager = app(TokenManager::class);
        $user = $this->createTestUser();

        $tokenData = [
            'access_token' => 'secret-access-token',
            'refresh_token' => 'secret-refresh-token',
            'token_type' => 'Bearer',
            'expires_in' => 3600,
        ];

        $channelInfo = [
            'id' => 'channel-123',
            'title' => 'Test Channel',
        ];

        $token = $tokenManager->storeToken($tokenData, $channelInfo, $user->id);

        // Tokens should be encrypted in database
        $rawToken = \DB::table('youtube_tokens')->where('id', $token->id)->first();

        expect($rawToken->access_token)->not->toBe('secret-access-token')
            ->and($rawToken->refresh_token)->not->toBe('secret-refresh-token');

        // But should decrypt correctly when accessed
        expect($tokenManager->getAccessToken($token))->toBe('secret-access-token')
            ->and($tokenManager->getRefreshToken($token))->toBe('secret-refresh-token');
    });

    it('caches tokens after retrieval', function () {
        $tokenManager = app(TokenManager::class);
        $user = $this->createTestUser();

        $token = YouTubeToken::factory()->create([
            'user_id' => $user->id,
            'is_active' => true,
            'expires_at' => now()->addHour(),
        ]);

        Cache::flush();
        $cacheKey = config('youtube.storage.cache_key') . ':' . $user->id;
        expect(Cache::has($cacheKey))->toBeFalse();

        // First retrieval should cache the token
        $retrieved = $tokenManager->getActiveToken($user->id);

        expect(Cache::has($cacheKey))->toBeTrue()
            ->and($retrieved->id)->toBe($token->id);
    });

    it('invalidates cache when token is updated', function () {
        $tokenManager = app(TokenManager::class);
        $user = $this->createTestUser();

        $token = YouTubeToken::factory()->create([
            'user_id' => $user->id,
            'is_active' => true,
        ]);

        // Cache the token
        $cacheKey = config('youtube.storage.cache_key') . ':' . $user->id;
        $tokenManager->getActiveToken($user->id);
        expect(Cache::has($cacheKey))->toBeTrue();

        // Update token should invalidate cache
        $tokenManager->updateToken($token, [
            'access_token' => 'new-access-token',
            'expires_in' => 3600,
        ]);

        expect(Cache::has($cacheKey))->toBeFalse();
    });

    it('handles multiple tokens per user', function () {
        $tokenManager = app(TokenManager::class);
        $user = $this->createTestUser();

        // Create multiple tokens for different channels
        $token1 = YouTubeToken::factory()->create([
            'user_id' => $user->id,
            'channel_id' => 'channel-1',
            'is_active' => true,
        ]);

        $token2 = YouTubeToken::factory()->create([
            'user_id' => $user->id,
            'channel_id' => 'channel-2',
            'is_active' => true,
        ]);

        $userTokens = $tokenManager->getUserTokens($user->id);
        expect($userTokens)->toHaveCount(2);

        // Can get specific channel token
        $channelToken = $tokenManager->getActiveToken($user->id, 'channel-2');
        expect($channelToken->id)->toBe($token2->id);
    });
});

describe('Token Refresh', function () {
    it('identifies tokens needing refresh', function () {
        $tokenManager = app(TokenManager::class);

        $expiringSoon = YouTubeToken::factory()->create([
            'expires_at' => now()->addMinutes(3),
        ]);

        $expired = YouTubeToken::factory()->create([
            'expires_at' => now()->subMinute(),
        ]);

        $valid = YouTubeToken::factory()->create([
            'expires_at' => now()->addHours(2),
        ]);

        expect($tokenManager->needsRefresh($expiringSoon))->toBeTrue()
            ->and($tokenManager->needsRefresh($expired))->toBeTrue()
            ->and($tokenManager->needsRefresh($valid))->toBeFalse();
    });

    it('refreshes access token using refresh token', function () {
        $this->markTestSkipped('OAuth refresh requires Google Client mocking at lower level');

        $authService = app(AuthService::class);
        $tokenManager = app(TokenManager::class);

        $token = YouTubeToken::factory()->create([
            'refresh_token' => Crypt::encryptString('valid-refresh-token'),
            'expires_at' => now()->subMinute(),
            'refresh_count' => 0,
        ]);

        $newTokenData = $authService->refreshAccessToken('valid-refresh-token');
        $tokenManager->updateToken($token, $newTokenData);

        $token->refresh();

        expect($token->refresh_count)->toBe(1)
            ->and($token->last_refreshed_at)->not->toBeNull()
            ->and($token->expires_at)->toBeGreaterThan(now());
    });

    it('marks token as failed when refresh fails', function () {
        $this->markTestSkipped('OAuth refresh requires Google Client mocking at lower level');

        $tokenManager = app(TokenManager::class);
        $authService = app(AuthService::class);

        $token = YouTubeToken::factory()->create([
            'refresh_token' => Crypt::encryptString('invalid-refresh-token'),
            'is_active' => true,
        ]);

        try {
            $authService->refreshAccessToken('invalid-refresh-token');
        } catch (\Exception $e) {
            $tokenManager->markTokenFailed($token, $e->getMessage());
        }

        $token->refresh();

        expect($token->is_active)->toBeFalse()
            ->and($token->error)->not->toBeNull()
            ->and($token->error_at)->not->toBeNull();
    });

    it('automatically refreshes expiring tokens', function () {
        $tokenManager = app(TokenManager::class);

        // Create expiring token
        $token = YouTubeToken::factory()->create([
            'refresh_token' => Crypt::encryptString('refresh-token'),
            'expires_at' => now()->addMinutes(3),
            'is_active' => true,
        ]);

        $count = $tokenManager->refreshExpiringTokens();

        expect($count)->toBe(1);
    });
});

describe('OAuth Flow', function () {
    it('generates proper OAuth URL with scopes', function () {
        Config::set('youtube.scopes', [
            'https://www.googleapis.com/auth/youtube',
            'https://www.googleapis.com/auth/youtube.upload',
        ]);

        $authService = app(AuthService::class);
        $authUrl = $authService->getAuthUrl('test-state');

        expect($authUrl)->toContain('https://accounts.google.com/o/oauth2/v2/auth')
            ->and($authUrl)->toContain('client_id=test-client-id')
            ->and($authUrl)->toContain('state=test-state')
            ->and($authUrl)->toContain('access_type=offline');

        // Check that scopes are included (Google Client may encode them differently)
        expect($authUrl)->toMatch('/scope=.*youtube/');
    });

    it('exchanges authorization code for tokens', function () {
        $this->markTestSkipped('OAuth code exchange requires Google Client mocking at lower level');
    });

    it('gets channel info after authentication', function () {
        $this->markTestSkipped('YouTube API calls require Google Client mocking at lower level');
    });

    it('can revoke tokens', function () {
        $this->markTestSkipped('OAuth token revocation requires Google Client mocking at lower level');
    });
});

describe('Token Cleanup', function () {
    it('deletes expired inactive tokens', function () {
        $tokenManager = app(TokenManager::class);

        // Create old inactive tokens
        YouTubeToken::factory()->count(3)->create([
            'is_active' => false,
            'updated_at' => now()->subDays(35),
        ]);

        // Create recent inactive token
        YouTubeToken::factory()->create([
            'is_active' => false,
            'updated_at' => now()->subDays(5),
        ]);

        // Create old but active token
        YouTubeToken::factory()->create([
            'is_active' => true,
            'updated_at' => now()->subDays(35),
        ]);

        $deleted = $tokenManager->deleteExpiredTokens(30);

        expect($deleted)->toBe(3)
            ->and(YouTubeToken::count())->toBe(2);
    });

    it('handles token with errors appropriately', function () {
        $tokenManager = app(TokenManager::class);

        $errorToken = YouTubeToken::factory()->create([
            'is_active' => true,
            'error' => null,
        ]);

        // Mark as failed
        $tokenManager->markTokenFailed($errorToken, 'Authentication failed');
        $errorToken->refresh();

        expect($errorToken->is_active)->toBeFalse()
            ->and($errorToken->has_error)->toBeTrue()
            ->and($errorToken->error)->toBe('Authentication failed');

        // Should be able to reactivate after fixing
        $errorToken->activate();

        expect($errorToken->is_active)->toBeTrue()
            ->and($errorToken->error)->toBeNull();
    });
});

describe('Token Scopes and Permissions', function () {
    it('stores and retrieves token scopes', function () {
        $tokenManager = app(TokenManager::class);

        $scopes = [
            'https://www.googleapis.com/auth/youtube',
            'https://www.googleapis.com/auth/youtube.upload',
            'https://www.googleapis.com/auth/youtubepartner',
        ];

        $tokenData = [
            'access_token' => 'token',
            'refresh_token' => 'refresh',
            'token_type' => 'Bearer',
            'expires_in' => 3600,
            'scope' => implode(' ', $scopes),
        ];

        $token = $tokenManager->storeToken($tokenData, [
            'id' => 'channel',
            'title' => 'Test Channel',
        ], null);

        expect($token->scopes)->toBeArray()
            ->and($token->scopes)->toHaveCount(3)
            ->and($token->scopes)->toContain('https://www.googleapis.com/auth/youtube.upload');
    });

    it('checks if token has required scope', function () {
        $token = YouTubeToken::factory()->create([
            'scopes' => [
                'https://www.googleapis.com/auth/youtube',
                'https://www.googleapis.com/auth/youtube.readonly',
            ],
        ]);

        expect($token->hasScope('youtube'))->toBeTrue()
            ->and($token->hasScope('youtube.upload'))->toBeFalse()
            ->and($token->hasScope('youtube.readonly'))->toBeTrue();
    });
});
