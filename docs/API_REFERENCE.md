# Service API Reference

## Table of Contents
- [Authentication](#authentication)
- [YouTube Facade Methods](#youtube-facade-methods)
- [Video Operations](#video-operations)
- [Channel Operations](#channel-operations)
- [Token Management](#token-management)
- [Events](#events)
- [Exceptions](#exceptions)

## Authentication

The package provides a simple authorization page and OAuth routes for connecting YouTube channels.

### GET /{auth_page_path}
Authorization page (default: `/youtube-authorize`)

Shows the current connection status and provides an authorization button. The page:
- Checks if Google OAuth credentials are configured
- Displays connected channel information (if any)
- Provides an "Authorize" button to initiate OAuth flow
- Shows token expiration status
- Automatically refreshes expired tokens

**Configuration:**
```env
YOUTUBE_AUTH_PAGE_PATH=youtube-authorize
```

### GET /youtube/auth
Initiates OAuth flow by redirecting to Google.

**Query Parameters:**
- `return_url` (optional): URL to return to after authentication

**Response:**
- 302 Redirect to Google OAuth

### GET /youtube/callback
Handles OAuth callback from Google.

**Query Parameters:**
- `code`: Authorization code from Google
- `state`: CSRF protection state

**Response:**
- 302 Redirect to authorization page with success/error message
- 403 if state mismatch

### POST /youtube/revoke
Revokes the current YouTube access token.

**Response:**
- 302 Redirect to authorization page with success message

## YouTube Facade Methods

The package provides a fluent API through the `YouTube` facade. All video and channel operations are performed through the service.

### Setting Context

```php
use EkstreMedia\LaravelYouTube\Facades\YouTube;

// Use default (most recent) active token
YouTube::usingDefault();

// Use token for specific channel
YouTube::forChannel('UCxxxxxxxxxx');

// Use token for specific user (legacy/multi-user support)
YouTube::forUser($userId, $channelId = null);

// Use specific token instance
YouTube::withToken($token);
```

## Video Operations

All video operations are performed through the YouTube service facade.

### Upload Video

```php
use EkstreMedia\LaravelYouTube\Facades\YouTube;

$video = YouTube::usingDefault()->uploadVideo(
    $file, // File path, UploadedFile, or resource
    [
        'title' => 'Video Title',
        'description' => 'Video description',
        'tags' => ['tag1', 'tag2'],
        'category_id' => '22',
        'privacy_status' => 'private', // private, unlisted, public
        'made_for_kids' => false,
        'embeddable' => true,
        'license' => 'youtube', // youtube or creativeCommon
    ],
    [
        'chunk_size' => 10 * 1024 * 1024, // Optional: 10MB chunks
        'progress_callback' => function ($uploaded, $total) {
            // Track upload progress
        }
    ]
);

// Returns YouTubeVideo model
echo $video->video_id; // YouTube video ID
echo $video->watch_url; // https://www.youtube.com/watch?v=...
```

### Get Videos

```php
// Get videos from channel
$videos = YouTube::usingDefault()->getVideos([
    'maxResults' => 50,
    'order' => 'date', // date, rating, relevance, title, viewCount
]);

// Videos are stored in database as YouTubeVideo models
foreach ($videos as $video) {
    echo "{$video->title} - {$video->view_count} views\n";
}
```

### Get Single Video

```php
$video = YouTube::usingDefault()->getVideo('dQw4w9WgXcQ', [
    'snippet',
    'contentDetails',
    'statistics',
    'status',
]);

// Returns YouTubeVideo model with full details
echo $video->title;
echo $video->view_count;
echo $video->duration;
```

### Update Video

```php
$updated = YouTube::usingDefault()->updateVideo('video-id', [
    'title' => 'Updated Title',
    'description' => 'Updated description',
    'tags' => ['new', 'tags'],
    'category_id' => '24',
    'privacy_status' => 'public',
]);
```

### Delete Video

```php
YouTube::usingDefault()->deleteVideo('video-id');
```

### Set Thumbnail

```php
YouTube::usingDefault()->setThumbnail(
    'video-id',
    $thumbnailFile // File path, UploadedFile, or resource
);
```

## Channel Operations

### Get Channel Information

```php
$channel = YouTube::usingDefault()->getChannel([
    'snippet',
    'contentDetails',
    'statistics',
    'brandingSettings',
]);

// Returns array with channel data
echo $channel['title'];
echo $channel['subscriberCount'];
echo $channel['videoCount'];
```

## Token Management

### YouTubeToken Model

```php
use EkstreMedia\LaravelYouTube\Models\YouTubeToken;

// Get user's active tokens
$tokens = YouTubeToken::where('user_id', $userId)
    ->active()
    ->get();

// Check token expiration
$token = YouTubeToken::find($id);
if ($token->is_expired) {
    // Refresh token
}
if ($token->expires_soon) {
    // Will expire in next 5 minutes
}

// Token scopes
if ($token->hasScope('youtube.upload')) {
    // Can upload videos
}

// Mark token as refreshed
$token->markAsRefreshed();

// Deactivate token
$token->deactivate();

// Activate token
$token->activate();
```

### TokenManager Service

```php
use EkstreMedia\LaravelYouTube\Services\TokenManager;

$manager = app(TokenManager::class);

// Store new token
$token = $manager->storeToken(
    $tokenData,
    $channelInfo,
    $userId
);

// Get active token
$token = $manager->getActiveToken($userId, $channelId);

// Get all user tokens
$tokens = $manager->getUserTokens($userId);

// Check if refresh needed
if ($manager->needsRefresh($token)) {
    // Refresh token
}

// Update token
$manager->updateToken($token, $newTokenData);

// Mark as failed
$manager->markTokenFailed($token, 'Error message');

// Deactivate
$manager->deactivateToken($token);

// Clean up old tokens
$deleted = $manager->deleteExpiredTokens(30); // days
```

## Events

### VideoUploaded

Fired when a video is successfully uploaded.

```php
namespace EkstreMedia\LaravelYouTube\Events;

class VideoUploaded
{
    public function __construct(
        public YouTubeVideo $video,
        public ?User $user = null
    ) {}
}
```

### TokenRefreshed

Fired when a token is refreshed.

```php
namespace EkstreMedia\LaravelYouTube\Events;

class TokenRefreshed
{
    public function __construct(
        public YouTubeToken $token
    ) {}
}
```

### UploadFailed

Fired when an upload fails.

```php
namespace EkstreMedia\LaravelYouTube\Events;

class UploadFailed
{
    public function __construct(
        public string $videoPath,
        public array $metadata,
        public string $error,
        public ?User $user = null
    ) {}
}
```

### Listening to Events

```php
// app/Providers/EventServiceProvider.php
protected $listen = [
    \EkstreMedia\LaravelYouTube\Events\VideoUploaded::class => [
        \App\Listeners\ProcessUploadedVideo::class,
        \App\Listeners\NotifyUserOfUpload::class,
    ],
    \EkstreMedia\LaravelYouTube\Events\TokenRefreshed::class => [
        \App\Listeners\LogTokenRefresh::class,
    ],
    \EkstreMedia\LaravelYouTube\Events\UploadFailed::class => [
        \App\Listeners\NotifyUploadFailure::class,
        \App\Listeners\RetryUpload::class,
    ],
];
```

## Exceptions

### YouTubeException

Base exception for all YouTube-related errors.

```php
use EkstreMedia\LaravelYouTube\Exceptions\YouTubeException;

try {
    // YouTube operation
} catch (YouTubeException $e) {
    $errorCode = $e->getYouTubeError();
    $errorReason = $e->getYouTubeReason();
    $message = $e->getMessage();
}
```

### YouTubeAuthException

Authentication and authorization errors.

```php
use EkstreMedia\LaravelYouTube\Exceptions\YouTubeAuthException;

try {
    // Auth operation
} catch (YouTubeAuthException $e) {
    // Redirect to re-authenticate
    return redirect()->route('youtube.auth');
}
```

### UploadException

Video upload failures.

```php
use EkstreMedia\LaravelYouTube\Exceptions\UploadException;

try {
    $video = $youtube->uploadVideo($file, $metadata);
} catch (UploadException $e) {
    if ($e->getCode() === UploadException::FILE_TOO_LARGE) {
        // Handle file size error
    }
}
```

Error codes:
- `FILE_TOO_LARGE = 413`
- `INVALID_FORMAT = 415`
- `NETWORK_ERROR = 500`
- `TIMEOUT = 504`

### TokenException

Token management errors.

```php
use EkstreMedia\LaravelYouTube\Exceptions\TokenException;

try {
    $token = $tokenManager->getActiveToken($userId);
} catch (TokenException $e) {
    if ($e->getCode() === TokenException::NO_ACTIVE_TOKEN) {
        // No active token found
    }
}
```

Error codes:
- `NO_ACTIVE_TOKEN = 404`
- `TOKEN_EXPIRED = 401`
- `REFRESH_FAILED = 500`

### QuotaExceededException

YouTube API quota limit reached.

```php
use EkstreMedia\LaravelYouTube\Exceptions\QuotaExceededException;

try {
    // API operation
} catch (QuotaExceededException $e) {
    $resetTime = $e->getResetTime();
    Log::error("Quota exceeded, resets at: " . $resetTime);

    // Queue for later
    dispatch(new RetryOperation())->delay($resetTime);
}
```

## Rate Limiting

### Configuration

```php
// config/youtube.php
'rate_limiting' => [
    'enabled' => true,
    'max_requests_per_minute' => 60,
    'max_requests_per_hour' => 3000,
],
```

The YouTube service respects these rate limits when making API calls.

## Configuration Reference

### Full Configuration

```php
return [
    // OAuth2 credentials
    'credentials' => [
        'client_id' => env('YOUTUBE_CLIENT_ID'),
        'client_secret' => env('YOUTUBE_CLIENT_SECRET'),
        'redirect_uri' => env('YOUTUBE_REDIRECT_URI', '/youtube/callback'),
    ],

    // API scopes
    'scopes' => [
        'https://www.googleapis.com/auth/youtube',
        'https://www.googleapis.com/auth/youtube.upload',
        'https://www.googleapis.com/auth/youtube.readonly',
        'https://www.googleapis.com/auth/youtube.force-ssl',
        'https://www.googleapis.com/auth/youtubepartner',
        'https://www.googleapis.com/auth/youtubepartner-channel-audit',
    ],

    // Routes settings
    'routes' => [
        'auth_page' => [
            'path' => env('YOUTUBE_AUTH_PAGE_PATH', 'youtube-authorize'),
            'middleware' => ['web'],
        ],
    ],

    // Token storage
    'token' => [
        'driver' => 'database',
        'table' => 'youtube_tokens',
        'cache_key' => 'youtube.token.',
        'cache_ttl' => env('YOUTUBE_TOKEN_CACHE_TTL', 3600),
    ],

    // Upload settings
    'upload' => [
        'chunk_size' => env('YOUTUBE_UPLOAD_CHUNK_SIZE', 1024 * 1024),
        'timeout' => env('YOUTUBE_UPLOAD_TIMEOUT', 3600),
        'max_file_size' => env('YOUTUBE_UPLOAD_MAX_SIZE', 128 * 1024 * 1024 * 1024),
        'temp_path' => env('YOUTUBE_UPLOAD_TEMP_PATH', storage_path('app/youtube-uploads')),
    ],

    // Default values
    'defaults' => [
        'privacy_status' => env('YOUTUBE_DEFAULT_PRIVACY', 'private'),
        'category_id' => env('YOUTUBE_DEFAULT_CATEGORY', '22'),
        'language' => env('YOUTUBE_DEFAULT_LANGUAGE', 'en'),
    ],

    // Rate limiting
    'rate_limiting' => [
        'enabled' => env('YOUTUBE_RATE_LIMIT_ENABLED', true),
        'max_requests_per_minute' => env('YOUTUBE_RATE_LIMIT_PER_MINUTE', 60),
        'max_requests_per_hour' => env('YOUTUBE_RATE_LIMIT_PER_HOUR', 3000),
    ],

    // Logging
    'logging' => [
        'enabled' => env('YOUTUBE_LOGGING_ENABLED', true),
        'channel' => env('YOUTUBE_LOGGING_CHANNEL', 'youtube'),
        'level' => env('YOUTUBE_LOGGING_LEVEL', 'info'),
    ],
];
```

## YouTube Category IDs

| ID | Category |
|----|----------|
| 1  | Film & Animation |
| 2  | Autos & Vehicles |
| 10 | Music |
| 15 | Pets & Animals |
| 17 | Sports |
| 18 | Short Movies |
| 19 | Travel & Events |
| 20 | Gaming |
| 21 | Videoblogging |
| 22 | People & Blogs |
| 23 | Comedy |
| 24 | Entertainment |
| 25 | News & Politics |
| 26 | Howto & Style |
| 27 | Education |
| 28 | Science & Technology |
| 29 | Nonprofits & Activism |
| 30 | Movies |
| 31 | Anime/Animation |
| 32 | Action/Adventure |
| 33 | Classics |
| 34 | Comedy |
| 35 | Documentary |
| 36 | Drama |
| 37 | Family |
| 38 | Foreign |
| 39 | Horror |
| 40 | Sci-Fi/Fantasy |
| 41 | Thriller |
| 42 | Shorts |
| 43 | Shows |
| 44 | Trailers |

## Privacy Status Values

- `private` - Video is private, only viewable by owner
- `unlisted` - Video is unlisted, viewable with link
- `public` - Video is public, searchable and viewable by all

## Best Practices

### Single-User Mode

The package is designed for single-user/single-channel applications. For most use cases:

```php
// Always use usingDefault() for simplicity
$video = YouTube::usingDefault()->uploadVideo($file, $metadata);

// The service automatically:
// - Uses the most recent active token
// - Refreshes expired tokens
// - Handles errors gracefully
```

### Multi-User Support (Advanced)

If you need multi-user support, you can still use the legacy methods:

```php
// Store token with user_id during OAuth
$tokenManager->storeToken($tokenData, $channelInfo, $userId);

// Use user-specific token
YouTube::forUser($userId)->uploadVideo($file, $metadata);
```

### Error Handling

Always wrap YouTube operations in try-catch blocks:

```php
use Ekstremedia\LaravelYouTube\Exceptions\{
    YouTubeException,
    UploadException,
    TokenException,
    QuotaExceededException
};

try {
    $video = YouTube::usingDefault()->uploadVideo($file, $metadata);
} catch (QuotaExceededException $e) {
    // Retry after quota resets
} catch (UploadException $e) {
    // Handle upload failure
} catch (TokenException $e) {
    // Redirect to authorization page
} catch (YouTubeException $e) {
    // Handle general errors
}
```