<?php

namespace Ekstremedia\LaravelYouTube\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class YouTubeApiAuth
{
    /**
     * Handle an incoming request.
     *
     * Supports both Laravel Sanctum/Passport authentication and API key authentication
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Skip authentication if not required
        if (! config('youtube.routes.auth_required', true)) {
            return $next($request);
        }

        // Check if user is authenticated via session/Sanctum
        if ($request->user()) {
            return $next($request);
        }

        // Check for API key authentication
        $apiKey = config('youtube.security.api_key');
        $headerName = config('youtube.security.api_key_header', 'X-YouTube-API-Key');

        if ($apiKey && $request->header($headerName) === $apiKey) {
            return $next($request);
        }

        // Neither authentication method succeeded
        logger()->warning('YouTube API access denied: Authentication failed', [
            'ip' => $request->ip(),
            'route' => $request->path(),
            'has_user' => (bool) $request->user(),
            'has_api_key_header' => $request->hasHeader($headerName),
        ]);

        abort(401, 'Unauthorized');
    }
}
