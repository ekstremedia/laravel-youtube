# Laravel YouTube Package

A modern, comprehensive Laravel package for YouTube API v3 integration with OAuth2 authentication, automatic token refresh, and a beautiful dark-purple themed admin panel.

[![Tests](https://github.com/ekstremedia/laravel-youtube/actions/workflows/tests.yml/badge.svg)](https://github.com/ekstremedia/laravel-youtube/actions/workflows/tests.yml)
[![Compatibility](https://github.com/ekstremedia/laravel-youtube/actions/workflows/compatibility.yml/badge.svg)](https://github.com/ekstremedia/laravel-youtube/actions/workflows/compatibility.yml)
[![codecov](https://codecov.io/gh/ekstremedia/laravel-youtube/branch/main/graph/badge.svg)](https://codecov.io/gh/ekstremedia/laravel-youtube)
![Laravel](https://img.shields.io/badge/Laravel-11%2B%20%7C%2012%2B-red)
![PHP](https://img.shields.io/badge/PHP-8.2%20%7C%208.3-blue)
![License](https://img.shields.io/badge/License-MIT-green)

## Features

- 🔐 **OAuth2 Authentication** with automatic token refresh
- 📹 **Video Management** - Upload, update, delete videos
- 📺 **Channel Management** - Connect and manage multiple YouTube channels
- 📝 **Playlist Management** - Create and manage playlists
- 🎨 **Beautiful Admin Panel** - Dark purple themed UI with Alpine.js
- 🔄 **Automatic Token Refresh** - Never worry about expired tokens
- 📊 **Analytics Dashboard** - View channel and video statistics
- 🧪 **Test Driven** - Comprehensive Pest test suite
- 🎯 **Laravel 12+ Compatible** - Built for the latest Laravel versions

## Requirements

- PHP 8.2 or higher
- Laravel 11.0 or higher
- Google API credentials (OAuth 2.0 Client ID)

## Installation

### Step 1: Install via Composer

```bash
composer require ekstremedia/laravel-youtube
```

### Step 2: Publish Configuration and Assets

```bash
# Publish configuration
php artisan vendor:publish --tag=youtube-config

# Publish migrations
php artisan vendor:publish --tag=youtube-migrations

# Publish views (optional, if you want to customize)
php artisan vendor:publish --tag=youtube-views

# Publish assets (CSS/JS for admin panel)
php artisan vendor:publish --tag=youtube-assets
```

### Step 3: Configure Environment Variables

Add the following to your `.env` file:

```env
# YouTube OAuth2 Credentials
YOUTUBE_CLIENT_ID=your-client-id-here
YOUTUBE_CLIENT_SECRET=your-client-secret-here
YOUTUBE_REDIRECT_URI=/youtube/callback

# Optional Settings
YOUTUBE_ADMIN_ENABLED=true
YOUTUBE_ADMIN_PREFIX=youtube-admin
YOUTUBE_DEFAULT_PRIVACY=private
YOUTUBE_RATE_LIMIT_ENABLED=true
YOUTUBE_LOGGING_ENABLED=true
```

### Step 4: Obtain Google API Credentials

1. Go to [Google Cloud Console](https://console.cloud.google.com)
2. Create a new project or select an existing one
3. Enable the YouTube Data API v3
4. Create OAuth 2.0 credentials
5. Add authorized redirect URI: `https://yourdomain.com/youtube/callback`
6. Copy the Client ID and Client Secret to your `.env` file

### Step 5: Run Migrations

```bash
php artisan migrate
```

## Usage

### Basic Usage with Facade

```php
use EkstreMedia\LaravelYouTube\Facades\YouTube;

// Set user context
$youtube = YouTube::forUser(auth()->id());

// Get channel information
$channel = $youtube->getChannel();

// Get videos
$videos = $youtube->getVideos([
    'maxResults' => 50,
    'order' => 'date'
]);

// Upload a video
$video = $youtube->uploadVideo($request->file('video'), [
    'title' => 'My Video Title',
    'description' => 'Video description',
    'tags' => ['tag1', 'tag2'],
    'category_id' => '22',
    'privacy_status' => 'private'
]);

// Update video metadata
$youtube->updateVideo($videoId, [
    'title' => 'New Title',
    'description' => 'New Description',
    'privacy_status' => 'public'
]);

// Delete a video
$youtube->deleteVideo($videoId);
```

### OAuth Authentication

```php
// In your controller
public function connectYouTube()
{
    return redirect()->route('youtube.auth');
}

// After authentication, tokens are automatically stored
// The package handles token refresh automatically
```

### Admin Panel

Access the admin panel at `/youtube-admin` (configurable via `YOUTUBE_ADMIN_PREFIX`).

Features include:
- Dashboard with statistics and charts
- Channel management
- Video listing and management
- Upload interface with progress tracking
- Playlist management
- Token management

### Using Service Classes Directly

```php
use EkstreMedia\LaravelYouTube\Services\YouTubeService;
use EkstreMedia\LaravelYouTube\Services\TokenManager;

class VideoController extends Controller
{
    protected $youtube;
    protected $tokenManager;

    public function __construct(YouTubeService $youtube, TokenManager $tokenManager)
    {
        $this->youtube = $youtube;
        $this->tokenManager = $tokenManager;
    }

    public function index()
    {
        $token = $this->tokenManager->getActiveToken(auth()->id());

        if (!$token) {
            return redirect()->route('youtube.auth');
        }

        $videos = $this->youtube
            ->withToken($token)
            ->getVideos();

        return view('videos.index', compact('videos'));
    }
}
```

### Handling Uploads

```php
public function upload(Request $request)
{
    $request->validate([
        'video' => 'required|file|mimes:mp4,avi,mov,wmv|max:12800000', // 128GB max
        'title' => 'required|string|max:100',
        'description' => 'nullable|string|max:5000',
        'tags' => 'nullable|array',
        'privacy' => 'required|in:private,unlisted,public'
    ]);

    try {
        $video = YouTube::forUser(auth()->id())
            ->uploadVideo(
                $request->file('video'),
                [
                    'title' => $request->input('title'),
                    'description' => $request->input('description'),
                    'tags' => $request->input('tags', []),
                    'privacy_status' => $request->input('privacy'),
                    'made_for_kids' => false,
                ],
                [
                    'chunk_size' => 5 * 1024 * 1024, // 5MB chunks
                ]
            );

        return response()->json([
            'success' => true,
            'video_id' => $video->video_id,
            'message' => 'Video uploaded successfully!'
        ]);
    } catch (\Exception $e) {
        return response()->json([
            'success' => false,
            'message' => 'Upload failed: ' . $e->getMessage()
        ], 500);
    }
}
```

### Blade Components

The package includes several Blade components:

```blade
{{-- Upload Form Component --}}
<x-youtube-upload-form
    :channel="$channel"
    :categories="$categories" />

{{-- Video List Component --}}
<x-youtube-video-list
    :videos="$videos"
    :show-actions="true" />

{{-- Channel Info Component --}}
<x-youtube-channel-info
    :channel="$channel"
    :show-stats="true" />

{{-- Auth Button Component --}}
<x-youtube-auth-button
    text="Connect YouTube Channel"
    class="btn-primary" />
```

## Console Commands

```bash
# Refresh expiring tokens
php artisan youtube:refresh-tokens

# Refresh specific token
php artisan youtube:refresh-tokens --token-id=1

# Refresh all tokens for a user
php artisan youtube:refresh-tokens --user-id=1

# Clear expired tokens
php artisan youtube:clear-expired-tokens

# Clear tokens older than 60 days
php artisan youtube:clear-expired-tokens --days=60

# Dry run (see what would be deleted)
php artisan youtube:clear-expired-tokens --dry-run
```

## Scheduled Tasks

Add to your `app/Console/Kernel.php`:

```php
protected function schedule(Schedule $schedule)
{
    // Automatically refresh expiring tokens
    $schedule->command('youtube:refresh-tokens')->hourly();

    // Clean up expired tokens
    $schedule->command('youtube:clear-expired-tokens')->daily();
}
```

## API Endpoints

The package provides RESTful API endpoints:

```
GET    /api/youtube/channel           - Get channel information
GET    /api/youtube/videos            - List videos
GET    /api/youtube/videos/{id}       - Get video details
PUT    /api/youtube/videos/{id}       - Update video
DELETE /api/youtube/videos/{id}       - Delete video
POST   /api/youtube/upload            - Upload video
GET    /api/youtube/playlists         - List playlists
POST   /api/youtube/playlists         - Create playlist
```

## Error Handling

The package provides custom exceptions:

```php
use EkstreMedia\LaravelYouTube\Exceptions\YouTubeException;
use EkstreMedia\LaravelYouTube\Exceptions\YouTubeAuthException;
use EkstreMedia\LaravelYouTube\Exceptions\UploadException;
use EkstreMedia\LaravelYouTube\Exceptions\QuotaExceededException;

try {
    $video = YouTube::forUser($userId)->uploadVideo($file, $metadata);
} catch (QuotaExceededException $e) {
    // Handle quota exceeded
    Log::error('YouTube quota exceeded: ' . $e->getMessage());
} catch (UploadException $e) {
    // Handle upload errors
    Log::error('Upload failed: ' . $e->getMessage());
} catch (YouTubeException $e) {
    // Handle general YouTube API errors
    Log::error('YouTube API error: ' . $e->getMessage());
}
```

## Configuration

See `config/youtube.php` for all available configuration options:

- OAuth2 credentials
- API scopes
- Admin panel settings
- Upload settings (chunk size, temp path, max file size)
- Default privacy settings
- Rate limiting
- Logging

## Testing

Run the test suite with Pest:

```bash
composer test

# With coverage
composer test-coverage
```

## Security

- All tokens are encrypted before storage
- CSRF protection on all forms
- Rate limiting on API endpoints
- Automatic token refresh prevents unauthorized access
- Secure OAuth2 flow with state parameter

## Customization

### Custom Views

Publish and modify views:

```bash
php artisan vendor:publish --tag=youtube-views
```

Views are published to `resources/views/vendor/youtube/`.

### Extending Services

```php
namespace App\Services;

use EkstreMedia\LaravelYouTube\Services\YouTubeService;

class CustomYouTubeService extends YouTubeService
{
    public function customMethod()
    {
        // Your custom logic
    }
}
```

Register in a service provider:

```php
$this->app->bind(YouTubeService::class, CustomYouTubeService::class);
```

## Troubleshooting

### Common Issues

1. **"No refresh token received"**
   - Ensure you're using `access_type=offline` and `approval_prompt=force`
   - User may need to revoke access and re-authenticate

2. **"Quota exceeded"**
   - YouTube API has daily quotas
   - Enable rate limiting in configuration
   - Consider caching responses

3. **"Token expired"**
   - Run `php artisan youtube:refresh-tokens`
   - Check scheduled tasks are running

## Contributing

Contributions are welcome! Please see [CONTRIBUTING.md](CONTRIBUTING.md) for details.

## License

The MIT License (MIT). See [LICENSE](LICENSE) for more information.

## Credits

- [Terje Nesthus](https://github.com/terje)
- Built with inspiration from abandoned packages, modernized for Laravel 12+

## Support

For issues, questions, or suggestions, please [open an issue](https://github.com/ekstremedia/laravel-youtube/issues) on GitHub.