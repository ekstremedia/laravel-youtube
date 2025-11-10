<?php

use EkstreMedia\LaravelYouTube\Exceptions\YouTubeException;
use EkstreMedia\LaravelYouTube\Models\YouTubeToken;
use EkstreMedia\LaravelYouTube\Models\YouTubeVideo;
use EkstreMedia\LaravelYouTube\Services\AuthService;
use EkstreMedia\LaravelYouTube\Services\TokenManager;
use EkstreMedia\LaravelYouTube\Services\YouTubeService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;

uses(RefreshDatabase::class);

beforeEach(function () {
    Config::set('youtube.credentials.client_id', 'test-client-id');
    Config::set('youtube.credentials.client_secret', 'test-client-secret');
    Config::set('youtube.scopes', ['https://www.googleapis.com/auth/youtube']);
});

describe('YouTube Service', function () {
    it('can be instantiated', function () {
        $tokenManager = app(TokenManager::class);
        $authService = app(AuthService::class);
        $config = config('youtube');

        $service = new YouTubeService($tokenManager, $authService, $config);

        expect($service)->toBeInstanceOf(YouTubeService::class);
    });

    it('throws exception when no active token is set', function () {
        $service = app(YouTubeService::class);

        expect(fn () => $service->getChannel())
            ->toThrow(YouTubeException::class, 'No active token set');
    });

    it('can set user context', function () {
        $user = $this->createTestUser();

        $token = YouTubeToken::factory()->create([
            'user_id' => $user->id,
            'is_active' => true,
            'expires_at' => now()->addHour(),
        ]);

        $service = app(YouTubeService::class);

        expect(fn () => $service->forUser($user->id))
            ->not->toThrow(Exception::class);
    });
});

describe('Token Manager', function () {
    it('can store tokens', function () {
        $tokenManager = app(TokenManager::class);
        $user = $this->createTestUser();

        $tokenData = [
            'access_token' => 'test-access-token',
            'refresh_token' => 'test-refresh-token',
            'token_type' => 'Bearer',
            'expires_in' => 3600,
        ];

        $channelInfo = [
            'id' => 'channel-123',
            'title' => 'Test Channel',
            'handle' => '@testchannel',
            'thumbnail' => 'https://example.com/thumb.jpg',
        ];

        $token = $tokenManager->storeToken($tokenData, $channelInfo, $user->id);

        expect($token)->toBeInstanceOf(YouTubeToken::class)
            ->and($token->user_id)->toBe($user->id)
            ->and($token->channel_id)->toBe('channel-123')
            ->and($token->channel_title)->toBe('Test Channel')
            ->and($token->is_active)->toBeTrue();
    });

    it('can retrieve active tokens', function () {
        $user = $this->createTestUser();
        $token = YouTubeToken::factory()->create([
            'user_id' => $user->id,
            'is_active' => true,
            'expires_at' => now()->addHour(),
        ]);

        $tokenManager = app(TokenManager::class);
        $retrievedToken = $tokenManager->getActiveToken($user->id);

        expect($retrievedToken)->toBeInstanceOf(YouTubeToken::class)
            ->and($retrievedToken->id)->toBe($token->id);
    });

    it('identifies tokens that need refresh', function () {
        $tokenManager = app(TokenManager::class);

        // Token expiring soon
        $expiringToken = YouTubeToken::factory()->create([
            'expires_at' => now()->addMinutes(3),
        ]);

        // Token not expiring soon
        $validToken = YouTubeToken::factory()->create([
            'expires_at' => now()->addHours(2),
        ]);

        expect($tokenManager->needsRefresh($expiringToken))->toBeTrue()
            ->and($tokenManager->needsRefresh($validToken))->toBeFalse();
    });

    it('can mark tokens as failed', function () {
        $tokenManager = app(TokenManager::class);
        $token = YouTubeToken::factory()->create([
            'is_active' => true,
        ]);

        $tokenManager->markTokenFailed($token, 'Test error');

        $token->refresh();

        expect($token->is_active)->toBeFalse()
            ->and($token->error)->toBe('Test error')
            ->and($token->error_at)->not->toBeNull();
    });
});

describe('Auth Service', function () {
    it('generates authorization URL', function () {
        $authService = app(AuthService::class);
        $authUrl = $authService->getAuthUrl();

        expect($authUrl)->toBeString()
            ->and($authUrl)->toContain('accounts.google.com/o/oauth2')
            ->and($authUrl)->toContain('client_id=test-client-id')
            ->and($authUrl)->toContain('access_type=offline');
    });

    it('includes state parameter for CSRF protection', function () {
        $authService = app(AuthService::class);
        $state = 'test-state-123';
        $authUrl = $authService->getAuthUrl($state);

        expect($authUrl)->toContain('state=' . $state);
    });
});

