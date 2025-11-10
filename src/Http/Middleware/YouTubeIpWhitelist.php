<?php

namespace Ekstremedia\LaravelYouTube\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class YouTubeIpWhitelist
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $whitelist = config('youtube.security.ip_whitelist', []);

        // If whitelist is empty, allow all IPs
        if (empty($whitelist)) {
            return $next($request);
        }

        $clientIp = $request->ip();

        // Check if client IP is in whitelist
        if (! in_array($clientIp, $whitelist)) {
            logger()->warning('YouTube API access denied: IP not whitelisted', [
                'ip' => $clientIp,
                'route' => $request->path(),
            ]);

            abort(403, 'Access denied: IP not whitelisted');
        }

        return $next($request);
    }
}
