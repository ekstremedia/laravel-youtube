# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project Overview

**Laravel YouTube Package** - A comprehensive Laravel package for YouTube API v3 integration with OAuth2 authentication, automatic token refresh, upload management, and extensive API coverage.

**Package Name**: `ekstremedia/laravel-youtube`
**Type**: Laravel package (library)
**Version**: 0.1.0 (development)
**PHP**: 8.2+
**Laravel**: 11.0+ and 12.0+
**License**: MIT

### Primary Use Case

This package is designed for:
1. **General Use**: Generic open-source YouTube API integration for Laravel applications
2. **Personal Use**: Automated video uploads from Raspberry Pi cameras (timelapse, security)
3. **Content Creators**: Managing YouTube channels programmatically
4. **Single-User Applications**: Designed primarily for single-user/single-channel scenarios

## Common Commands

### Development

```bash
# Install dependencies
composer install

# Run tests
composer test
vendor/bin/pest
vendor/bin/pest --filter="Upload"
vendor/bin/pest --coverage

# Code formatting
composer format
./vendor/bin/pint

# Check formatting (no changes)
composer format:test
./vendor/bin/pint --test

# Static analysis
composer analyse
./vendor/bin/phpstan analyse
```

### Package Development

```bash
# Publish to main project for testing
# (from nesthus_2026)
composer update ekstremedia/laravel-youtube --no-scripts

# Check package version
composer show ekstremedia/laravel-youtube
```

## Architecture

### Package Structure

```
laravel-youtube/
├── src/                          # Main package code
│   ├── Facades/
│   │   └── YouTube.php          # Main facade
│   ├── Http/
│   │   └── Controllers/
│   │       └── AuthController.php  # OAuth & authorization page
│   ├── Models/
│   │   ├── YouTubeToken.php    # OAuth token storage
│   │   └── YouTubeVideo.php    # Video metadata
│   ├── Services/
│   │   ├── YouTubeService.php  # Core YouTube API wrapper
│   │   ├── TokenManager.php    # Token storage & refresh
│   │   └── AuthService.php     # OAuth flow handler
│   ├── Console/Commands/
│   │   ├── RefreshTokensCommand.php
│   │   └── ClearExpiredTokensCommand.php
│   ├── Exceptions/             # Custom exceptions
│   │   ├── YouTubeException.php
│   │   ├── YouTubeAuthException.php
│   │   ├── UploadException.php
│   │   ├── TokenException.php
│   │   └── QuotaExceededException.php
│   └── YouTubeServiceProvider.php
├── config/
│   └── youtube.php             # Configuration
├── database/
│   ├── migrations/             # Package migrations
│   │   ├── create_youtube_tokens_table.php
│   │   └── create_youtube_videos_table.php
│   └── factories/              # Test factories
│       ├── YouTubeTokenFactory.php
│       └── YouTubeVideoFactory.php
├── resources/
│   └── views/
│       └── authorize.blade.php  # Authorization page view
├── routes/
│   └── web.php                 # OAuth routes & authorization page
├── tests/
│   ├── Feature/                # Feature tests
│   │   ├── YouTubeServiceTest.php
│   │   ├── UploadServiceTest.php
│   │   └── TokenManagementTest.php
│   ├── Unit/                   # Unit tests
│   │   └── ConfigTest.php
│   ├── TestCase.php            # Base test case
│   └── Pest.php                # Pest configuration
├── docs/
│   └── API_REFERENCE.md        # Service API documentation
├── README.md                   # Main documentation
├── CLAUDE.md                   # This file
├── CHANGELOG.md
├── CONTRIBUTING.md
└── composer.json
```

### Key Components

#### 1. YouTubeService (Core Service)

**Location**: `src/Services/YouTubeService.php`

**Purpose**: Main interface for YouTube API operations

**Key Methods**:

*Token Selection:*
- `usingDefault()` - Use default (most recent) active token
- `forChannel(string $channelId)` - Use token for specific channel
- `forUser(int $userId, ?string $channelId = null)` - Set user context (legacy support)
- `withToken(YouTubeToken $token)` - Use specific token

*Video Operations:*
- `uploadVideo($video, array $metadata, array $options = [])` - Upload videos with 15+ metadata fields
- `getVideos(array $options = [])` - List videos
- `getVideo(string $videoId, array $parts = [])` - Get video details
- `updateVideo(string $videoId, array $metadata)` - Update metadata
- `deleteVideo(string $videoId)` - Delete video
- `setThumbnail(string $videoId, $thumbnail)` - Set custom thumbnail

