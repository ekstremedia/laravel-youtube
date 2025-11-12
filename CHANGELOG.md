# Changelog

All notable changes to `laravel-youtube` will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## v1.0.0 - First version released - 2025-11-12

First stable version of Laravel Youtube upload package

## [Unreleased](https://github.com/ekstremedia/laravel-youtube/compare/v1.1.0 - First version released...HEAD)

## [1.0.0](https://github.com/ekstremedia/laravel-youtube/releases/tag/v1.0.0) - 2025-11-12

### Added

- **OAuth2 Authentication**: Complete OAuth2 flow with automatic token refresh and encryption
- **Video Management**: Upload, update, delete videos with comprehensive metadata support
- **Extended Upload Metadata**: License selection, audio language, location coordinates, recording dates, scheduled publishing
- **Playlist Management**: Create, update, delete playlists and manage video additions/removals
- **Caption Management**: Upload, update, delete, and download captions in multiple formats (SRT, VTT, etc.)
- **Channel Operations**: Retrieve channel information and statistics
- **Token Management**: Automatic token refresh, expiration handling, and multi-channel support
- **Single-User Mode**: Optimized for single-user/single-channel scenarios (default user_id = null)
- **Multi-User Support**: Extensible for multi-user applications with user_id context
- **Chunked Uploads**: Automatic chunked uploads for large video files (configurable chunk size)
- **Progress Tracking**: Callback support for upload progress monitoring
- **Rate Limiting**: Built-in rate limiting (60 req/min, 3000 req/hour)
- **Beautiful Admin Panel**: Dark purple themed authorization interface
- **Database Models**: YouTubeToken and YouTubeVideo models with comprehensive scopes and relationships
- **Artisan Commands**: Token refresh and cleanup commands
- **Comprehensive Testing**: 78 test cases with Pest PHP covering all features
- **Security Features**: Token encryption, CSRF protection, mass assignment protection, input validation
- **Extensive Documentation**: Complete API reference (1000+ lines), detailed README (770+ lines), and AI assistance guide

### Framework Support

- Laravel 11.x and 12.x
- PHP 8.2 and 8.3
- Google API Client 2.15+

### Testing

- 78 passing tests with 271 assertions
- PHPStan static analysis at max level
- Laravel Pint code formatting
- Feature tests for OAuth, uploads, playlists, captions
- Security tests for CSRF, validation, authorization
- Unit tests for configuration and models

### Security

- All OAuth tokens encrypted at rest using Laravel's Crypt facade
- Tokens hidden in model serialization
- CSRF protection on OAuth flow with state parameter validation
- Rate limiting on all API endpoints
- Mass assignment protection on models
- Comprehensive input validation for all metadata
- File type and size validation for uploads

## [v1.1.0 - First version released](https://github.com/ekstremedia/laravel-youtube/compare/v1.0.0...v1.1.0 - First version released) - 2025-11-12

First stable version of Laravel Youtube upload package
