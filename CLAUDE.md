# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project Overview

This is a modern Laravel package for YouTube API v3 integration with Laravel 12+. It provides OAuth2 authentication with automatic token refresh, video management, channel management, and a beautiful dark purple themed admin panel using Blade and Alpine.js.

## Development Commands

### Laravel Setup
```bash
# Install PHP dependencies
composer install

# Install JavaScript dependencies
npm install

# Copy environment file
cp .env.example .env

# Generate application key
php artisan key:generate

# Run database migrations
php artisan migrate

# Seed the database (if seeders exist)
php artisan db:seed
```

### Development Server
```bash
# Start Laravel development server
php artisan serve

# Start Vite development server for asset compilation
npm run dev

# Build assets for production
npm run build
```

### Testing
```bash
# Run all tests
php artisan test

# Run specific test file
php artisan test --filter TestClassName

# Run tests with coverage
php artisan test --coverage

# Run PHPUnit directly
./vendor/bin/phpunit

# Run specific test method
./vendor/bin/phpunit --filter testMethodName
```

### Code Quality
```bash
# Run PHP CS Fixer (if configured)
./vendor/bin/php-cs-fixer fix

# Run PHPStan (if configured)
./vendor/bin/phpstan analyse

# Clear all caches
php artisan optimize:clear

# Cache configuration for production
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

### Database
```bash
# Create a new migration
php artisan make:migration create_table_name

# Rollback last migration
php artisan migrate:rollback

# Reset database and run all migrations
php artisan migrate:fresh

# Reset database and seed
php artisan migrate:fresh --seed
```

## Architecture Overview

### Expected Structure

**Controllers**: Handle HTTP requests and return responses. Located in `app/Http/Controllers/`.

**Models**: Eloquent ORM models representing database tables. Located in `app/Models/`.

**Routes**: API routes in `routes/api.php`, web routes in `routes/web.php`.

**Services**: Business logic should be extracted into service classes in `app/Services/` for complex operations, particularly YouTube API integration logic.

**Middleware**: Request filtering and transformation in `app/Http/Middleware/`.

**Database**: Migrations in `database/migrations/`, seeders in `database/seeders/`.

### YouTube Integration Patterns

When implementing YouTube functionality:

- API credentials should be stored in `.env` file (never commit)
- Use Laravel's HTTP client or Google API PHP client for YouTube API calls
- Implement rate limiting for API requests
- Cache YouTube API responses appropriately using Laravel's cache system
- Consider using queued jobs (`app/Jobs/`) for long-running YouTube operations
- Store YouTube video metadata in the database for efficient querying

### Testing Strategy

- Feature tests should be in `tests/Feature/` for HTTP endpoints
- Unit tests should be in `tests/Unit/` for isolated logic
- Use factories (`database/factories/`) for test data generation
- Mock external YouTube API calls in tests to avoid rate limits