*Playlist Operations:*
- `createPlaylist(string $title, array $options = [])` - Create new playlist
- `getPlaylists(array $options = [])` - List playlists
- `getPlaylist(string $playlistId)` - Get single playlist
- `updatePlaylist(string $playlistId, array $updates)` - Update playlist
- `deletePlaylist(string $playlistId)` - Delete playlist
- `addVideoToPlaylist(string $videoId, string $playlistId, ?int $position = null)` - Add video to playlist
- `removeVideoFromPlaylist(string $playlistItemId)` - Remove video from playlist
- `getPlaylistVideos(string $playlistId, array $options = [])` - Get videos in playlist

*Caption Operations:*
- `uploadCaption(string $videoId, string $language, $captionFile, array $options = [])` - Upload captions
- `getCaptions(string $videoId)` - List captions
- `updateCaption(string $captionId, array $updates, $captionFile = null)` - Update captions
- `deleteCaption(string $captionId)` - Delete captions
- `downloadCaption(string $captionId, string $format = 'srt')` - Download captions

*Channel Operations:*
- `getChannel(array $parts = [])` - Get channel info

**Features**:
- Automatic token refresh before API calls
- Chunked file uploads (default 1MB chunks)
- Progress tracking callbacks
- Exception handling with specific error codes
- Comprehensive logging

#### 2. TokenManager

**Location**: `src/Services/TokenManager.php`

**Purpose**: OAuth token storage, retrieval, and refresh

**Key Methods**:
- `storeToken(array $tokenData, array $channelInfo, ?int $userId)` - Store new token
- `getActiveToken(int $userId, ?string $channelId)` - Get active token (cached)
- `getUserTokens(int $userId)` - Get all user tokens
- `needsRefresh(YouTubeToken $token)` - Check if refresh needed (5min threshold)
- `updateToken(YouTubeToken $token, array $newTokenData)` - Update after refresh
- `markTokenFailed(YouTubeToken $token, string $error)` - Mark as failed
- `deleteExpiredTokens(int $daysOld = 30)` - Clean up old tokens

**Features**:
- Token encryption using Laravel's `Crypt` facade
- Cache-based retrieval (1 hour TTL)
- Composite database indexes for performance
- Automatic cache invalidation
- Single-user mode (user_id = null by default)
- Can be extended for multi-user scenarios

#### 3. AuthService

**Location**: `src/Services/AuthService.php`

**Purpose**: OAuth2 flow management

**Key Methods**:
- `getAuthUrl(?string $state = null)` - Generate OAuth URL
- `exchangeCode(string $code, ?string $state = null)` - Exchange auth code
- `refreshAccessToken(string $refreshToken)` - Refresh expired token
- `revokeToken(string $token)` - Revoke access
- `getUserInfo(string $accessToken)` - Get user info from Google
- `getChannelInfo(string $accessToken)` - Get YouTube channel info

**Features**:
- CSRF protection via state parameter
- Offline access (long-lived refresh tokens)
- Automatic approval prompt
- Session-based state validation

#### 4. Models

**YouTubeToken** (`src/Models/YouTubeToken.php`):
- Stores OAuth tokens (encrypted)
- Tracks token expiry and refresh count
- Designed for single-user mode (user_id nullable)
- Scopes: `active()`, `expired()`, `expiringSoon()`, `forUser()`, `forChannel()`
- Methods: `hasScope()`, `markAsRefreshed()`, `activate()`, `deactivate()`

**YouTubeVideo** (`src/Models/YouTubeVideo.php`):
- Stores video metadata from YouTube
- Tracks processing status and statistics
- Supports all YouTube video fields
- Computed attributes: `watch_url`, `embed_url`, `studio_url`, `formatted_view_count`
- Scopes: `public()`, `private()`, `unlisted()`, `processed()`, `search()`

#### 5. Authorization Page

**Route**: `/youtube-authorize` (configurable)
**View**: `resources/views/authorize.blade.php`
**Controller**: `AuthController::index()`

**Purpose**: Simple web interface for OAuth authorization

**Features**:
- Checks if credentials are configured
- Displays connected channel information
- Provides authorization/re-authorization button
- Shows token status and expiration
- Automatically refreshes expired tokens

### Database Schema

#### `youtube_tokens` Table

