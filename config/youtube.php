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
        'auth_page' => [
            'enabled' => env('YOUTUBE_AUTH_PAGE_ENABLED', true),
            'path' => env('YOUTUBE_AUTH_PAGE_PATH', 'youtube-authenticate'),
            'middleware' => ['web', 'auth'], // Require authentication
        ],
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

    /*
    |--------------------------------------------------------------------------
    | Security Settings
    |--------------------------------------------------------------------------
    |
    | Enhanced security configuration for the YouTube package.
    |
    | - csrf_protection: Enable CSRF protection for OAuth callbacks
    | - ip_whitelist: Restrict API access to specific IP addresses
    | - webhook_signing: Verify webhook signatures
    | - token_encryption: Use strong encryption for stored tokens (always enabled)
    | - allowed_upload_mime_types: Whitelist of allowed video MIME types
    |
    */
    'security' => [
        // CSRF Protection
        'csrf_protection' => env('YOUTUBE_CSRF_PROTECTION', true),

        // IP Whitelist (leave empty to allow all)
        'ip_whitelist' => array_filter(explode(',', env('YOUTUBE_IP_WHITELIST', ''))),

        // API Key for programmatic access (alternative to OAuth for server-to-server)
        'api_key' => env('YOUTUBE_API_KEY'),
        'api_key_header' => env('YOUTUBE_API_KEY_HEADER', 'X-YouTube-API-Key'),

        // Webhook Signature Verification
        'webhook_signing' => [
            'enabled' => env('YOUTUBE_WEBHOOK_SIGNING_ENABLED', true),
            'secret' => env('YOUTUBE_WEBHOOK_SECRET'),
            'header' => env('YOUTUBE_WEBHOOK_SIGNATURE_HEADER', 'X-YouTube-Signature'),
            'algorithm' => env('YOUTUBE_WEBHOOK_ALGORITHM', 'sha256'),
        ],

        // Allowed Video MIME Types (security measure)
        'allowed_upload_mime_types' => [
            'video/mp4',
            'video/mpeg',
            'video/quicktime',
            'video/x-msvideo',
            'video/x-flv',
            'video/webm',
            'video/x-matroska',
        ],

        // Maximum allowed file extensions
        'allowed_upload_extensions' => ['mp4', 'avi', 'mov', 'wmv', 'flv', 'webm', 'mkv'],

        // User authorization
        'require_verified_email' => env('YOUTUBE_REQUIRE_VERIFIED_EMAIL', false),
        'require_terms_acceptance' => env('YOUTUBE_REQUIRE_TERMS', false),

        // Admin panel access control
        'admin_permission' => env('YOUTUBE_ADMIN_PERMISSION', 'manage-youtube'),
        'admin_role' => env('YOUTUBE_ADMIN_ROLE'), // e.g., 'admin'

        // Token security
        'token_refresh_threshold' => 300, // Refresh tokens 5 minutes before expiry
        'revoke_on_logout' => env('YOUTUBE_REVOKE_ON_LOGOUT', false),
    ],
];
