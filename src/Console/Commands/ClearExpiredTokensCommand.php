<?php

namespace Ekstremedia\LaravelYouTube\Console\Commands;

use Carbon\Carbon;
use Ekstremedia\LaravelYouTube\Models\YouTubeToken;
use Ekstremedia\LaravelYouTube\Services\TokenManager;
use Illuminate\Console\Command;

class ClearExpiredTokensCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'youtube:clear-expired-tokens
                            {--days=30 : Number of days old for inactive tokens to be deleted}
                            {--dry-run : Show what would be deleted without actually deleting}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Clear expired and inactive YouTube OAuth tokens';

    /**
     * Token manager instance
     */
    protected TokenManager $tokenManager;

    /**
     * Create a new command instance.
     */
    public function __construct(TokenManager $tokenManager)
    {
        parent::__construct();
        $this->tokenManager = $tokenManager;
    }

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $days = (int) $this->option('days');
        $dryRun = $this->option('dry-run');

        $this->info('Starting YouTube token cleanup process...');

        if ($dryRun) {
            $this->warn('DRY RUN MODE - No tokens will be deleted.');
        }

        // Find tokens to delete
        $cutoffDate = Carbon::now()->subDays($days);

        $tokensToDelete = YouTubeToken::where(function ($query) use ($cutoffDate) {
            // Inactive tokens older than cutoff
            $query->where('is_active', false)
                ->where('updated_at', '<', $cutoffDate);
        })->orWhere(function ($query) use ($cutoffDate) {
            // Tokens with errors older than cutoff
            $query->whereNotNull('error')
                ->where('error_at', '<', $cutoffDate);
        })->get();

        if ($tokensToDelete->isEmpty()) {
            $this->info('No expired tokens found to delete.');

            return 0;
        }

        $this->info("Found {$tokensToDelete->count()} expired tokens to delete.");

        // Display table of tokens to be deleted
        $tableData = $tokensToDelete->map(function ($token) {
            return [
                'ID' => $token->id,
                'User ID' => $token->user_id,
                'Channel' => $token->channel_title,
                'Status' => $token->is_active ? 'Active' : 'Inactive',
                'Error' => $token->error ? substr($token->error, 0, 30) . '...' : 'None',
                'Updated' => $token->updated_at->format('Y-m-d H:i'),
            ];
        })->toArray();

        $this->table(
            ['ID', 'User ID', 'Channel', 'Status', 'Error', 'Updated'],
            $tableData
        );

        if ($dryRun) {
            $this->info("Would delete {$tokensToDelete->count()} tokens.");

            return 0;
        }

        // Confirm deletion
        if (! $this->confirm("Do you want to delete {$tokensToDelete->count()} expired tokens?")) {
            $this->info('Operation cancelled.');

            return 0;
        }

        // Delete tokens
        $deletedCount = 0;
        $failedCount = 0;

        $this->output->progressStart($tokensToDelete->count());

        foreach ($tokensToDelete as $token) {
            try {
                $token->delete();
                $deletedCount++;
            } catch (\Exception $e) {
                $this->error("Failed to delete token {$token->id}: {$e->getMessage()}");
                $failedCount++;
            }
            $this->output->progressAdvance();
        }

        $this->output->progressFinish();

        // Also delete orphaned video records
        $this->cleanupOrphanedVideos();

        $this->info('Token cleanup complete!');
        $this->info("Successfully deleted: {$deletedCount} tokens");

        if ($failedCount > 0) {
            $this->warn("Failed to delete: {$failedCount} tokens");

            return 1;
        }

        return 0;
    }

    /**
     * Clean up orphaned video records
     */
    protected function cleanupOrphanedVideos(): void
    {
        $this->info('Checking for orphaned video records...');

        // Find videos without valid tokens
        $orphanedVideos = \DB::table('youtube_videos')
            ->leftJoin('youtube_tokens', 'youtube_videos.token_id', '=', 'youtube_tokens.id')
            ->whereNull('youtube_tokens.id')
            ->select('youtube_videos.id')
            ->get();

        if ($orphanedVideos->isEmpty()) {
            $this->info('No orphaned video records found.');

            return;
        }

        $count = $orphanedVideos->count();
        $this->warn("Found {$count} orphaned video records.");

        if ($this->confirm("Do you want to delete {$count} orphaned video records?")) {
            $ids = $orphanedVideos->pluck('id')->toArray();
            $deleted = \DB::table('youtube_videos')->whereIn('id', $ids)->delete();
            $this->info("Deleted {$deleted} orphaned video records.");
        }
    }
}
