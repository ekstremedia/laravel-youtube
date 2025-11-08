<?php

namespace EkstreMedia\LaravelYouTube\Database\Factories;

use Carbon\Carbon;
use EkstreMedia\LaravelYouTube\Models\YouTubeToken;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Crypt;

class YouTubeTokenFactory extends Factory
{
    protected $model = YouTubeToken::class;

    public function definition(): array
    {
        return [
            'user_id' => null,
            'channel_id' => $this->faker->uuid(),
            'channel_title' => $this->faker->company() . ' Channel',
            'channel_handle' => '@' . $this->faker->userName(),
            'channel_thumbnail' => $this->faker->imageUrl(240, 240),
            'access_token' => Crypt::encryptString('test-access-token-' . $this->faker->uuid()),
            'refresh_token' => Crypt::encryptString('test-refresh-token-' . $this->faker->uuid()),
            'token_type' => 'Bearer',
            'expires_in' => 3600,
            'expires_at' => Carbon::now()->addHour(),
            'scopes' => [
                'https://www.googleapis.com/auth/youtube',
                'https://www.googleapis.com/auth/youtube.upload',
            ],
            'channel_metadata' => [
                'subscriber_count' => $this->faker->numberBetween(100, 1000000),
                'video_count' => $this->faker->numberBetween(10, 1000),
                'view_count' => $this->faker->numberBetween(1000, 10000000),
            ],
            'is_active' => true,
            'last_refreshed_at' => Carbon::now(),
            'refresh_count' => 0,
            'error' => null,
            'error_at' => null,
        ];
    }

    public function expired(): Factory
    {
        return $this->state(fn (array $attributes) => [
            'expires_at' => Carbon::now()->subHour(),
        ]);
    }

    public function inactive(): Factory
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }

    public function withError(): Factory
    {
        return $this->state(fn (array $attributes) => [
            'error' => 'Token refresh failed: Invalid grant',
            'error_at' => Carbon::now(),
            'is_active' => false,
        ]);
    }
}
