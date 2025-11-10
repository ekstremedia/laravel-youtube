<?php

namespace Ekstremedia\LaravelYouTube\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Symfony\Component\HttpFoundation\Response;

class YouTubeRateLimit
{
    /**
     * Handle an incoming request.
     *
     * Enforces rate limiting for YouTube API operations
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Skip if rate limiting is disabled
        if (! config('youtube.rate_limit.enabled', true)) {
            return $next($request);
        }

        $user = $request->user();
        $userId = $user ? $user->id : $request->ip();
        $key = 'youtube_rate_limit:' . $userId;

        // Check per-minute limit
        $perMinuteLimit = config('youtube.rate_limit.max_requests_per_minute', 60);
        $perMinuteKey = $key . ':minute';

        if (RateLimiter::tooManyAttempts($perMinuteKey, $perMinuteLimit)) {
            $seconds = RateLimiter::availableIn($perMinuteKey);

            logger()->warning('YouTube API rate limit exceeded (per minute)', [
                'user_id' => $userId,
                'route' => $request->path(),
            ]);

            return response()->json([
                'message' => 'Too many requests. Please slow down.',
                'retry_after' => $seconds,
            ], 429)->header('Retry-After', (string) $seconds);
        }

        RateLimiter::hit($perMinuteKey, 60); // 1 minute window

        // Check per-hour limit
        $perHourLimit = config('youtube.rate_limit.max_requests_per_hour', 3000);
        $perHourKey = $key . ':hour';

        if (RateLimiter::tooManyAttempts($perHourKey, $perHourLimit)) {
            $seconds = RateLimiter::availableIn($perHourKey);

            logger()->warning('YouTube API rate limit exceeded (per hour)', [
                'user_id' => $userId,
                'route' => $request->path(),
            ]);

            return response()->json([
                'message' => 'Hourly rate limit exceeded. Please try again later.',
                'retry_after' => $seconds,
            ], 429)->header('Retry-After', (string) $seconds);
        }

        RateLimiter::hit($perHourKey, 3600); // 1 hour window

        return $next($request);
    }
}
