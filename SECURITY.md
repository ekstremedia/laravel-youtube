# Security Policy

## Overview

This document outlines the security features and best practices for the Laravel YouTube package. Security is a top priority, and this package includes multiple layers of protection to ensure safe handling of OAuth tokens, API access, and video uploads.

## Security Features

### 1. Token Encryption

All OAuth tokens (access and refresh tokens) are **automatically encrypted** before being stored in the database using Laravel's `Crypt` facade with AES-256-CBC encryption.

**What this protects against:**
- Database breaches
- SQL injection attacks that expose token data
- Unauthorized access to stored credentials

**Implementation:**
```php
// Tokens are encrypted in TokenManager
$tokenModel->access_token = Crypt::encryptString($tokenData['access_token']);
$tokenModel->refresh_token = Crypt::encryptString($tokenData['refresh_token']);
```

### 2. CSRF Protection

OAuth callbacks are protected against Cross-Site Request Forgery (CSRF) attacks using Laravel's session-based state parameter validation.

**Configuration:**
```env
YOUTUBE_CSRF_PROTECTION=true
```

**How it works:**
- A random state parameter is generated and stored in the session
- State is validated when OAuth callback is received
- Invalid state parameters are rejected

### 3. IP Whitelisting

Restrict API access to specific IP addresses to prevent unauthorized access.

**Configuration:**
```env
# Comma-separated list of allowed IPs (leave empty to allow all)
YOUTUBE_IP_WHITELIST=192.168.1.1,10.0.0.1
```

**Usage:**
- Applied automatically via `youtube.ip` middleware
- Blocks requests from non-whitelisted IPs with 403 Forbidden
- Logs all blocked attempts

### 4. API Authentication

Multiple authentication methods are supported:

#### Laravel Sanctum/Passport (Recommended)
```php
// API routes automatically use auth:sanctum middleware
config(['youtube.routes.api_middleware' => ['api', 'auth:sanctum']]);
```

#### API Key Authentication
For server-to-server communication (e.g., Raspberry Pi uploads):

```env
YOUTUBE_API_KEY=your-secret-api-key-here
YOUTUBE_API_KEY_HEADER=X-YouTube-API-Key
```

**Usage:**
```bash
curl -H "X-YouTube-API-Key: your-secret-api-key" \
     https://yourapp.com/api/youtube/upload
```

### 5. Rate Limiting

Protect against abuse and quota exhaustion with multi-tier rate limiting.

**Configuration:**
```env
YOUTUBE_RATE_LIMIT_ENABLED=true
```

**Default Limits:**
- 60 requests per minute per user/IP
- 3,000 requests per hour per user/IP

**Customization in config:**
```php
'rate_limit' => [
    'enabled' => true,
    'max_requests_per_minute' => 60,
    'max_requests_per_hour' => 3000,
],
```

### 6. Webhook Signature Verification

Verify webhook requests using HMAC signatures to ensure they come from trusted sources.

**Configuration:**
```env
YOUTUBE_WEBHOOK_SIGNING_ENABLED=true
YOUTUBE_WEBHOOK_SECRET=your-webhook-secret
YOUTUBE_WEBHOOK_SIGNATURE_HEADER=X-YouTube-Signature
YOUTUBE_WEBHOOK_ALGORITHM=sha256
```

**Implementation:**
```php
// Webhook endpoint with signature verification
Route::post('/webhook/upload-complete', [WebhookController::class, 'handle'])
    ->middleware('youtube.webhook');
```

**Generating signatures (client side):**
```php
$signature = hash_hmac('sha256', $payload, $secret);
// Send in X-YouTube-Signature header
```

### 7. Admin Access Control

Fine-grained access control for admin panel using Laravel's authorization system.

**Configuration:**
```env
# Require specific permission
YOUTUBE_ADMIN_PERMISSION=manage-youtube

# Or require specific role (if using Spatie Permission or similar)
YOUTUBE_ADMIN_ROLE=admin

# Require verified email
YOUTUBE_REQUIRE_VERIFIED_EMAIL=true
```

**Supported Authorization Methods:**
- Laravel Gates and Policies
- Spatie Permission package
- Custom role/permission systems

### 8. Upload Security

#### File Type Validation
Only allowed video formats can be uploaded:

**Default allowed MIME types:**
- video/mp4
- video/mpeg
- video/quicktime
- video/x-msvideo
- video/x-flv
- video/webm
- video/x-matroska

**Default allowed extensions:**
- mp4, avi, mov, wmv, flv, webm, mkv

#### File Size Limits
```env
# Maximum file size (default: 128GB - YouTube's limit)
YOUTUBE_MAX_FILE_SIZE=137438953472
```

#### Temporary File Cleanup
Uploaded files are automatically cleaned up after processing to prevent storage exhaustion.

### 9. Logging and Monitoring

