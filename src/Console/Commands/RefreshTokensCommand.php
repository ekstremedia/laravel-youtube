<?php

namespace EkstreMedia\LaravelYouTube\Console\Commands;

use Illuminate\Console\Command;
use EkstreMedia\LaravelYouTube\Services\TokenManager;
use EkstreMedia\LaravelYouTube\Services\AuthService;
use EkstreMedia\LaravelYouTube\Models\YouTubeToken;

class RefreshTokensCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'youtube:refresh-tokens
                            {--token-id= : Refresh a specific token by ID}
                            {--user-id= : Refresh all tokens for a specific user}
                            {--force : Force refresh even if not expiring}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Refresh expiring or expired YouTube OAuth tokens';

    /**
     * Token manager instance
     */
    protected TokenManager $tokenManager;

    /**
     * Auth service instance
     */
    protected AuthService $authService;

    /**
     * Create a new command instance.
     *
     * @param TokenManager $tokenManager
     * @param AuthService $authService
     */
    public function __construct(TokenManager $tokenManager, AuthService $authService)
    {
        parent::__construct();
        $this->tokenManager = $tokenManager;
        $this->authService = $authService;
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle(): int
    {
        $this->info('Starting YouTube token refresh process...');

        $tokenId = $this->option('token-id');
        $userId = $this->option('user-id');
        $force = $this->option('force');

        if ($tokenId) {
            // Refresh specific token
            $token = YouTubeToken::find($tokenId);

            if (!$token) {
                $this->error("Token with ID {$tokenId} not found.");
                return 1;
            }

            return $this->refreshToken($token, $force) ? 0 : 1;
        }

        if ($userId) {
            // Refresh all tokens for a user
            $tokens = YouTubeToken::where('user_id', $userId)
                ->where('is_active', true)
                ->get();

            if ($tokens->isEmpty()) {
                $this->warn("No active tokens found for user ID {$userId}.");
                return 0;
            }

            $this->info("Found {$tokens->count()} active tokens for user ID {$userId}.");
        } else {
            // Refresh all expiring tokens
            $query = YouTubeToken::where('is_active', true);

            if (!$force) {
                $query->expiringSoon(15); // Tokens expiring in 15 minutes
            }

            $tokens = $query->get();

            if ($tokens->isEmpty()) {
                $this->info('No tokens need refreshing.');
                return 0;
            }

            $this->info("Found {$tokens->count()} tokens that need refreshing.");
        }

        $successCount = 0;
        $failCount = 0;

        $this->output->progressStart($tokens->count());

        foreach ($tokens as $token) {
            if ($this->refreshToken($token, $force)) {
                $successCount++;
            } else {
                $failCount++;
            }
            $this->output->progressAdvance();
        }

        $this->output->progressFinish();

        $this->info("Token refresh complete!");
        $this->info("Successfully refreshed: {$successCount}");

        if ($failCount > 0) {
            $this->warn("Failed to refresh: {$failCount}");
        }

        return $failCount > 0 ? 1 : 0;
    }

    /**
     * Refresh a single token
     *
     * @param YouTubeToken $token
     * @param bool $force
     * @return bool
     */
    protected function refreshToken(YouTubeToken $token, bool $force = false): bool
    {
        try {
            // Check if refresh is needed
            if (!$force && !$this->tokenManager->needsRefresh($token)) {
                $this->line("Token {$token->id} does not need refresh (expires at {$token->expires_at}).");
                return true;
            }

            $this->line("Refreshing token {$token->id} for channel: {$token->channel_title}...");

            // Get refresh token
            $refreshToken = $this->tokenManager->getRefreshToken($token);

            // Refresh the token
            $newTokenData = $this->authService->refreshAccessToken($refreshToken);

            // Update token in database
            $this->tokenManager->updateToken($token, $newTokenData);

            $this->info("✓ Token {$token->id} refreshed successfully. New expiry: {$token->fresh()->expires_at}");

            return true;
        } catch (\Exception $e) {
            $this->error("✗ Failed to refresh token {$token->id}: {$e->getMessage()}");

            // Mark token as failed
            $this->tokenManager->markTokenFailed($token, $e->getMessage());

            return false;
        }
    }
}