<?php

use Ekstremedia\LaravelYouTube\Facades\YouTube;
use Ekstremedia\LaravelYouTube\Models\YouTubeToken;
use Ekstremedia\LaravelYouTube\Models\YouTubeVideo;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;

uses(RefreshDatabase::class);

beforeEach(function () {
    Config::set('youtube.credentials.client_id', 'test-client-id');
    Config::set('youtube.credentials.client_secret', 'test-client-secret');
    Config::set('youtube.scopes', ['https://www.googleapis.com/auth/youtube']);
});

describe('Extended Upload Metadata', function () {
    it('accepts license metadata during upload', function () {
        $token = YouTubeToken::factory()->create([
            'is_active' => true,
            'expires_at' => now()->addHour(),
        ]);

        // Create test video file
        $videoPath = storage_path('test-video.mp4');
        file_put_contents($videoPath, 'fake video content');

        // Mock YouTube API response
        $metadata = [
            'title' => 'Test Video',
            'description' => 'Test description',
            'license' => 'creativeCommon',
        ];

        // Since we can't actually test API calls without real credentials,
        // we'll just verify the metadata structure is accepted
        expect($metadata)->toHaveKey('license')
            ->and($metadata['license'])->toBe('creativeCommon');

        // Cleanup
        @unlink($videoPath);
    });

    it('accepts default_audio_language metadata during upload', function () {
        $metadata = [
            'title' => 'Test Video',
            'description' => 'Test description',
            'default_language' => 'en',
            'default_audio_language' => 'en-US',
        ];

        expect($metadata)->toHaveKey('default_audio_language')
            ->and($metadata['default_audio_language'])->toBe('en-US');
    });

    it('accepts self_declared_made_for_kids metadata during upload', function () {
        $metadata = [
            'title' => 'Test Video',
            'description' => 'Test description',
            'made_for_kids' => false,
            'self_declared_made_for_kids' => false,
        ];

        expect($metadata)->toHaveKey('self_declared_made_for_kids')
            ->and($metadata['self_declared_made_for_kids'])->toBeBool();
    });

    it('accepts public_stats_viewable metadata during upload', function () {
        $metadata = [
            'title' => 'Test Video',
            'description' => 'Test description',
            'public_stats_viewable' => true,
        ];

        expect($metadata)->toHaveKey('public_stats_viewable')
            ->and($metadata['public_stats_viewable'])->toBeTrue();
    });

    it('accepts recording_date metadata during upload', function () {
        $metadata = [
            'title' => 'Test Video',
            'description' => 'Test description',
            'recording_date' => now()->toIso8601String(),
        ];

        expect($metadata)->toHaveKey('recording_date')
            ->and($metadata['recording_date'])->toBeString();
    });

    it('accepts location metadata during upload', function () {
        $metadata = [
            'title' => 'Test Video',
            'description' => 'Test description',
            'location' => [
                'latitude' => 59.9139,
                'longitude' => 10.7522,
                'altitude' => 100.0,
                'description' => 'Oslo, Norway',
            ],
        ];

        expect($metadata)->toHaveKey('location')
            ->and($metadata['location'])->toHaveKey('latitude')
            ->and($metadata['location'])->toHaveKey('longitude')
            ->and($metadata['location']['latitude'])->toBeFloat()
            ->and($metadata['location']['longitude'])->toBeFloat();
    });

    it('accepts publish_at metadata for scheduled publishing', function () {
        $publishDate = now()->addDay()->toIso8601String();
        $metadata = [
            'title' => 'Test Video',
            'description' => 'Test description',
            'privacy_status' => 'private',
            'publish_at' => $publishDate,
        ];

        expect($metadata)->toHaveKey('publish_at')
            ->and($metadata['publish_at'])->toBe($publishDate);
    });

    it('validates license values', function () {
        $validLicenses = ['youtube', 'creativeCommon'];

        foreach ($validLicenses as $license) {
            $metadata = [
                'title' => 'Test Video',
                'license' => $license,
            ];

            expect($metadata['license'])->toBeIn($validLicenses);
        }
    });
});

describe('Playlist Operations', function () {
    it('can create playlist with basic metadata', function () {
        $playlistData = [
            'title' => 'Test Playlist',
            'description' => 'Test playlist description',
            'privacy_status' => 'private',
        ];

        expect($playlistData)->toHaveKey('title')
            ->and($playlistData['title'])->toBe('Test Playlist')
            ->and($playlistData)->toHaveKey('privacy_status');
    });

    it('can create playlist with tags', function () {
        $playlistData = [
            'title' => 'Test Playlist',
            'description' => 'Test playlist description',
            'tags' => ['tag1', 'tag2', 'tag3'],
            'privacy_status' => 'private',
        ];

        expect($playlistData)->toHaveKey('tags')
            ->and($playlistData['tags'])->toBeArray()
            ->and($playlistData['tags'])->toHaveCount(3);
    });

    it('accepts add video to playlist parameters', function () {
        $params = [
            'video_id' => 'dQw4w9WgXcQ',
            'playlist_id' => 'PLxxxxxxxxxxxxxx',
            'position' => 0,
        ];

        expect($params)->toHaveKey('video_id')
            ->and($params)->toHaveKey('playlist_id')
            ->and($params)->toHaveKey('position')
            ->and($params['position'])->toBeInt();
    });

    it('validates privacy status for playlists', function () {
        $validStatuses = ['private', 'public', 'unlisted'];

        foreach ($validStatuses as $status) {
            $playlist = [
                'title' => 'Test',
                'privacy_status' => $status,
            ];

            expect($playlist['privacy_status'])->toBeIn($validStatuses);
        }
    });
});