All security events are logged for auditing and monitoring.

**Configuration:**
```env
YOUTUBE_LOGGING_ENABLED=true
YOUTUBE_LOG_CHANNEL=youtube
YOUTUBE_LOG_LEVEL=info
```

**Logged Events:**
- Failed authentication attempts
- IP whitelist violations
- Rate limit exceedances
- Webhook signature failures
- Token refresh failures
- Admin access denials

## Security Best Practices

### 1. Environment Variables

**Never commit sensitive credentials to version control:**

```env
# .env file (add to .gitignore)
YOUTUBE_CLIENT_ID=your-google-client-id
YOUTUBE_CLIENT_SECRET=your-google-client-secret
YOUTUBE_API_KEY=your-api-key
YOUTUBE_WEBHOOK_SECRET=your-webhook-secret
```

### 2. HTTPS Only

**Always use HTTPS in production:**
- OAuth callbacks require HTTPS
- Token transmission must be encrypted
- Configure your redirect URI with https://

```env
YOUTUBE_REDIRECT_URI=https://yourapp.com/youtube/callback
```

### 3. Token Refresh Threshold

Tokens are automatically refreshed before expiry to prevent authentication failures:

```php
'security' => [
    'token_refresh_threshold' => 300, // 5 minutes before expiry
],
```

### 4. Revoke Tokens on Logout

Optionally revoke YouTube tokens when users log out:

```env
YOUTUBE_REVOKE_ON_LOGOUT=true
```

### 5. Regular Token Cleanup

Use scheduled commands to clean up expired tokens:

```bash
# Runs automatically via Laravel scheduler
php artisan youtube:clear-expired-tokens
```

### 6. Limit OAuth Scopes

Only request the scopes your application needs:

```php
// config/youtube.php
'scopes' => [
    'https://www.googleapis.com/auth/youtube.upload', // Only if you need upload
    'https://www.googleapis.com/auth/youtube.readonly', // Read-only access
],
```

### 7. Admin Panel Protection

Add additional middleware to admin routes:

```php
// routes/web.php
Route::group([
    'prefix' => 'youtube-admin',
    'middleware' => ['web', 'auth', 'verified', '2fa'], // Add 2FA if available
], function () {
    // Admin routes
});
```

### 8. Database Security

- Use separate database user with minimal privileges
- Enable database encryption at rest
- Regular backups with encrypted storage
- Use prepared statements (Laravel does this automatically)

### 9. API Key Rotation

Rotate API keys regularly:

```bash
# Generate new key
php artisan tinker
>>> Str::random(64)

# Update .env
YOUTUBE_API_KEY=new-key-here

# Update clients with new key
```

### 10. Monitor YouTube API Quota

Set up alerts for quota usage to prevent service disruptions:

- Monitor daily quota usage in Google Cloud Console
- Set up billing alerts
- Implement application-level quota tracking

## Middleware Reference

The package provides the following middleware:

| Middleware | Alias | Purpose |
|------------|-------|---------|
| `YouTubeApiAuth` | `youtube.auth` | API authentication (Sanctum/API Key) |
| `YouTubeIpWhitelist` | `youtube.ip` | IP address restriction |
| `YouTubeRateLimit` | `youtube.ratelimit` | Rate limiting |
| `YouTubeWebhookSignature` | `youtube.webhook` | Webhook signature verification |
| `YouTubeAdminAccess` | `youtube.admin` | Admin panel access control |

### Applying Middleware

**To API routes:**
```php
Route::post('/api/youtube/upload', [UploadController::class, 'upload'])
    ->middleware(['youtube.auth', 'youtube.ip', 'youtube.ratelimit']);
```

**To admin routes:**
```php
Route::get('/youtube-admin', [DashboardController::class, 'index'])
    ->middleware(['youtube.admin', 'youtube.ip']);
```

**To webhook routes:**
```php
Route::post('/webhooks/youtube', [WebhookController::class, 'handle'])
    ->middleware('youtube.webhook');
```

## Reporting Security Vulnerabilities

If you discover a security vulnerability, please email terje@ekstremedia.no. Do not create public GitHub issues for security vulnerabilities.

**Please include:**
- Description of the vulnerability
- Steps to reproduce
- Potential impact
- Suggested fix (if available)

We will respond within 48 hours and provide a timeline for fixes.

## Security Updates

Security updates are released as soon as possible after a vulnerability is confirmed. Subscribe to GitHub releases to stay informed.

## Compliance

This package follows security best practices and is designed to be compliant with:
- OWASP Top 10
- Laravel security guidelines
- Google OAuth 2.0 security requirements
- GDPR (data encryption, right to deletion)

## License

This security policy is part of the Laravel YouTube package and is covered under the MIT License.

---

**Last Updated:** 2025-11-10
**Package Version:** 0.1.0
