<?php

namespace Ekstremedia\LaravelYouTube\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class YouTubeWebhookSignature
{
    /**
     * Handle an incoming request.
     *
     * Verifies webhook signatures to ensure requests come from trusted sources
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Skip if webhook signing is disabled
        if (! config('youtube.security.webhook_signing.enabled', true)) {
            return $next($request);
        }

        $secret = config('youtube.security.webhook_signing.secret');
        $headerName = config('youtube.security.webhook_signing.header', 'X-YouTube-Signature');
        $algorithm = config('youtube.security.webhook_signing.algorithm', 'sha256');

        // If no secret is configured, skip validation but log warning
        if (empty($secret)) {
            logger()->warning('YouTube webhook signature validation skipped: No secret configured');

            return $next($request);
        }

        // Get signature from header
        $signature = $request->header($headerName);

        if (empty($signature)) {
            logger()->warning('YouTube webhook signature missing', [
                'ip' => $request->ip(),
                'route' => $request->path(),
            ]);

            abort(401, 'Webhook signature missing');
        }

        // Get raw request body
        $payload = $request->getContent();

        // Calculate expected signature
        $expectedSignature = hash_hmac($algorithm, $payload, $secret);

        // Verify signature (constant-time comparison)
        if (! hash_equals($expectedSignature, $signature)) {
            logger()->warning('YouTube webhook signature invalid', [
                'ip' => $request->ip(),
                'route' => $request->path(),
            ]);

            abort(401, 'Invalid webhook signature');
        }

        return $next($request);
    }
}
