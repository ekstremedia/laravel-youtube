<?php

namespace EkstreMedia\LaravelYouTube\Database\Factories;

use Carbon\Carbon;
use EkstreMedia\LaravelYouTube\Models\YouTubeVideo;
use Illuminate\Database\Eloquent\Factories\Factory;

class YouTubeVideoFactory extends Factory
{
    protected $model = YouTubeVideo::class;

    public function definition(): array
    {
        $videoId = $this->faker->regexify('[A-Za-z0-9]{11}');

        return [
            'user_id' => null,
            'token_id' => null,
            'video_id' => $videoId,
            'channel_id' => $this->faker->uuid(),
            'title' => $this->faker->sentence(6),
            'description' => $this->faker->paragraph(3),
            'tags' => $this->faker->words(5),
            'category_id' => $this->faker->randomElement(['1', '2', '10', '17', '19', '20', '22', '24', '25', '26', '27', '28']),
            'privacy_status' => $this->faker->randomElement(['private', 'unlisted', 'public']),
            'license' => 'youtube',
            'embeddable' => true,
            'public_stats_viewable' => true,
            'made_for_kids' => false,
            'default_language' => 'en',
            'default_audio_language' => 'en',
            'recording_date' => $this->faker->dateTimeBetween('-1 year', 'now'),
            'video_url' => "https://www.youtube.com/watch?v={$videoId}",
            'embed_url' => "https://www.youtube.com/embed/{$videoId}",
            'thumbnail_default' => $this->faker->imageUrl(120, 90),
            'thumbnail_medium' => $this->faker->imageUrl(320, 180),
            'thumbnail_high' => $this->faker->imageUrl(480, 360),
            'thumbnail_standard' => $this->faker->imageUrl(640, 480),
            'thumbnail_maxres' => $this->faker->imageUrl(1280, 720),
            'duration' => 'PT' . $this->faker->numberBetween(1, 59) . 'M' . $this->faker->numberBetween(0, 59) . 'S',
            'definition' => $this->faker->randomElement(['hd', 'sd']),
            'caption' => $this->faker->randomElement(['true', 'false', null]),
            'licensed_content' => $this->faker->boolean(),
            'projection' => 'rectangular',
            'view_count' => $this->faker->numberBetween(0, 1000000),
            'like_count' => $this->faker->numberBetween(0, 10000),
            'dislike_count' => $this->faker->numberBetween(0, 1000),
            'comment_count' => $this->faker->numberBetween(0, 5000),
            'upload_status' => 'uploaded',
            'failure_reason' => null,
            'rejection_reason' => null,
            'processing_status' => 'succeeded',
            'processing_progress' => 100,
            'processing_details' => null,
            'published_at' => $this->faker->dateTimeBetween('-1 year', 'now'),
            'scheduled_start_time' => null,
            'scheduled_end_time' => null,
            'actual_start_time' => null,
            'actual_end_time' => null,
            'live_streaming_details' => null,
            'statistics' => [
                'viewCount' => $this->faker->numberBetween(0, 1000000),
                'likeCount' => $this->faker->numberBetween(0, 10000),
                'dislikeCount' => $this->faker->numberBetween(0, 1000),
                'commentCount' => $this->faker->numberBetween(0, 5000),
            ],
            'metadata' => [],
            'synced_at' => Carbon::now(),
        ];
    }

    public function public(): Factory
    {
        return $this->state(fn (array $attributes) => [
            'privacy_status' => 'public',
        ]);
    }

    public function private(): Factory
    {
        return $this->state(fn (array $attributes) => [
            'privacy_status' => 'private',
        ]);
    }

    public function unlisted(): Factory
    {
        return $this->state(fn (array $attributes) => [
            'privacy_status' => 'unlisted',
        ]);
    }

    public function processing(): Factory
    {
        return $this->state(fn (array $attributes) => [
            'processing_status' => 'processing',
            'processing_progress' => $this->faker->numberBetween(0, 99),
        ]);
    }

    public function failed(): Factory
    {
        return $this->state(fn (array $attributes) => [
            'processing_status' => 'failed',
            'failure_reason' => 'Processing failed due to invalid video format',
        ]);
    }

    public function live(): Factory
    {
        return $this->state(fn (array $attributes) => [
            'live_streaming_details' => [
                'actualStartTime' => Carbon::now()->subHour(),
                'scheduledStartTime' => Carbon::now()->subHours(2),
                'concurrentViewers' => $this->faker->numberBetween(10, 10000),
            ],
        ]);
    }
}
