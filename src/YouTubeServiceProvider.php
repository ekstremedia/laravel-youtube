<?php

namespace Ekstremedia\LaravelYouTube;

use Ekstremedia\LaravelYouTube\Console\Commands\ClearExpiredTokensCommand;
use Ekstremedia\LaravelYouTube\Console\Commands\RefreshTokensCommand;
use Ekstremedia\LaravelYouTube\Http\Middleware\YouTubeRateLimit;
use Ekstremedia\LaravelYouTube\Services\AuthService;
use Ekstremedia\LaravelYouTube\Services\TokenManager;
use Ekstremedia\LaravelYouTube\Services\YouTubeService;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Routing\Router;
use Illuminate\Support\ServiceProvider;

class YouTubeServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        // Merge configuration
        $this->mergeConfigFrom(
            __DIR__ . '/../config/youtube.php',
            'youtube'
        );

        // Register the main YouTube service as a singleton
        $this->app->singleton(YouTubeService::class, function ($app) {
            return new YouTubeService(
                $app->make(TokenManager::class),
                $app->make(AuthService::class),
                $app['config']['youtube']
            );
        });

        // Register the token manager as a singleton
        $this->app->singleton(TokenManager::class, function ($app) {
            return new TokenManager(
                $app['config']['youtube.storage'],
                $app['cache'],
                $app['db']
            );
        });

        // Register the auth service as a singleton
        $this->app->singleton(AuthService::class, function ($app) {
            return new AuthService(
                $app['config']['youtube.credentials'],
                $app['config']['youtube.scopes']
            );
        });

        // Register alias for the facade
        $this->app->alias(YouTubeService::class, 'youtube');
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        // Register middleware
        $router = $this->app->make(Router::class);
        $router->aliasMiddleware('youtube.ratelimit', YouTubeRateLimit::class);

        // Publish configuration
        $this->publishes([
            __DIR__ . '/../config/youtube.php' => config_path('youtube.php'),
        ], 'youtube-config');

        // Publish migrations
        $this->publishes([
            __DIR__ . '/../database/migrations' => database_path('migrations'),
        ], 'youtube-migrations');

        // Publish views
        $this->publishes([
            __DIR__ . '/../resources/views' => resource_path('views/vendor/youtube'),
        ], 'youtube-views');

        // Load migrations
        $this->loadMigrationsFrom(__DIR__ . '/../database/migrations');

        // Load views
        $this->loadViewsFrom(__DIR__ . '/../resources/views', 'youtube');

        // Load routes
        if (config('youtube.routes.enabled')) {
            $this->loadRoutesFrom(__DIR__ . '/../routes/web.php');
        }

        // Register console commands if running in console
        if ($this->app->runningInConsole()) {
            $this->commands([
                RefreshTokensCommand::class,
                ClearExpiredTokensCommand::class,
            ]);

            // Schedule token refresh
            $this->app->booted(function () {
                $schedule = $this->app->make(Schedule::class);
                $schedule->command('youtube:refresh-tokens')->hourly();
                $schedule->command('youtube:clear-expired-tokens')->daily();
            });
        }
    }
}