describe('YouTube Video Model', function () {
    it('has correct relationships', function () {
        $user = $this->createTestUser();
        $token = YouTubeToken::factory()->create(['user_id' => $user->id]);
        $video = YouTubeVideo::factory()->create([
            'user_id' => $user->id,
            'token_id' => $token->id,
        ]);

        expect($video->user)->toBeInstanceOf(\Illuminate\Foundation\Auth\User::class)
            ->and($video->token)->toBeInstanceOf(YouTubeToken::class)
            ->and($video->user->id)->toBe($user->id)
            ->and($video->token->id)->toBe($token->id);
    });

    it('generates correct URLs', function () {
        $token = YouTubeToken::factory()->create();
        $video = YouTubeVideo::factory()->create([
            'video_id' => 'abc123',
            'token_id' => $token->id,
        ]);

        expect($video->watch_url)->toBe('https://www.youtube.com/watch?v=abc123')
            ->and($video->embed_url)->toBe('https://www.youtube.com/embed/abc123')
            ->and($video->studio_url)->toBe('https://studio.youtube.com/video/abc123/edit');
    });

    it('correctly identifies video privacy status', function () {
        $token = YouTubeToken::factory()->create();
        $publicVideo = YouTubeVideo::factory()->create(['privacy_status' => 'public', 'token_id' => $token->id]);
        $privateVideo = YouTubeVideo::factory()->create(['privacy_status' => 'private', 'token_id' => $token->id]);
        $unlistedVideo = YouTubeVideo::factory()->create(['privacy_status' => 'unlisted', 'token_id' => $token->id]);

        expect($publicVideo->is_public)->toBeTrue()
            ->and($publicVideo->is_private)->toBeFalse()
            ->and($privateVideo->is_private)->toBeTrue()
            ->and($privateVideo->is_public)->toBeFalse()
            ->and($unlistedVideo->is_unlisted)->toBeTrue()
            ->and($unlistedVideo->is_public)->toBeFalse();
    });

    it('formats view count correctly', function () {
        $token = YouTubeToken::factory()->create();
        $video1 = YouTubeVideo::factory()->create(['view_count' => 999, 'token_id' => $token->id]);
        $video2 = YouTubeVideo::factory()->create(['view_count' => 1500, 'token_id' => $token->id]);
        $video3 = YouTubeVideo::factory()->create(['view_count' => 2500000, 'token_id' => $token->id]);

        expect($video1->formatted_view_count)->toBe('999')
            ->and($video2->formatted_view_count)->toBe('1.5K')
            ->and($video3->formatted_view_count)->toBe('2.5M');
    });
});

describe('YouTube Token Model', function () {
    it('correctly identifies expired tokens', function () {
        $expiredToken = YouTubeToken::factory()->create([
            'expires_at' => now()->subHour(),
        ]);

        $validToken = YouTubeToken::factory()->create([
            'expires_at' => now()->addHour(),
        ]);

        expect($expiredToken->is_expired)->toBeTrue()
            ->and($validToken->is_expired)->toBeFalse();
    });

    it('correctly identifies tokens expiring soon', function () {
        $expiringSoon = YouTubeToken::factory()->create([
            'expires_at' => now()->addMinutes(3),
        ]);

        $notExpiringSoon = YouTubeToken::factory()->create([
            'expires_at' => now()->addHours(2),
        ]);

        expect($expiringSoon->expires_soon)->toBeTrue()
            ->and($notExpiringSoon->expires_soon)->toBeFalse();
    });

    it('can be marked as refreshed', function () {
        $token = YouTubeToken::factory()->create([
            'refresh_count' => 5,
            'error' => 'Some error',
            'error_at' => now()->subHour(),
        ]);

        $token->markAsRefreshed();

        expect($token->refresh_count)->toBe(6)
            ->and($token->error)->toBeNull()
            ->and($token->error_at)->toBeNull()
            ->and($token->last_refreshed_at)->not->toBeNull();
    });

    it('can be deactivated and activated', function () {
        $token = YouTubeToken::factory()->create([
            'is_active' => true,
            'error' => 'Test error',
        ]);

        $token->deactivate();
        expect($token->is_active)->toBeFalse();

        $token->activate();
        expect($token->is_active)->toBeTrue()
            ->and($token->error)->toBeNull();
    });

    it('scopes work correctly', function () {
        $user = $this->createTestUser();

        // Create various tokens
        YouTubeToken::factory()->create([
            'user_id' => $user->id,
            'is_active' => true,
            'expires_at' => now()->addHour(),
        ]);

        YouTubeToken::factory()->create([
            'user_id' => $user->id,
            'is_active' => false,
        ]);

        YouTubeToken::factory()->create([
            'user_id' => $user->id + 1,
            'is_active' => true,
        ]);

        $activeTokens = YouTubeToken::active()->get();
        $userTokens = YouTubeToken::forUser($user->id)->get();
        $activeUserTokens = YouTubeToken::active()->forUser($user->id)->get();

        expect($activeTokens)->toHaveCount(2)
            ->and($userTokens)->toHaveCount(2)
            ->and($activeUserTokens)->toHaveCount(1);
    });
});
