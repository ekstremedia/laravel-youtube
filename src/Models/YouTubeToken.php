<?php

namespace EkstreMedia\LaravelYouTube\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class YouTubeToken extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'youtube_tokens';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'user_id',
        'channel_id',
        'channel_title',
        'channel_handle',
        'channel_thumbnail',
        'access_token',
        'refresh_token',
        'token_type',
        'expires_in',
        'expires_at',
        'scopes',
        'channel_metadata',
        'is_active',
        'last_refreshed_at',
        'refresh_count',
        'error',
        'error_at',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'scopes' => 'array',
        'channel_metadata' => 'array',
        'is_active' => 'boolean',
        'expires_at' => 'datetime',
        'last_refreshed_at' => 'datetime',
        'error_at' => 'datetime',
        'refresh_count' => 'integer',
        'expires_in' => 'integer',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'access_token',
        'refresh_token',
    ];

    /**
     * Get the user that owns the token.
     */
    public function user(): BelongsTo
    {
        // Use configured user model or fall back to Illuminate's default
        $userModel = config('auth.providers.users.model', \Illuminate\Foundation\Auth\User::class);

        return $this->belongsTo($userModel);
    }

    /**
     * Get the videos uploaded with this token.
     */
    public function videos(): HasMany
    {
        return $this->hasMany(YouTubeVideo::class, 'token_id');
    }

    /**
     * Check if token is expired.
     */
    protected function isExpired(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->expires_at ? $this->expires_at->isPast() : true,
        );
    }

    /**
     * Check if token will expire soon (within 5 minutes).
     */
    protected function expiresSoon(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->expires_at ? $this->expires_at->subMinutes(5)->isPast() : true,
        );
    }

    /**
     * Get the time until expiration.
     */
    protected function expiresInMinutes(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->expires_at ? $this->expires_at->diffInMinutes(Carbon::now(), false) : 0,
        );
    }

    /**
     * Check if token has error.
     */
    protected function hasError(): Attribute
    {
        return Attribute::make(
            get: fn () => ! empty($this->error),
        );
    }

    /**
     * Get formatted channel info.
     */
    protected function channelInfo(): Attribute
    {
        return Attribute::make(
            get: fn () => [
                'id' => $this->channel_id,
                'title' => $this->channel_title,
                'handle' => $this->channel_handle,
                'thumbnail' => $this->channel_thumbnail,
                'metadata' => $this->channel_metadata,
            ],
        );
    }

    /**
     * Scope a query to only include active tokens.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope a query to only include expired tokens.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeExpired($query)
    {
        return $query->where('expires_at', '<=', Carbon::now());
    }

    /**
     * Scope a query to only include tokens expiring soon.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @param  int  $minutes  Minutes until expiration
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeExpiringSoon($query, int $minutes = 5)
    {
        return $query->where('expires_at', '<=', Carbon::now()->addMinutes($minutes));
    }

    /**
     * Scope a query to only include tokens with errors.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     */
    public function scopeWithError($query): \Illuminate\Database\Eloquent\Builder
    {
        return $query->whereNotNull('error');
    }

    /**
     * Scope a query to only include tokens for a specific channel.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeForChannel($query, string $channelId)
    {
        return $query->where('channel_id', $channelId);
    }

    /**
     * Scope a query to only include tokens for a specific user.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeForUser($query, int $userId)
    {
        return $query->where('user_id', $userId);
    }

    /**
     * Check if token has a specific scope.
     *
     * @param  string  $scope  The scope to check for (can be partial, e.g., 'youtube.upload')
     * @return bool
     */
    public function hasScope(string $scope): bool
    {
        if (!$this->scopes || !is_array($this->scopes)) {
            return false;
        }

        foreach ($this->scopes as $tokenScope) {
            // Check if the scope matches or contains the requested scope
            if (str_contains($tokenScope, $scope) || str_contains($tokenScope, "auth/{$scope}")) {
                return true;
            }
        }

        return false;
    }

    /**
     * Mark token as refreshed.
     */
    public function markAsRefreshed(): void
    {
        $this->update([
            'last_refreshed_at' => Carbon::now(),
            'refresh_count' => $this->refresh_count + 1,
            'error' => null,
            'error_at' => null,
        ]);
    }

    /**
     * Mark token as failed.
     *
     * @param  string  $error  Error message
     */
    public function markAsFailed(string $error): void
    {
        $this->update([
            'error' => $error,
            'error_at' => Carbon::now(),
            'is_active' => false,
        ]);
    }

    /**
     * Deactivate the token.
     */
    public function deactivate(): void
    {
        $this->update(['is_active' => false]);
    }

    /**
     * Activate the token.
     */
    public function activate(): void
    {
        $this->update([
            'is_active' => true,
            'error' => null,
            'error_at' => null,
        ]);
    }

    /**
     * Create a new factory instance for the model.
     *
     * @return \Illuminate\Database\Eloquent\Factories\Factory
     */
    protected static function newFactory()
    {
        return \EkstreMedia\LaravelYouTube\Database\Factories\YouTubeTokenFactory::new();
    }
}