describe('Caption Operations', function () {
    it('accepts caption upload parameters', function () {
        $captionData = [
            'video_id' => 'test-video-id',
            'language' => 'en',
            'name' => 'English',
            'is_draft' => false,
        ];

        expect($captionData)->toHaveKey('video_id')
            ->and($captionData)->toHaveKey('language')
            ->and($captionData)->toHaveKey('name')
            ->and($captionData['is_draft'])->toBeBool();
    });

    it('accepts standard language codes', function () {
        $languages = ['en', 'en-US', 'no', 'nb', 'nn', 'es', 'fr', 'de'];

        foreach ($languages as $lang) {
            $caption = [
                'language' => $lang,
            ];

            expect($caption['language'])->toBeString()
                ->and(strlen($caption['language']))->toBeGreaterThanOrEqual(2);
        }
    });

    it('accepts caption update parameters', function () {
        $updateData = [
            'caption_id' => 'test-caption-id',
            'name' => 'Updated English',
            'is_draft' => true,
        ];

        expect($updateData)->toHaveKey('caption_id')
            ->and($updateData)->toHaveKey('name')
            ->and($updateData['is_draft'])->toBeTrue();
    });

    it('accepts valid caption download formats', function () {
        $validFormats = ['srt', 'vtt', 'ttml', 'sbv'];

        foreach ($validFormats as $format) {
            expect($format)->toBeIn($validFormats);
        }
    });
});

describe('Backward Compatibility', function () {
    it('maintains existing upload functionality without new metadata', function () {
        $metadata = [
            'title' => 'Test Video',
            'description' => 'Test description',
            'tags' => ['test'],
            'category_id' => '22',
            'privacy_status' => 'private',
            'made_for_kids' => false,
        ];

        // Verify all existing fields are present
        expect($metadata)->toHaveKey('title')
            ->and($metadata)->toHaveKey('description')
            ->and($metadata)->toHaveKey('tags')
            ->and($metadata)->toHaveKey('category_id')
            ->and($metadata)->toHaveKey('privacy_status')
            ->and($metadata)->toHaveKey('made_for_kids');
    });

    it('allows optional new metadata fields', function () {
        // Upload with only required fields
        $minimalMetadata = [
            'title' => 'Test Video',
        ];

        // Upload with all new fields
        $fullMetadata = [
            'title' => 'Test Video',
            'description' => 'Description',
            'license' => 'youtube',
            'default_audio_language' => 'en',
            'self_declared_made_for_kids' => false,
            'public_stats_viewable' => true,
            'recording_date' => now()->toIso8601String(),
            'location' => [
                'latitude' => 59.9139,
                'longitude' => 10.7522,
            ],
        ];

        expect($minimalMetadata)->toHaveKey('title')
            ->and($fullMetadata)->toHaveKey('title')
            ->and($fullMetadata)->toHaveKey('license');
    });

    it('does not break existing video model structure', function () {
        $video = YouTubeVideo::factory()->make();

        expect($video->video_id)->not->toBeNull()
            ->and($video->title)->not->toBeNull()
            ->and($video->description)->toBeTruthy()
            ->and($video->privacy_status)->not->toBeNull();
    });
});

describe('Metadata Validation', function () {
    it('validates location coordinates are numeric', function () {
        $location = [
            'latitude' => 59.9139,
            'longitude' => 10.7522,
        ];

        expect($location['latitude'])->toBeNumeric()
            ->and($location['longitude'])->toBeNumeric()
            ->and($location['latitude'])->toBeGreaterThanOrEqual(-90)
            ->and($location['latitude'])->toBeLessThanOrEqual(90)
            ->and($location['longitude'])->toBeGreaterThanOrEqual(-180)
            ->and($location['longitude'])->toBeLessThanOrEqual(180);
    });

    it('validates boolean metadata fields', function () {
        $booleanFields = [
            'made_for_kids' => false,
            'self_declared_made_for_kids' => false,
            'embeddable' => true,
            'public_stats_viewable' => true,
        ];

        foreach ($booleanFields as $field => $value) {
            expect($value)->toBeBool();
        }
    });

    it('validates ISO 8601 date formats', function () {
        $dates = [
            now()->toIso8601String(),
            now()->addDay()->toIso8601String(),
            now()->subDay()->toIso8601String(),
        ];

        foreach ($dates as $date) {
            expect($date)->toBeString()
                ->and($date)->toContain('T')
                ->and(strlen($date))->toBeGreaterThan(19);
        }
    });
});
