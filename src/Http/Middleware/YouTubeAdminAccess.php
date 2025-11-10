<?php

namespace Ekstremedia\LaravelYouTube\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class YouTubeAdminAccess
{
    /**
     * Handle an incoming request.
     *
     * Ensures only authorized users can access the YouTube admin panel
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        // User must be authenticated
        if (! $user) {
            abort(401, 'Authentication required');
        }

        // Check for required role
        $requiredRole = config('youtube.security.admin_role');
        if ($requiredRole && method_exists($user, 'hasRole')) {
            if (! $user->hasRole($requiredRole)) {
                logger()->warning('YouTube admin access denied: Missing role', [
                    'user_id' => $user->id,
                    'required_role' => $requiredRole,
                ]);

                abort(403, 'Insufficient permissions');
            }
        }

        // Check for required permission
        $requiredPermission = config('youtube.security.admin_permission', 'manage-youtube');
        if ($requiredPermission && method_exists($user, 'can')) {
            if (! $user->can($requiredPermission)) {
                logger()->warning('YouTube admin access denied: Missing permission', [
                    'user_id' => $user->id,
                    'required_permission' => $requiredPermission,
                ]);

                abort(403, 'Insufficient permissions');
            }
        }

        // Check for verified email if required
        if (config('youtube.security.require_verified_email', false)) {
            if (method_exists($user, 'hasVerifiedEmail') && ! $user->hasVerifiedEmail()) {
                logger()->warning('YouTube admin access denied: Email not verified', [
                    'user_id' => $user->id,
                ]);

                abort(403, 'Email verification required');
            }
        }

        return $next($request);
    }
}