| Column | Type | Description |
|--------|------|-------------|
| id | bigint | Primary key |
| user_id | bigint (nullable) | Foreign key to users |
| channel_id | string | YouTube channel ID (indexed) |
| channel_title | string | Channel name |
| channel_handle | string | @handle |
| channel_thumbnail | string | Channel image URL |
| access_token | text | Encrypted OAuth access token |
| refresh_token | text | Encrypted OAuth refresh token |
| token_type | string | Bearer |
| expires_in | integer | Token lifetime in seconds |
| expires_at | timestamp | Expiration timestamp |
| scopes | json | OAuth scopes array |
| channel_metadata | json | Channel statistics |
| is_active | boolean | Active status (indexed) |
| last_refreshed_at | timestamp | Last refresh time |
| refresh_count | integer | Number of refreshes |
| error | text | Last error message |
| error_at | timestamp | Error timestamp |

**Indexes**:
- `user_id, is_active, expires_at`
- `channel_id, is_active`

#### `youtube_videos` Table

| Column | Type | Description |
|--------|------|-------------|
| id | bigint | Primary key |
| user_id | bigint | Foreign key to users |
| token_id | bigint | Foreign key to youtube_tokens |
| video_id | string (unique) | YouTube video ID |
| channel_id | string (indexed) | Channel ID |
| title | string | Video title |
| description | text | Full description |
| tags | json | Tags array |
| category_id | string | YouTube category |
| privacy_status | string | private/unlisted/public (indexed) |
| license | string | youtube/creativeCommon |
| embeddable | boolean | Embedding allowed |
| made_for_kids | boolean | Kids content |
| default_language | string | Primary language |
| default_audio_language | string | Audio language |
| thumbnail_* | string | 5 thumbnail URLs |
| duration | string | ISO 8601 duration |
| definition | string | sd/hd |
| caption | string | Caption availability |
| view_count | bigint | Views |
| like_count | integer | Likes |
| comment_count | integer | Comments |
| upload_status | string | Upload status (indexed) |
| processing_status | string | Processing status (indexed) |
| statistics | json | All statistics |
| metadata | json | Additional metadata |
| published_at | timestamp | YouTube publish time |

**Indexes**:
- `user_id, privacy_status, created_at`
- `channel_id, privacy_status`
- `upload_status, processing_status`
- `published_at`

### Testing

**Framework**: Pest 3.x
**Database**: SQLite in-memory
**Total Tests**: 78 test cases (all passing ✓)

**Test Categories**:
- OAuth flow and token management
- Video upload (simple, chunked, from path)
- **Extended upload metadata** (license, audio language, location, recording details)
- File size validation
- Video CRUD operations via service
- **Playlist operations** (create, list, update, delete, add/remove videos)
- **Caption operations** (upload, list, update, delete, download)
- Token refresh and expiry
- Single-user mode
- Rate limiting
- Authentication
- Backward compatibility
- Metadata validation
- Raspberry Pi integration scenario

**Running Tests**:
```bash
# All tests
vendor/bin/pest

# Specific suite
vendor/bin/pest --filter="Upload"

# With coverage (requires pcov)
vendor/bin/pest --coverage

# Stop on failure
vendor/bin/pest --stop-on-failure
```

**Important Test Note**: Tests require PDO SQLite driver. If tests fail with "could not find driver", install php-sqlite3.

### Raspberry Pi Integration

**Use Case**: Automated daily timelapse uploads from Pi cameras

**Recommended Setup**:
1. Pi sends video to Laravel endpoint
2. Laravel stores video temporarily
3. Upload using `YouTube::usingDefault()` (synchronous or via your own job)
4. Optional: Add to playlist, send webhooks, etc.
5. Clean up temporary files

**Example Implementation**:
```php
// Pi upload endpoint
Route::post('/api/pi/upload', function (Request $request) {
    $file = $request->file('video');

    // Upload immediately using default token
    $video = YouTube::usingDefault()->uploadVideo($file, [
        'title' => "Pi Camera - " . now()->format('Y-m-d'),
        'description' => 'Automated timelapse',
        'tags' => ['raspberry-pi', 'timelapse'],
        'privacy_status' => 'unlisted',
    ]);

    return [
        'message' => 'Upload complete',
        'video_id' => $video->video_id,
        'watch_url' => $video->watch_url,
    ];
});
```

## Important Notes

### Token Security
- **All tokens are encrypted** at rest using Laravel's `Crypt` facade
- Tokens are **hidden** in model serialization
- Access tokens cached for 1 hour
- Refresh tokens stored permanently (until revoked)
- State parameter used for CSRF protection in OAuth flow

