# API Reference

## Table of Contents
- [Authentication](#authentication)
- [Video Operations](#video-operations)
- [Upload Operations](#upload-operations)
- [Channel Operations](#channel-operations)
- [Token Management](#token-management)
- [Admin Panel](#admin-panel)
- [Queue Jobs](#queue-jobs)
- [Events](#events)
- [Exceptions](#exceptions)

## Authentication

### GET /youtube/auth
Initiates OAuth flow by redirecting to Google.

**Query Parameters:**
- `return_url` (optional): URL to return to after authentication
- `channel_id` (optional): Pre-select specific channel

**Response:**
- 302 Redirect to Google OAuth

### GET /youtube/callback
Handles OAuth callback from Google.

**Query Parameters:**
- `code`: Authorization code from Google
- `state`: CSRF protection state

**Response:**
- 302 Redirect to return_url or dashboard
- 403 if state mismatch

### POST /youtube/revoke
Revokes the current user's YouTube access.

**Headers:**
- `Authorization: Bearer {token}`

**Response:**
```json
{
  "message": "Token revoked successfully"
}
```

### GET /youtube/status
Check current authentication status.

**Headers:**
- `Authorization: Bearer {token}`

**Response:**
```json
{
  "authenticated": true,
  "channels": [
    {
      "channel_id": "UC123456",
      "channel_title": "My Channel",
      "is_active": true,
      "expires_at": "2024-12-31T23:59:59Z"
    }
  ]
}
```

## Video Operations

### GET /api/youtube/videos
List user's YouTube videos.

**Headers:**
- `Authorization: Bearer {token}`

**Query Parameters:**
- `page` (integer): Page number (default: 1)
- `per_page` (integer): Items per page (default: 20, max: 50)
- `channel_id` (string): Filter by channel
- `privacy_status` (string): Filter by privacy (public, private, unlisted)
- `search` (string): Search in title/description
- `order` (string): Sort order (date, rating, relevance, title, viewCount)

**Response:**
```json
{
  "data": [
    {
      "id": 1,
      "video_id": "dQw4w9WgXcQ",
      "title": "Video Title",
      "description": "Video description",
      "privacy_status": "public",
      "view_count": 1000000,
      "like_count": 50000,
      "comment_count": 5000,
      "duration": "PT3M45S",
      "thumbnail": "https://i.ytimg.com/vi/dQw4w9WgXcQ/maxresdefault.jpg",
      "watch_url": "https://www.youtube.com/watch?v=dQw4w9WgXcQ",
      "embed_url": "https://www.youtube.com/embed/dQw4w9WgXcQ",
      "published_at": "2024-01-15T10:00:00Z",
      "created_at": "2024-01-15T10:00:00Z"
    }
  ],
  "meta": {
    "current_page": 1,
    "from": 1,
    "last_page": 5,
    "per_page": 20,
    "to": 20,
    "total": 100
  },
  "links": {
    "first": "/api/youtube/videos?page=1",
    "last": "/api/youtube/videos?page=5",
    "prev": null,
    "next": "/api/youtube/videos?page=2"
  }
}
```

### GET /api/youtube/videos/{video_id}
Get details of a specific video.

**Headers:**
- `Authorization: Bearer {token}`

**Response:**
```json
{
  "data": {
    "id": 1,
    "video_id": "dQw4w9WgXcQ",
    "channel_id": "UC123456",
    "title": "Video Title",
    "description": "Full video description",
    "tags": ["tag1", "tag2", "tag3"],
    "category_id": "22",
    "privacy_status": "public",
    "license": "youtube",
    "embeddable": true,
    "made_for_kids": false,
    "default_language": "en",
    "default_audio_language": "en",
    "duration": "PT3M45S",
    "definition": "hd",
    "caption": "false",
    "thumbnails": {
      "default": "https://i.ytimg.com/vi/dQw4w9WgXcQ/default.jpg",
      "medium": "https://i.ytimg.com/vi/dQw4w9WgXcQ/mqdefault.jpg",
      "high": "https://i.ytimg.com/vi/dQw4w9WgXcQ/hqdefault.jpg",
      "standard": "https://i.ytimg.com/vi/dQw4w9WgXcQ/sddefault.jpg",
      "maxres": "https://i.ytimg.com/vi/dQw4w9WgXcQ/maxresdefault.jpg"
    },
    "statistics": {
      "view_count": 1000000,
      "like_count": 50000,
      "dislike_count": 500,
      "comment_count": 5000
    },
    "upload_status": "processed",
    "processing_status": "succeeded",
    "published_at": "2024-01-15T10:00:00Z",
    "created_at": "2024-01-15T10:00:00Z",
    "updated_at": "2024-01-15T10:00:00Z"
  }
}
```

### PUT /api/youtube/videos/{video_id}
Update video metadata.

**Headers:**
- `Authorization: Bearer {token}`
- `Content-Type: application/json`

**Request Body:**
```json
{
  "title": "Updated Title",
  "description": "Updated description",
  "tags": ["new", "tags"],
  "category_id": "24",
  "privacy_status": "unlisted",
  "embeddable": true,
  "license": "creativeCommon",
  "made_for_kids": false
}
```

**Response:**
```json
{
  "message": "Video updated successfully",
  "data": {
    "video_id": "dQw4w9WgXcQ",
    "title": "Updated Title"
  }
}
```

### DELETE /api/youtube/videos/{video_id}
Delete a video from YouTube.

**Headers:**
- `Authorization: Bearer {token}`

**Response:**
```json
{
  "message": "Video deleted successfully"
}
```

### POST /api/youtube/videos/{video_id}/thumbnail
Set custom thumbnail for a video.

**Headers:**
- `Authorization: Bearer {token}`
- `Content-Type: multipart/form-data`

**Request Body:**
- `thumbnail` (file): Image file (JPEG/PNG, max 2MB, min 1280x720)

**Response:**
```json
{
  "message": "Thumbnail updated successfully",
  "thumbnail_url": "https://i.ytimg.com/vi/dQw4w9WgXcQ/maxresdefault.jpg"
}
```

## Upload Operations

### POST /api/youtube/upload
Upload a new video to YouTube.

**Headers:**
- `Authorization: Bearer {token}`
- `Content-Type: multipart/form-data`

**Request Body:**
- `video` (file, required): Video file (mp4, avi, mov, wmv, flv, webm)
- `title` (string, required): Video title (max 100 chars)
- `description` (string): Video description (max 5000 chars)
- `tags` (array): Video tags
- `category_id` (string): YouTube category ID
- `privacy_status` (string): private, unlisted, or public
- `thumbnail` (file): Custom thumbnail
- `made_for_kids` (boolean): Kids content flag
- `embeddable` (boolean): Allow embedding
- `license` (string): youtube or creativeCommon
- `notify_url` (string): Webhook URL for completion

**Response:**
```json
{
  "message": "Upload started",
  "upload_id": "upload-abc123",
  "status_url": "/api/youtube/upload/status/upload-abc123"
}
```

### GET /api/youtube/upload/status/{upload_id}
Get upload progress status.

**Headers:**
- `Authorization: Bearer {token}`

**Response:**
```json
{
  "upload_id": "upload-abc123",
  "status": "processing",
  "progress": 45,
  "bytes_uploaded": 47185920,
  "total_bytes": 104857600,
  "video_id": null,
  "message": "Uploading..."
}
```

Status values:
- `pending`: Upload queued
- `processing`: Upload in progress
- `completed`: Upload successful
- `failed`: Upload failed

## Channel Operations

### GET /api/youtube/channel
Get authenticated user's channel information.

**Headers:**
- `Authorization: Bearer {token}`

**Query Parameters:**
- `channel_id` (string): Specific channel if user has multiple

**Response:**
```json
{
  "data": {
    "id": "UC123456",
    "title": "My Channel",
    "handle": "@mychannel",
    "description": "Channel description",
    "thumbnail": "https://yt3.ggpht.com/xxx",
    "banner": "https://yt3.ggpht.com/xxx",
    "country": "US",
    "published_at": "2020-01-15T10:00:00Z",
    "statistics": {
      "view_count": "10000000",
      "subscriber_count": "100000",
      "video_count": "500"
    },
    "branding": {
      "keywords": "channel keywords",
      "unsubscribed_trailer": "dQw4w9WgXcQ",
      "featured_channels": ["UCxxxxxx", "UCyyyyyy"]
    }
  }
}
```

### GET /api/youtube/channel/videos
List videos from the authenticated channel.

**Headers:**
- `Authorization: Bearer {token}`

**Query Parameters:**
- `page` (integer): Page number
- `per_page` (integer): Items per page
- `order` (string): Sort order
- `search` (string): Search query

**Response:**
Same as `/api/youtube/videos`

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

## Admin Panel

### Routes

All admin routes are prefixed with `/youtube-admin` (configurable).

```
GET  /youtube-admin                     - Dashboard
GET  /youtube-admin/tokens               - Token management
GET  /youtube-admin/videos               - Video listing
GET  /youtube-admin/videos/{id}          - Video details
GET  /youtube-admin/upload               - Upload interface
POST /youtube-admin/upload               - Process upload
GET  /youtube-admin/channels             - Channel overview
POST /youtube-admin/channels/sync        - Sync channel data
```

### Middleware

Admin panel uses these middleware by default:
- `web`
- `auth`
- Custom middleware can be added in config

```php
// config/youtube.php
'admin' => [
    'enabled' => true,
    'prefix' => 'youtube-admin',
    'middleware' => ['web'],
    'auth_middleware' => ['auth', 'admin'],
],
```

## Queue Jobs

### UploadVideoJob

```php
use EkstreMedia\LaravelYouTube\Jobs\UploadVideoJob;

UploadVideoJob::dispatch(
    userId: 1,
    videoPath: '/path/to/video.mp4',
    metadata: [
        'title' => 'Video Title',
        'description' => 'Description',
        'tags' => ['tag1', 'tag2'],
        'privacy_status' => 'private',
        'category_id' => '22',
    ],
    channelId: 'UC123456',
    thumbnailPath: '/path/to/thumb.jpg',
    notifyUrl: 'https://example.com/webhook'
)->onQueue('media');
```

Job properties:
- `$tries = 3` - Retry attempts
- `$timeout = 7200` - 2 hour timeout
- `$maxExceptions = 3` - Max unhandled exceptions
- Exponential backoff: 1min, 5min, 15min

### RefreshTokensJob

Automatically scheduled hourly to refresh expiring tokens.

```php
use EkstreMedia\LaravelYouTube\Jobs\RefreshTokensJob;

RefreshTokensJob::dispatch();

// Or refresh specific user's tokens
RefreshTokensJob::dispatch($userId);
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

### Custom Rate Limits

```php
// In RouteServiceProvider
RateLimiter::for('youtube-api', function (Request $request) {
    return Limit::perMinute(60)->by($request->user()?->id ?: $request->ip());
});

// Apply to routes
Route::middleware(['throttle:youtube-api'])->group(function () {
    // YouTube API routes
});
```

## Helper Functions

### YouTube Facade Methods

```php
use EkstreMedia\LaravelYouTube\Facades\YouTube;

// Set user context
YouTube::forUser($userId, $channelId = null);

// Use specific token
YouTube::withToken(YouTubeToken $token);

// Get auth URL
YouTube::getAuthUrl($state = null);

// Video operations
YouTube::uploadVideo($video, array $metadata, array $options = []);
YouTube::getVideos(array $options = []);
YouTube::getVideo($videoId, array $parts = []);
YouTube::updateVideo($videoId, array $metadata);
YouTube::deleteVideo($videoId);
YouTube::setThumbnail($videoId, $thumbnail);

// Channel operations
YouTube::getChannel(array $parts = []);
YouTube::getChannelVideos($channelId, array $options = []);
```

## Validation Rules

### Custom Validation Rules

```php
use EkstreMedia\LaravelYouTube\Rules\YouTubeVideoId;
use EkstreMedia\LaravelYouTube\Rules\YouTubeChannelId;

$request->validate([
    'video_id' => ['required', new YouTubeVideoId()],
    'channel_id' => ['required', new YouTubeChannelId()],
]);
```

### Upload Validation

```php
$request->validate([
    'video' => 'required|file|mimes:mp4,avi,mov,wmv,flv,webm|max:137438953472',
    'title' => 'required|string|max:100',
    'description' => 'nullable|string|max:5000',
    'tags' => 'nullable|array|max:500',
    'tags.*' => 'string|max:30',
    'category_id' => 'nullable|in:1,2,10,15,17,19,20,21,22,23,24,25,26,27,28',
    'privacy_status' => 'nullable|in:private,unlisted,public',
    'thumbnail' => 'nullable|image|mimes:jpeg,jpg,png|max:2048|dimensions:min_width=1280,min_height=720',
]);
```

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

    // Admin panel settings
    'admin' => [
        'enabled' => env('YOUTUBE_ADMIN_ENABLED', true),
        'prefix' => env('YOUTUBE_ADMIN_PREFIX', 'youtube-admin'),
        'middleware' => ['web'],
        'auth_middleware' => ['auth'],
    ],

    // API routes settings
    'routes' => [
        'api' => [
            'enabled' => env('YOUTUBE_API_ENABLED', true),
            'prefix' => env('YOUTUBE_API_PREFIX', 'youtube'),
            'middleware' => ['api'],
            'api_middleware' => ['auth:sanctum', 'throttle:60,1'],
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

## Response Codes

### Success Codes
- `200` - OK: Request successful
- `201` - Created: Resource created successfully
- `202` - Accepted: Request accepted for processing
- `204` - No Content: Request successful, no content to return

### Client Error Codes
- `400` - Bad Request: Invalid request data
- `401` - Unauthorized: Authentication required
- `403` - Forbidden: Access denied
- `404` - Not Found: Resource not found
- `409` - Conflict: Resource conflict
- `413` - Payload Too Large: File too large
- `415` - Unsupported Media Type: Invalid file format
- `422` - Unprocessable Entity: Validation failed
- `429` - Too Many Requests: Rate limit exceeded

### Server Error Codes
- `500` - Internal Server Error: Server error
- `502` - Bad Gateway: YouTube API error
- `503` - Service Unavailable: Service temporarily down
- `504` - Gateway Timeout: Request timeout