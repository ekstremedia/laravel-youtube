<?php

namespace Tests;

use Ekstremedia\LaravelYouTube\YouTubeServiceProvider;
use Orchestra\Testbench\TestCase as Orchestra;

abstract class TestCase extends Orchestra
{
    protected function setUp(): void
    {
        parent::setUp();

        // Load Laravel migrations (includes users table)
        $this->loadLaravelMigrations();

        // Load package migrations
        $this->loadMigrationsFrom(__DIR__ . '/../database/migrations');

        // Run migrations
        $this->artisan('migrate')->run();
    }

    /**
     * Get package providers.
     *
     * @param  \Illuminate\Foundation\Application  $app
     * @return array
     */
    protected function getPackageProviders($app)
    {
        return [
            YouTubeServiceProvider::class,
        ];
    }

    /**
     * Define environment setup.
     *
     * @param  \Illuminate\Foundation\Application  $app
     * @return void
     */
    protected function defineEnvironment($app)
    {
        // Setup default database to use sqlite :memory:
        $app['config']->set('database.default', 'testbench');
        $app['config']->set('database.connections.testbench', [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
        ]);

        // Set encryption key for testing
        $app['config']->set('app.key', 'base64:' . base64_encode(random_bytes(32)));
        $app['config']->set('app.cipher', 'AES-256-CBC');

        // Setup YouTube config
        $app['config']->set('youtube.credentials.client_id', 'test-client-id');
        $app['config']->set('youtube.credentials.client_secret', 'test-client-secret');
        $app['config']->set('youtube.credentials.redirect_uri', '/youtube/callback');
        $app['config']->set('youtube.scopes', [
            'https://www.googleapis.com/auth/youtube',
            'https://www.googleapis.com/auth/youtube.upload',
        ]);
    }

    /**
     * Define database migrations.
     *
     * @return void
     */
    protected function defineDatabaseMigrations()
    {
        // Load Laravel's default migrations (includes users table)
        $this->loadLaravelMigrations();

        // Load package migrations
        $this->loadMigrationsFrom(__DIR__ . '/../database/migrations');

        // Run migrations
        $this->artisan('migrate', ['--database' => 'testbench'])->run();
    }

    /**
     * Create a test user for authentication testing.
     *
     * @return \Illuminate\Foundation\Auth\User
     */
    protected function createTestUser()
    {
        $user = new class extends \Illuminate\Foundation\Auth\User
        {
            protected $table = 'users';

            protected $fillable = ['name', 'email', 'password'];
        };

        $user->name = 'Test User ' . rand(1000, 9999);
        $user->email = 'test' . rand(1000, 9999) . '@example.com';
        $user->password = bcrypt('password');
        $user->save();

        return $user;
    }
}