### Rate Limiting
- Built-in rate limiting: 60 requests/minute, 3000/hour
- YouTube API quota: 10,000 units/day (default)
- Upload costs ~1600 units
- Monitor quota usage in Google Cloud Console

### File Size Limits
- YouTube maximum: 128GB
- Configurable in package (default: 128GB)
- Chunked uploads for files >10MB recommended
- Progress tracking available via callbacks

### Supported Video Formats
- MP4, AVI, MOV, WMV, FLV, WebM
- Recommended: MP4 with H.264 video and AAC audio
- Max resolution: 4K (3840x2160)
- Max frame rate: 60fps

### Development Workflow

1. **Make changes** in `laravel-youtube` package
2. **Run tests**: `composer test`
3. **Format code**: `composer format`
4. **Test in main app**: Changes reflect immediately (symlinked from nesthus_2026)
5. **Commit changes** in package repository
6. **Update version** in `composer.json` when releasing

### Debugging

**Enable debug logging**:
```env
YOUTUBE_LOGGING_ENABLED=true
YOUTUBE_LOGGING_LEVEL=debug
```

**Common Issues**:
- **Token expired**: Check `expires_at`, refresh automatically handled
- **Quota exceeded**: Check Google Cloud Console, resets daily
- **Upload fails**: Check file format, size, temp path permissions
- **No active token**: User needs to re-authenticate via OAuth

## Quick Reference

### Most Common Operations

```php
use EkstreMedia\LaravelYouTube\Facades\YouTube;

// Upload video with basic metadata (single-user mode)
$video = YouTube::usingDefault()->uploadVideo($file, [
    'title' => 'My Video',
    'description' => 'Description',
    'privacy_status' => 'private',
]);

// Upload with extended metadata
$video = YouTube::usingDefault()->uploadVideo($file, [
    'title' => 'My Video',
    'description' => 'Description',
    'privacy_status' => 'private',
    'license' => 'creativeCommon',
    'default_audio_language' => 'en-US',
    'location' => [
        'latitude' => 59.9139,
        'longitude' => 10.7522,
        'description' => 'Oslo, Norway',
    ],
    'recording_date' => now()->toIso8601String(),
    'publish_at' => now()->addDay()->toIso8601String(), // Schedule
]);

// Upload for specific user (legacy/multi-user support)
$video = YouTube::forUser(auth()->id())->uploadVideo($file, [
    'title' => 'My Video',
    'description' => 'Description',
    'privacy_status' => 'private',
]);

// Get videos
$videos = YouTube::usingDefault()->getVideos();

// Update video
YouTube::usingDefault()->updateVideo('video-id', [
    'title' => 'New Title',
    'privacy_status' => 'public',
]);

// Create and manage playlists
$playlist = YouTube::usingDefault()->createPlaylist('My Playlist', [
    'privacy_status' => 'public',
]);
YouTube::usingDefault()->addVideoToPlaylist('video-id', $playlist['id']);

// Upload captions
YouTube::usingDefault()->uploadCaption('video-id', 'en', '/path/to/captions.srt');

// Get channel info
$channel = YouTube::usingDefault()->getChannel();
```

### Environment Variables Template

```env
# Required
YOUTUBE_CLIENT_ID=your-client-id
YOUTUBE_CLIENT_SECRET=your-client-secret
YOUTUBE_REDIRECT_URI=https://yourapp.com/youtube/callback

# Optional
YOUTUBE_AUTH_PAGE_PATH=youtube-authorize
YOUTUBE_DEFAULT_PRIVACY=private
YOUTUBE_UPLOAD_CHUNK_SIZE=10485760
YOUTUBE_RATE_LIMIT_ENABLED=true
YOUTUBE_LOGGING_ENABLED=true
```

## Documentation Files

- **README.md**: Main package documentation (770+ lines)
- **CLAUDE.md**: This file (AI assistance guide)
- **docs/API_REFERENCE.md**: Complete API documentation (1000+ lines)
- **CHANGELOG.md**: Version history
- **CONTRIBUTING.md**: Contribution guidelines
- **LICENSE**: MIT License

---

**Package maintained by**: Terje Nesthus (terje@ekstremedia.no)
**Repository**: https://github.com/ekstremedia/laravel-youtube (develop branch)
**Status**: Development (not published to Packagist yet)
**Used in**: nesthus_2026 project (symlinked via composer path repository)