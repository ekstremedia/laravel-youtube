<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Google OAuth2 Credentials
    |--------------------------------------------------------------------------
    |
    | These credentials are used to authenticate with Google's OAuth2 service
    | and access the YouTube Data API v3. You can obtain these from the
    | Google Cloud Console (https://console.cloud.google.com).
    |
    */
    'credentials' => [
        'client_id' => env('YOUTUBE_CLIENT_ID'),
        'client_secret' => env('YOUTUBE_CLIENT_SECRET'),
        'redirect_uri' => env('YOUTUBE_REDIRECT_URI', '/youtube/callback'),
    ],

    /*
    |--------------------------------------------------------------------------
    | API Scopes
    |--------------------------------------------------------------------------
    |
    | These scopes determine what permissions your application has when
    | accessing the YouTube API on behalf of the user. Add or remove
    | scopes based on your application's requirements.
    |
    */
    'scopes' => [
        'https://www.googleapis.com/auth/youtube',
        'https://www.googleapis.com/auth/youtube.upload',
        'https://www.googleapis.com/auth/youtube.readonly',
        'https://www.googleapis.com/auth/youtube.force-ssl',
        'https://www.googleapis.com/auth/youtubepartner',
        'https://www.googleapis.com/auth/youtubepartner-channel-audit',
    ],

    /*
    |--------------------------------------------------------------------------
    | Admin Panel Settings
    |--------------------------------------------------------------------------
    |
    | Configuration for the built-in admin panel that provides a UI for
    | managing YouTube channels, videos, and uploads.
    |
    */
    'admin' => [
        'enabled' => env('YOUTUBE_ADMIN_ENABLED', true),
        'prefix' => env('YOUTUBE_ADMIN_PREFIX', 'youtube-admin'),
        'middleware' => ['web'],
        'auth_middleware' => ['auth'],
    ],

    /*
    |--------------------------------------------------------------------------
    | API Routes Settings
    |--------------------------------------------------------------------------
    |
    | Configuration for the API routes that handle OAuth authentication
    | and provide endpoints for YouTube operations.
    |
    */
    'routes' => [
        'enabled' => env('YOUTUBE_ROUTES_ENABLED', true),
        'prefix' => env('YOUTUBE_ROUTES_PREFIX', 'youtube'),
        'middleware' => ['web'],
        'api_middleware' => ['api', 'throttle:60,1'],
    ],

    /*
    |--------------------------------------------------------------------------
    | Token Storage
    |--------------------------------------------------------------------------
    |
    | Configure how OAuth tokens are stored. By default, they are stored
    | in the database with automatic refresh handling.
    |
    */
    'storage' => [
        'driver' => env('YOUTUBE_STORAGE_DRIVER', 'database'),
        'table' => 'youtube_tokens',
        'cache_key' => 'youtube_token',
        'cache_ttl' => 3600, // 1 hour
    ],

    /*
    |--------------------------------------------------------------------------
    | Upload Settings
    |--------------------------------------------------------------------------
    |
    | Configure default settings for video uploads including chunk size,
    | timeout, and temporary storage location.
    |
    */
    'upload' => [
        'chunk_size' => env('YOUTUBE_UPLOAD_CHUNK_SIZE', 1 * 1024 * 1024), // 1MB chunks
        'timeout' => env('YOUTUBE_UPLOAD_TIMEOUT', 3600), // 1 hour
        'temp_path' => env('YOUTUBE_UPLOAD_TEMP_PATH', storage_path('app/youtube-uploads')),
        'max_file_size' => env('YOUTUBE_MAX_FILE_SIZE', 128 * 1024 * 1024 * 1024), // 128GB (YouTube's max)
    ],

    /*
    |--------------------------------------------------------------------------
    | Default Privacy Settings
    |--------------------------------------------------------------------------
    |
    | Default privacy status for uploaded videos. Can be overridden
    | when uploading individual videos.
    |
    */
    'defaults' => [
        'privacy_status' => env('YOUTUBE_DEFAULT_PRIVACY', 'private'), // private, unlisted, public
        'category_id' => env('YOUTUBE_DEFAULT_CATEGORY', '22'), // People & Blogs
        'language' => env('YOUTUBE_DEFAULT_LANGUAGE', 'en'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Rate Limiting
    |--------------------------------------------------------------------------
    |
    | Configure rate limiting for API requests to avoid hitting YouTube's
    | quota limits. YouTube API has daily quota limits.
    |
    */
    'rate_limit' => [
        'enabled' => env('YOUTUBE_RATE_LIMIT_ENABLED', true),
        'cache_key' => 'youtube_rate_limit',
        'max_requests_per_minute' => 60,
        'max_requests_per_hour' => 3000,
    ],

    /*
    |--------------------------------------------------------------------------
    | Logging
    |--------------------------------------------------------------------------
    |
    | Configure logging for YouTube API operations. Useful for debugging
    | and monitoring API usage.
    |
    */
    'logging' => [
        'enabled' => env('YOUTUBE_LOGGING_ENABLED', true),
        'channel' => env('YOUTUBE_LOG_CHANNEL', 'youtube'),
        'level' => env('YOUTUBE_LOG_LEVEL', 'info'),
    ],
];