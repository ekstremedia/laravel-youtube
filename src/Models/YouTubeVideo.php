<?php

namespace EkstreMedia\LaravelYouTube\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Carbon\Carbon;

class YouTubeVideo extends Model
{
    use HasFactory;
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'youtube_videos';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'user_id',
        'token_id',
        'video_id',
        'channel_id',
        'title',
        'description',
        'tags',
        'category_id',
        'privacy_status',
        'license',
        'embeddable',
        'public_stats_viewable',
        'made_for_kids',
        'default_language',
        'default_audio_language',
        'recording_date',
        'video_url',
        'embed_url',
        'thumbnail_default',
        'thumbnail_medium',
        'thumbnail_high',
        'thumbnail_standard',
        'thumbnail_maxres',
        'duration',
        'definition',
        'caption',
        'licensed_content',
        'projection',
        'view_count',
        'like_count',
        'dislike_count',
        'comment_count',
        'upload_status',
        'failure_reason',
        'rejection_reason',
        'processing_status',
        'processing_progress',
        'processing_details',
        'published_at',
        'scheduled_start_time',
        'scheduled_end_time',
        'actual_start_time',
        'actual_end_time',
        'live_streaming_details',
        'statistics',
        'metadata',
        'synced_at',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'tags' => 'array',
        'embeddable' => 'boolean',
        'public_stats_viewable' => 'boolean',
        'made_for_kids' => 'boolean',
        'licensed_content' => 'boolean',
        'view_count' => 'integer',
        'like_count' => 'integer',
        'dislike_count' => 'integer',
        'comment_count' => 'integer',
        'processing_progress' => 'integer',
        'published_at' => 'datetime',
        'scheduled_start_time' => 'datetime',
        'scheduled_end_time' => 'datetime',
        'actual_start_time' => 'datetime',
        'actual_end_time' => 'datetime',
        'live_streaming_details' => 'array',
        'statistics' => 'array',
        'metadata' => 'array',
        'synced_at' => 'datetime',
    ];

    /**
     * Get the user that owns the video.
     */
    public function user(): BelongsTo
    {
        // Use configured user model or fall back to Illuminate's default
        $userModel = config('auth.providers.users.model', \Illuminate\Foundation\Auth\User::class);
        return $this->belongsTo($userModel);
    }

    /**
     * Get the token used to upload the video.
     */
    public function token(): BelongsTo
    {
        return $this->belongsTo(YouTubeToken::class, 'token_id');
    }

    /**
     * Get the YouTube watch URL.
     *
     * @return Attribute
     */
    protected function watchUrl(): Attribute
    {
        return Attribute::make(
            get: fn () => "https://www.youtube.com/watch?v={$this->video_id}",
        );
    }

    /**
     * Get the YouTube embed URL.
     *
     * @return Attribute
     */
    protected function embedUrl(): Attribute
    {
        return Attribute::make(
            get: fn () => "https://www.youtube.com/embed/{$this->video_id}",
        );
    }

    /**
     * Get the YouTube studio edit URL.
     *
     * @return Attribute
     */
    protected function studioUrl(): Attribute
    {
        return Attribute::make(
            get: fn () => "https://studio.youtube.com/video/{$this->video_id}/edit",
        );
    }

    /**
     * Check if video is public.
     *
     * @return Attribute
     */
    protected function isPublic(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->privacy_status === 'public',
        );
    }

    /**
     * Check if video is private.
     *
     * @return Attribute
     */
    protected function isPrivate(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->privacy_status === 'private',
        );
    }

    /**
     * Check if video is unlisted.
     *
     * @return Attribute
     */
    protected function isUnlisted(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->privacy_status === 'unlisted',
        );
    }

    /**
     * Check if video is being processed.
     *
     * @return Attribute
     */
    protected function isProcessing(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->processing_status === 'processing',
        );
    }

    /**
     * Check if video processing failed.
     *
     * @return Attribute
     */
    protected function processingFailed(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->processing_status === 'failed' || !empty($this->failure_reason),
        );
    }

    /**
     * Check if video is live.
     *
     * @return Attribute
     */
    protected function isLive(): Attribute
    {
        return Attribute::make(
            get: fn () => !empty($this->live_streaming_details),
        );
    }

    /**
     * Get formatted duration.
     *
     * @return Attribute
     */
    protected function formattedDuration(): Attribute
    {
        return Attribute::make(
            get: function () {
                if (!$this->duration) {
                    return null;
                }

                // Parse ISO 8601 duration (e.g., PT4M13S)
                try {
                    $interval = new \DateInterval($this->duration);
                    $parts = [];

                    if ($interval->h > 0) {
                        $parts[] = $interval->h . 'h';
                    }
                    if ($interval->i > 0) {
                        $parts[] = $interval->i . 'm';
                    }
                    if ($interval->s > 0) {
                        $parts[] = $interval->s . 's';
                    }

                    return implode(' ', $parts);
                } catch (\Exception $e) {
                    return $this->duration;
                }
            },
        );
    }

    /**
     * Get the best available thumbnail.
     *
     * @return Attribute
     */
    protected function thumbnail(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->thumbnail_maxres
                ?? $this->thumbnail_standard
                ?? $this->thumbnail_high
                ?? $this->thumbnail_medium
                ?? $this->thumbnail_default,
        );
    }

    /**
     * Get formatted view count.
     *
     * @return Attribute
     */
    protected function formattedViewCount(): Attribute
    {
        return Attribute::make(
            get: function () {
                $count = $this->view_count;
                if ($count >= 1000000) {
                    return round($count / 1000000, 1) . 'M';
                } elseif ($count >= 1000) {
                    return round($count / 1000, 1) . 'K';
                }
                return (string) $count;
            },
        );
    }

    /**
     * Scope a query to only include public videos.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopePublic($query)
    {
        return $query->where('privacy_status', 'public');
    }

    /**
     * Scope a query to only include private videos.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopePrivate($query)
    {
        return $query->where('privacy_status', 'private');
    }

    /**
     * Scope a query to only include unlisted videos.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeUnlisted($query)
    {
        return $query->where('privacy_status', 'unlisted');
    }

    /**
     * Scope a query to only include videos from a specific channel.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param string $channelId
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeFromChannel($query, string $channelId)
    {
        return $query->where('channel_id', $channelId);
    }

    /**
     * Scope a query to only include processed videos.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeProcessed($query)
    {
        return $query->where('processing_status', 'succeeded');
    }

    /**
     * Scope a query to only include videos being processed.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeProcessing($query)
    {
        return $query->where('processing_status', 'processing');
    }

    /**
     * Scope a query to search videos by title or description.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param string $search
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeSearch($query, string $search)
    {
        return $query->where(function ($q) use ($search) {
            $q->where('title', 'like', "%{$search}%")
              ->orWhere('description', 'like', "%{$search}%");
        });
    }

    /**
     * Update video statistics from YouTube API.
     *
     * @param array $statistics
     * @return void
     */
    public function updateStatistics(array $statistics): void
    {
        $this->update([
            'view_count' => $statistics['viewCount'] ?? $this->view_count,
            'like_count' => $statistics['likeCount'] ?? $this->like_count,
            'dislike_count' => $statistics['dislikeCount'] ?? $this->dislike_count,
            'comment_count' => $statistics['commentCount'] ?? $this->comment_count,
            'statistics' => $statistics,
            'synced_at' => Carbon::now(),
        ]);
    }

    /**
     * Update video processing status.
     *
     * @param string $status
     * @param array|null $details
     * @return void
     */
    public function updateProcessingStatus(string $status, ?array $details = null): void
    {
        $data = ['processing_status' => $status];

        if ($details) {
            $data['processing_details'] = json_encode($details);
            $data['processing_progress'] = $details['progress'] ?? null;
        }

        if ($status === 'failed') {
            $data['failure_reason'] = $details['reason'] ?? 'Unknown error';
        }

        $this->update($data);
    }

    /**
     * Create a new factory instance for the model.
     *
     * @return \Illuminate\Database\Eloquent\Factories\Factory
     */
    protected static function newFactory()
    {
        return \EkstreMedia\LaravelYouTube\Database\Factories\YouTubeVideoFactory::new();
    }
}