<?php

namespace Ekstremedia\LaravelYouTube\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class YouTubeUpload extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'youtube_uploads';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'user_id',
        'token_id',
        'file_path',
        'file_name',
        'file_size',
        'title',
        'description',
        'tags',
        'privacy_status',
        'category_id',
        'playlist_id',
        'upload_status',
        'youtube_video_id',
        'error_message',
        'started_at',
        'completed_at',
        'progress',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'tags' => 'array',
        'file_size' => 'integer',
        'progress' => 'integer',
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
    ];

    /**
     * Get the user that owns the upload.
     */
    public function user(): BelongsTo
    {
        // Use configured user model or fall back to Illuminate's default
        $userModel = config('auth.providers.users.model', \Illuminate\Foundation\Auth\User::class);

        return $this->belongsTo($userModel);
    }

    /**
     * Get the token used for this upload.
     */
    public function token(): BelongsTo
    {
        return $this->belongsTo(YouTubeToken::class, 'token_id');
    }

    /**
     * Check if upload is pending.
     */
    public function isPending(): bool
    {
        return $this->upload_status === 'pending';
    }

    /**
     * Check if upload is in progress.
     */
    public function isUploading(): bool
    {
        return $this->upload_status === 'uploading';
    }

    /**
     * Check if upload is processing.
     */
    public function isProcessing(): bool
    {
        return $this->upload_status === 'processing';
    }

    /**
     * Check if upload is completed.
     */
    public function isCompleted(): bool
    {
        return $this->upload_status === 'completed';
    }

    /**
     * Check if upload has failed.
     */
    public function hasFailed(): bool
    {
        return $this->upload_status === 'failed';
    }

    /**
     * Mark upload as started.
     */
    public function markAsStarted(): void
    {
        $this->update([
            'upload_status' => 'uploading',
            'started_at' => now(),
        ]);
    }

    /**
     * Mark upload as processing.
     */
    public function markAsProcessing(): void
    {
        $this->update([
            'upload_status' => 'processing',
        ]);
    }

    /**
     * Mark upload as completed.
     */
    public function markAsCompleted(string $youtubeVideoId): void
    {
        $this->update([
            'upload_status' => 'completed',
            'youtube_video_id' => $youtubeVideoId,
            'completed_at' => now(),
            'progress' => 100,
        ]);
    }

    /**
     * Mark upload as failed.
     */
    public function markAsFailed(string $errorMessage): void
    {
        $this->update([
            'upload_status' => 'failed',
            'error_message' => $errorMessage,
            'completed_at' => now(),
        ]);
    }

    /**
     * Update upload progress.
     */
    public function updateProgress(int $progress): void
    {
        $this->update([
            'progress' => min(100, max(0, $progress)),
        ]);
    }

    /**
     * Scope a query to only include pending uploads.
     */
    public function scopePending($query)
    {
        return $query->where('upload_status', 'pending');
    }

    /**
     * Scope a query to only include uploading uploads.
     */
    public function scopeUploading($query)
    {
        return $query->where('upload_status', 'uploading');
    }

    /**
     * Scope a query to only include processing uploads.
     */
    public function scopeProcessing($query)
    {
        return $query->where('upload_status', 'processing');
    }

    /**
     * Scope a query to only include completed uploads.
     */
    public function scopeCompleted($query)
    {
        return $query->where('upload_status', 'completed');
    }

    /**
     * Scope a query to only include failed uploads.
     */
    public function scopeFailed($query)
    {
        return $query->where('upload_status', 'failed');
    }

    /**
     * Scope a query to only include uploads for a specific user.
     */
    public function scopeForUser($query, int $userId)
    {
        return $query->where('user_id', $userId);
    }
}
