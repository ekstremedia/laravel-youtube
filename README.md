# Laravel YouTube Package

[![Latest Version on Packagist](https://img.shields.io/packagist/v/ekstremedia/laravel-youtube.svg?style=flat-square)](https://packagist.org/packages/ekstremedia/laravel-youtube)
[![Tests](https://github.com/ekstremedia/laravel-youtube/actions/workflows/tests.yml/badge.svg)](https://github.com/ekstremedia/laravel-youtube/actions/workflows/tests.yml)
[![Total Downloads](https://img.shields.io/packagist/dt/ekstremedia/laravel-youtube.svg?style=flat-square)](https://packagist.org/packages/ekstremedia/laravel-youtube)
[![License](https://img.shields.io/packagist/l/ekstremedia/laravel-youtube.svg?style=flat-square)](https://packagist.org/packages/ekstremedia/laravel-youtube)
![Laravel](https://img.shields.io/badge/Laravel-11%2B%20%7C%2012%2B-red)
![PHP](https://img.shields.io/badge/PHP-8.2%2B-blue)

A comprehensive Laravel package for YouTube API v3 integration with OAuth2 authentication, automatic token refresh, upload management, and extensive API coverage. Perfect for content creators, video platforms, and automated video upload systems.

## 🚀 Features

### Core Features
- 🔐 **OAuth2 Authentication** - Secure authentication with automatic token refresh
- 📹 **Video Management** - Complete CRUD operations for videos
- 📤 **Advanced Upload** - Chunked uploads with progress tracking
- 📺 **Channel Management** - Multi-channel support per user
- 🎨 **Thumbnail Management** - Custom thumbnail upload support
- 🔄 **Queue Support** - Background video uploads via Laravel jobs
- 🎯 **Rate Limiting** - Built-in rate limiting to respect API quotas
- 📊 **Statistics Tracking** - View counts, likes, and engagement metrics
- 🌐 **Webhook Support** - Upload completion notifications

### Special Features
- 🥧 **Raspberry Pi Integration** - Optimized for automated uploads from IoT devices
- 🔄 **Auto Token Refresh** - Never worry about expired tokens
- 💾 **Database Storage** - Track all uploads and video metadata
- 🔒 **Encrypted Token Storage** - Secure storage of OAuth tokens
- 📝 **Comprehensive Logging** - Detailed logging for debugging
- 🎨 **Admin Panel** - Optional web interface for management
- 🧪 **Test Coverage** - Extensive test suite with Pest

## 📋 Requirements

- PHP 8.2 or higher
- Laravel 11.0 or 12.0
- Google API credentials (OAuth 2.0 Client ID)
- Composer
- Database (MySQL, PostgreSQL, SQLite)

## 📦 Installation

### Step 1: Install via Composer

```bash
composer require ekstremedia/laravel-youtube
```

### Step 2: Publish Configuration and Migrations

```bash
# Publish configuration
php artisan vendor:publish --provider="EkstreMedia\LaravelYouTube\YouTubeServiceProvider" --tag="youtube-config"

# Publish migrations
php artisan vendor:publish --provider="EkstreMedia\LaravelYouTube\YouTubeServiceProvider" --tag="youtube-migrations"

# Optional: Publish views for admin panel
php artisan vendor:publish --provider="EkstreMedia\LaravelYouTube\YouTubeServiceProvider" --tag="youtube-views"

# Optional: Publish assets for admin panel
php artisan vendor:publish --provider="EkstreMedia\LaravelYouTube\YouTubeServiceProvider" --tag="youtube-assets"
```

### Step 3: Configure Environment Variables

Add the following to your `.env` file:

```env
# Required: YouTube OAuth2 Credentials
YOUTUBE_CLIENT_ID=your-client-id-here
YOUTUBE_CLIENT_SECRET=your-client-secret-here
YOUTUBE_REDIRECT_URI=https://yourdomain.com/youtube/callback

# Optional: Admin Panel
YOUTUBE_ADMIN_ENABLED=true
YOUTUBE_ADMIN_PREFIX=youtube-admin

# Optional: Authentication Page
YOUTUBE_AUTH_PAGE_ENABLED=true
YOUTUBE_AUTH_PAGE_PATH=youtube-authenticate

# Optional: Upload Settings
YOUTUBE_UPLOAD_CHUNK_SIZE=10485760  # 10MB chunks
YOUTUBE_UPLOAD_TIMEOUT=3600         # 1 hour
YOUTUBE_UPLOAD_MAX_SIZE=137438953472 # 128GB (YouTube's max)

# Optional: Default Settings
YOUTUBE_DEFAULT_PRIVACY=private     # private, unlisted, public
YOUTUBE_DEFAULT_CATEGORY=22         # People & Blogs
YOUTUBE_DEFAULT_LANGUAGE=en

# Optional: Rate Limiting
YOUTUBE_RATE_LIMIT_ENABLED=true
YOUTUBE_RATE_LIMIT_PER_MINUTE=60
YOUTUBE_RATE_LIMIT_PER_HOUR=3000

# Optional: Logging
YOUTUBE_LOGGING_ENABLED=true
YOUTUBE_LOGGING_CHANNEL=youtube
YOUTUBE_LOGGING_LEVEL=info
```

### Step 4: Obtain Google API Credentials

1. Go to [Google Cloud Console](https://console.cloud.google.com)
2. Create a new project or select an existing one
3. Enable the **YouTube Data API v3**
4. Create OAuth 2.0 credentials:
   - Application type: Web application
   - Add authorized redirect URI: `https://yourdomain.com/youtube/callback`
5. Copy the Client ID and Client Secret to your `.env` file

### Step 5: Run Migrations

```bash
php artisan migrate
```

## 🎯 Quick Start

### Basic Usage

```php
use EkstreMedia\LaravelYouTube\Facades\YouTube;

// Authenticate user (redirect to Google)
return redirect()->to(YouTube::getAuthUrl());

// After authentication, upload a video
$video = YouTube::forUser(auth()->id())
    ->uploadVideo(
        '/path/to/video.mp4',
        [
            'title' => 'My Amazing Video',
            'description' => 'This is a great video!',
            'tags' => ['laravel', 'youtube', 'api'],
            'category_id' => '22',
            'privacy_status' => 'public',
        ]
    );

echo "Video uploaded: " . $video->watch_url;
```

### Raspberry Pi Integration & API Usage

Perfect for automated timelapse or security camera uploads. When uploading via API without a logged-in user, use these methods:

#### Using Default Token (Without User Context)

```php
// Method 1: Use the default (most recent) active token
YouTube::usingDefault()->uploadVideo($file, $metadata);

// Method 2: Use a specific channel by ID
YouTube::forChannel('UCxxxxxxxxxx')->uploadVideo($file, $metadata);
```

#### Complete Raspberry Pi API Example

```php
// In your Pi upload endpoint
use EkstreMedia\LaravelYouTube\Jobs\UploadVideoJob;
use Ekstremedia\LaravelYouTube\Facades\YouTube;

Route::post('/api/pi/upload', function (Request $request) {
    $request->validate([
        'video' => 'required|file|mimes:mp4,avi,mov|max:5242880', // 5GB
        'camera_id' => 'required|string',
    ]);

    $file = $request->file('video');

    // Option 1: Upload immediately (synchronous)
    $video = YouTube::usingDefault()->uploadVideo($file, [
        'title' => "Pi Camera {$request->camera_id} - " . now()->format('Y-m-d H:i'),
        'description' => 'Automated timelapse from Raspberry Pi',
        'tags' => ['raspberry-pi', 'timelapse', $request->camera_id],
        'privacy_status' => 'unlisted',
    ]);

    return response()->json([
        'success' => true,
        'video_id' => $video->video_id,
        'watch_url' => $video->watch_url,
    ]);
});

// Option 2: Queue upload (recommended for large files)
Route::post('/api/pi/upload-queue', function (Request $request) {
    $request->validate([
        'video' => 'required|file|mimes:mp4,avi,mov|max:5242880',
        'camera_id' => 'required|string',
    ]);

    $path = $request->file('video')->store('pi-uploads');

    UploadVideoJob::dispatch(
        userId: null, // No user - will use default token in job
        videoPath: storage_path('app/' . $path),
        metadata: [
            'title' => "Pi Camera {$request->camera_id} - " . now()->format('Y-m-d H:i'),
            'description' => 'Automated upload from Raspberry Pi',
            'tags' => ['raspberry-pi', 'timelapse', $request->camera_id],
            'privacy_status' => 'unlisted',
        ],
        channelId: null,
        notifyUrl: 'https://yourapp.com/webhook/upload-complete'
    )->onQueue('media');

    return response()->json(['success' => true, 'message' => 'Upload queued']);
});
```

#### From Raspberry Pi (Shell Script)

```bash
#!/bin/bash
# On your Raspberry Pi

VIDEO_FILE="/home/pi/videos/timelapse_$(date +%Y%m%d_%H%M%S).mp4"
API_ENDPOINT="https://your-domain.com/api/pi/upload"
API_TOKEN="your-api-token"

# Upload to your Laravel API
curl -X POST $API_ENDPOINT \
  -H "Authorization: Bearer $API_TOKEN" \
  -F "video=@$VIDEO_FILE" \
  -F "camera_id=pi_camera_1"
```

#### One-Time Google OAuth Setup

Since API uploads don't have a logged-in user, you need one YouTube token in your database:

```bash
# In your Laravel app, run this once
php artisan tinker

# Generate OAuth URL
$authService = app(\Ekstremedia\LaravelYouTube\Services\AuthService::class);
$url = $authService->getAuthUrl();
echo $url;

# Visit the URL in browser, authorize with Google
# After callback, the token is stored in database
# All future API uploads will automatically use and refresh this token!
```

## 📚 Comprehensive Documentation

### Authentication Page (Frontend)

The package includes a ready-to-use authentication page where users can connect their YouTube channels:

**Access the page:** `https://yourdomain.com/youtube-authenticate` (configurable)

**Features:**
- Beautiful, modern UI with glass morphism design
- Shows all connected YouTube channels
- One-click connect/disconnect
- Token status and expiration info
- Permissions overview

**Configuration:**
```env
YOUTUBE_AUTH_PAGE_ENABLED=true
YOUTUBE_AUTH_PAGE_PATH=youtube-authenticate
```

**Usage in your app:**
```php
// Link to authentication page
<a href="{{ route('youtube.authenticate') }}">Connect YouTube</a>

// Or use the configured path
<a href="/{{ config('youtube.routes.auth_page.path') }}">Connect YouTube</a>
```

The page requires user authentication (configurable middleware). Users can:
1. View all connected channels
2. Connect new channels
3. Disconnect existing channels
4. See token expiration status

### Authentication & Token Management

#### OAuth Flow

```php
use EkstreMedia\LaravelYouTube\Services\AuthService;

$authService = app(AuthService::class);

// Generate OAuth URL with state for CSRF protection
$state = Str::random(40);
session(['youtube_oauth_state' => $state]);
$authUrl = $authService->getAuthUrl($state);

// In callback handler
if (request('state') !== session('youtube_oauth_state')) {
    abort(403, 'Invalid state');
}

// Exchange code for tokens
$tokens = $authService->exchangeCode(request('code'));
```

#### Token Storage & Management

```php
use EkstreMedia\LaravelYouTube\Services\TokenManager;

$tokenManager = app(TokenManager::class);

// Store tokens after OAuth
$token = $tokenManager->storeToken(
    $tokens,
    $channelInfo,
    auth()->id()
);

// Get active token for user
$token = $tokenManager->getActiveToken(auth()->id());

// Check if refresh needed (within 5 minutes of expiry)
if ($tokenManager->needsRefresh($token)) {
    $newTokens = $authService->refreshAccessToken($token->refresh_token);
    $tokenManager->updateToken($token, $newTokens);
}

// Handle multiple channels
$tokens = $tokenManager->getUserTokens(auth()->id());
foreach ($tokens as $token) {
    echo "Channel: {$token->channel_title}\n";
}
```

### Video Management

#### Upload Videos

```php
use EkstreMedia\LaravelYouTube\Facades\YouTube;

// Simple upload
$video = YouTube::forUser(auth()->id())->uploadVideo(
    $request->file('video'),
    [
        'title' => 'My Video',
        'description' => 'Video description',
        'tags' => ['tag1', 'tag2'],
        'category_id' => '22', // People & Blogs
        'privacy_status' => 'private', // private, unlisted, public
    ]
);

// Advanced upload with options
$video = YouTube::forUser(auth()->id())->uploadVideo(
    '/path/to/large-video.mp4',
    [
        'title' => 'Large Video Upload',
        'description' => 'Testing chunked upload',
        'tags' => ['large', 'chunked'],
        'made_for_kids' => false,
        'embeddable' => true,
        'license' => 'creativeCommon',
        'recording_date' => '2024-01-15T10:00:00Z',
        'default_language' => 'en',
        'default_audio_language' => 'en',
    ],
    [
        'chunk_size' => 50 * 1024 * 1024, // 50MB chunks
        'notify_url' => 'https://yourapp.com/webhook',
        'progress_callback' => function ($uploaded, $total) {
            $percent = round(($uploaded / $total) * 100);
            Log::info("Upload progress: {$percent}%");
        }
    ]
);

// Set custom thumbnail
YouTube::forUser(auth()->id())->setThumbnail(
    $video->video_id,
    $request->file('thumbnail')
);
```

#### Manage Videos

```php
// Get user's videos
$videos = YouTube::forUser(auth()->id())->getVideos([
    'maxResults' => 50,
    'order' => 'date', // date, rating, relevance, title, viewCount
    'type' => 'video',
    'videoDefinition' => 'high', // any, high, standard
    'videoDuration' => 'medium', // short (<4min), medium (4-20min), long (>20min)
]);

// Get single video details
$video = YouTube::forUser(auth()->id())->getVideo('video-id', [
    'snippet',
    'contentDetails',
    'statistics',
    'status',
    'processingDetails'
]);

// Update video metadata
$updated = YouTube::forUser(auth()->id())->updateVideo('video-id', [
    'title' => 'Updated Title',
    'description' => 'Updated description',
    'tags' => ['new', 'tags'],
    'category_id' => '24',
    'privacy_status' => 'public',
]);

// Delete video
YouTube::forUser(auth()->id())->deleteVideo('video-id');
```

### Channel Management

```php
// Get channel info
$channel = YouTube::forUser(auth()->id())->getChannel([
    'snippet',
    'contentDetails',
    'statistics',
    'brandingSettings',
    'contentOwnerDetails',
    'localizations',
    'status',
    'topicDetails'
]);

// Get channel videos
$videos = YouTube::forUser(auth()->id())->getChannelVideos('channel-id', [
    'maxResults' => 50,
    'order' => 'date',
]);

// Switch between multiple channels
$tokens = YouTubeToken::where('user_id', auth()->id())->get();
foreach ($tokens as $token) {
    $youtube = YouTube::withToken($token);
    $channel = $youtube->getChannel();
    echo "Channel: {$channel['title']} ({$channel['subscriberCount']} subscribers)\n";
}
```

### Background Jobs & Queues

```php
use EkstreMedia\LaravelYouTube\Jobs\UploadVideoJob;

// Dispatch upload job
UploadVideoJob::dispatch(
    userId: auth()->id(),
    videoPath: '/path/to/video.mp4',
    metadata: [
        'title' => 'Queued Upload',
        'description' => 'Uploaded via queue',
        'tags' => ['queued', 'background'],
        'privacy_status' => 'private',
    ],
    channelId: 'UC123456', // Optional: specific channel
    thumbnailPath: '/path/to/thumbnail.jpg', // Optional
    notifyUrl: 'https://yourapp.com/webhook' // Optional: webhook
)->onQueue('media');

// The job handles:
// - Automatic retries (3 attempts)
// - Exponential backoff (1min, 5min, 15min)
// - Progress tracking
// - Webhook notifications
// - Cleanup of temporary files
// - Error handling and logging
```

### API Endpoints

The package provides RESTful API endpoints:

#### Video Endpoints
```
GET    /api/youtube/videos              - List user's videos
GET    /api/youtube/videos/{id}         - Get video details
PUT    /api/youtube/videos/{id}         - Update video
DELETE /api/youtube/videos/{id}         - Delete video
POST   /api/youtube/videos/{id}/thumbnail - Set thumbnail
```

#### Upload Endpoints
```
POST   /api/youtube/upload               - Upload video
GET    /api/youtube/upload/status/{id}   - Get upload status
```

#### Channel Endpoints
```
GET    /api/youtube/channel              - Get channel info
GET    /api/youtube/channel/videos       - List channel videos
```

### Admin Panel

Access the admin panel at `/youtube-admin` (configurable):

```php
// In routes/web.php
Route::middleware(['auth', 'admin'])->group(function () {
    // Admin panel routes are automatically registered
    // Configure in config/youtube.php
});
```

Features:
- Token management
- Video listing and management
- Upload interface
- Channel statistics
- Upload history

### Advanced Features

#### Live Streaming

```php
// Create live broadcast
$broadcast = YouTube::forUser(auth()->id())->createLiveBroadcast([
    'title' => 'Live Stream',
    'description' => 'Live streaming event',
    'scheduled_start_time' => now()->addHour(),
    'scheduled_end_time' => now()->addHours(2),
    'privacy_status' => 'public',
]);

// Create live stream
$stream = YouTube::forUser(auth()->id())->createLiveStream([
    'title' => 'Stream',
    'description' => 'Stream description',
    'cdn' => [
        'frameRate' => '30fps',
        'resolution' => '1080p',
        'ingestionType' => 'rtmp',
    ],
]);

// Bind stream to broadcast
YouTube::forUser(auth()->id())->bindBroadcastToStream(
    $broadcast['id'],
    $stream['id']
);
```

#### Comments Management

```php
// Get video comments
$comments = YouTube::forUser(auth()->id())->getVideoComments('video-id', [
    'maxResults' => 100,
    'order' => 'time', // time, relevance
    'textFormat' => 'plainText', // plainText, html
]);

// Reply to comment
YouTube::forUser(auth()->id())->replyToComment('comment-id', 'Thank you for watching!');

// Moderate comments
YouTube::forUser(auth()->id())->setCommentModerationStatus('comment-id', 'published'); // heldForReview, published, rejected
```

#### Analytics Integration

```php
// Get video analytics (requires YouTube Analytics API)
$analytics = YouTube::forUser(auth()->id())->getVideoAnalytics('video-id', [
    'metrics' => 'views,estimatedMinutesWatched,averageViewDuration',
    'dimensions' => 'day',
    'startDate' => now()->subDays(30)->format('Y-m-d'),
    'endDate' => now()->format('Y-m-d'),
]);
```

## 🧪 Testing

The package includes comprehensive test coverage:

```bash
# Run all tests
composer test

# Run specific test suite
vendor/bin/pest --filter="Upload"

# Run with coverage
composer test-coverage
```

Test categories:
- Unit tests for configuration and models
- Feature tests for services and API endpoints
- Integration tests for OAuth flow
- Upload tests including chunked uploads
- Token management and refresh tests
- Rate limiting and authentication tests

## 🔧 Configuration

Full configuration options in `config/youtube.php`:

```php
return [
    'credentials' => [
        'client_id' => env('YOUTUBE_CLIENT_ID'),
        'client_secret' => env('YOUTUBE_CLIENT_SECRET'),
        'redirect_uri' => env('YOUTUBE_REDIRECT_URI', '/youtube/callback'),
    ],

    'scopes' => [
        'https://www.googleapis.com/auth/youtube',
        'https://www.googleapis.com/auth/youtube.upload',
        'https://www.googleapis.com/auth/youtube.readonly',
        'https://www.googleapis.com/auth/youtube.force-ssl',
        'https://www.googleapis.com/auth/youtubepartner',
        'https://www.googleapis.com/auth/youtubepartner-channel-audit',
    ],

    'admin' => [
        'enabled' => env('YOUTUBE_ADMIN_ENABLED', true),
        'prefix' => env('YOUTUBE_ADMIN_PREFIX', 'youtube-admin'),
        'middleware' => ['web'],
        'auth_middleware' => ['auth'],
    ],

    'routes' => [
        'api' => [
            'enabled' => env('YOUTUBE_API_ENABLED', true),
            'prefix' => env('YOUTUBE_API_PREFIX', 'youtube'),
            'middleware' => ['api'],
            'api_middleware' => ['auth:sanctum', 'throttle:60,1'],
        ],
    ],

    'token' => [
        'driver' => 'database',
        'table' => 'youtube_tokens',
        'cache_key' => 'youtube.token.',
        'cache_ttl' => env('YOUTUBE_TOKEN_CACHE_TTL', 3600),
    ],

    'upload' => [
        'chunk_size' => env('YOUTUBE_UPLOAD_CHUNK_SIZE', 1024 * 1024), // 1MB
        'timeout' => env('YOUTUBE_UPLOAD_TIMEOUT', 3600),
        'max_file_size' => env('YOUTUBE_UPLOAD_MAX_SIZE', 128 * 1024 * 1024 * 1024), // 128GB
        'temp_path' => env('YOUTUBE_UPLOAD_TEMP_PATH', storage_path('app/youtube-uploads')),
    ],

    'defaults' => [
        'privacy_status' => env('YOUTUBE_DEFAULT_PRIVACY', 'private'),
        'category_id' => env('YOUTUBE_DEFAULT_CATEGORY', '22'),
        'language' => env('YOUTUBE_DEFAULT_LANGUAGE', 'en'),
    ],

    'rate_limiting' => [
        'enabled' => env('YOUTUBE_RATE_LIMIT_ENABLED', true),
        'max_requests_per_minute' => env('YOUTUBE_RATE_LIMIT_PER_MINUTE', 60),
        'max_requests_per_hour' => env('YOUTUBE_RATE_LIMIT_PER_HOUR', 3000),
    ],

    'logging' => [
        'enabled' => env('YOUTUBE_LOGGING_ENABLED', true),
        'channel' => env('YOUTUBE_LOGGING_CHANNEL', 'youtube'),
        'level' => env('YOUTUBE_LOGGING_LEVEL', 'info'),
    ],
];
```

## 🛠️ Console Commands

```bash
# Refresh expiring tokens
php artisan youtube:refresh-tokens
php artisan youtube:refresh-tokens --token-id=1
php artisan youtube:refresh-tokens --user-id=1 --force

# Clean up expired tokens
php artisan youtube:clear-expired-tokens
php artisan youtube:clear-expired-tokens --days=30 --dry-run

# Sync video statistics
php artisan youtube:sync-videos
php artisan youtube:sync-videos --user-id=1
php artisan youtube:sync-videos --video-id=abc123
```

## 🔄 Scheduled Tasks

Add to your `app/Console/Kernel.php`:

```php
protected function schedule(Schedule $schedule)
{
    // Automatically refresh expiring tokens
    $schedule->command('youtube:refresh-tokens')->hourly();

    // Clean up expired tokens daily
    $schedule->command('youtube:clear-expired-tokens')->daily();

    // Sync video statistics every 6 hours
    $schedule->command('youtube:sync-videos')->everySixHours();
}
```

## 🚨 Error Handling

The package provides specific exception types:

```php
use EkstreMedia\LaravelYouTube\Exceptions\{
    YouTubeException,
    YouTubeAuthException,
    UploadException,
    TokenException,
    QuotaExceededException
};

try {
    $video = YouTube::forUser($userId)->uploadVideo($file, $metadata);
} catch (QuotaExceededException $e) {
    // Handle quota exceeded
    Log::error("YouTube quota exceeded: " . $e->getMessage());
    // Retry after reset
} catch (UploadException $e) {
    // Handle upload failure
    Log::error("Upload failed: " . $e->getMessage());
} catch (TokenException $e) {
    // Handle token issues
    return redirect()->route('youtube.auth');
} catch (YouTubeException $e) {
    // Handle general YouTube API errors
    Log::error("YouTube API error: " . $e->getYouTubeError());
}
```

## 🔐 Security

- OAuth tokens are encrypted using Laravel's encryption
- CSRF protection on OAuth flow
- Rate limiting on API endpoints
- Scoped access control
- Automatic token rotation
- Secure webhook signatures

## 📊 Events

The package dispatches Laravel events:

```php
// Listen for events in EventServiceProvider
protected $listen = [
    \EkstreMedia\LaravelYouTube\Events\VideoUploaded::class => [
        \App\Listeners\ProcessUploadedVideo::class,
    ],
    \EkstreMedia\LaravelYouTube\Events\TokenRefreshed::class => [
        \App\Listeners\LogTokenRefresh::class,
    ],
    \EkstreMedia\LaravelYouTube\Events\UploadFailed::class => [
        \App\Listeners\NotifyUploadFailure::class,
    ],
];
```

## 🤝 Contributing

Please see [CONTRIBUTING](CONTRIBUTING.md) for details.

## 🧪 Testing

```bash
composer test
composer test-coverage
composer format
composer analyse
```

## 📝 Changelog

Please see [CHANGELOG](CHANGELOG.md) for more information on what has changed recently.

## 🔒 Security

If you discover any security-related issues, please email security@ekstremedia.no instead of using the issue tracker.

## 📜 License

The MIT License (MIT). Please see [License File](LICENSE) for more information.

## 👥 Credits

- [Terje Nesthus](https://github.com/terjenesthus)
- [All Contributors](../../contributors)

## 🙏 Acknowledgments

- Thanks to Google for the YouTube Data API
- Laravel framework for the excellent foundation
- The open-source community for inspiration

## 📞 Support

- 📧 Email: support@ekstremedia.no
- 🐛 Issues: [GitHub Issues](https://github.com/ekstremedia/laravel-youtube/issues)
- 💬 Discussions: [GitHub Discussions](https://github.com/ekstremedia/laravel-youtube/discussions)
- 📖 Documentation: [Full Docs](https://ekstremedia.github.io/laravel-youtube)

---

Made with ❤️ by [Ekstre Media](https://ekstremedia.no)