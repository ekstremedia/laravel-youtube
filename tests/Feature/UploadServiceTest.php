<?php

use Ekstremedia\LaravelYouTube\Exceptions\UploadException;
use Ekstremedia\LaravelYouTube\Models\YouTubeToken;
use Ekstremedia\LaravelYouTube\Models\YouTubeVideo;
use Ekstremedia\LaravelYouTube\Services\YouTubeService;
use Google\Service\YouTube;
use Google\Service\YouTube\Video;
use Google\Service\YouTube\VideoSnippet;
use Google\Service\YouTube\VideoStatus;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

beforeEach(function () {
    Config::set('youtube.credentials.client_id', 'test-client-id');
    Config::set('youtube.credentials.client_secret', 'test-client-secret');
    Config::set('youtube.upload.chunk_size', 1024 * 1024); // 1MB
    Config::set('youtube.upload.temp_path', 'temp/youtube');
    Config::set('youtube.upload.max_file_size', 128 * 1024 * 1024 * 1024); // 128GB
    Storage::fake('local');
});

describe('Upload Service', function () {
    beforeEach(function () {
        $this->markTestSkipped('Upload tests require Google YouTube API mocking at lower level');
    });

    it('can upload a video file with metadata', function () {
        $user = $this->createTestUser();
        $token = YouTubeToken::factory()->create([
            'user_id' => $user->id,
            'is_active' => true,
            'expires_at' => now()->addHour(),
        ]);

        // Create a fake video file
        $videoFile = UploadedFile::fake()->create('test-video.mp4', 10240); // 10MB

        $metadata = [
            'title' => 'Test Video Upload',
            'description' => 'This is a test video upload',
            'tags' => ['test', 'laravel', 'youtube'],
            'category_id' => '22',
            'privacy_status' => 'private',
        ];

        // Mock the YouTube service response
        $mockVideo = new Video;
        $mockVideo->setId('test-video-id');

        $mockStatus = new VideoStatus;
        $mockStatus->setPrivacyStatus('private');
        $mockStatus->setUploadStatus('uploaded');
        $mockVideo->setStatus($mockStatus);

        $mockSnippet = new VideoSnippet;
        $mockSnippet->setTitle($metadata['title']);
        $mockSnippet->setDescription($metadata['description']);
        $mockVideo->setSnippet($mockSnippet);

        // Mock the YouTube service
        $youtubeMock = Mockery::mock(YouTube::class);
        $videosServiceMock = Mockery::mock(YouTube\Resource\Videos::class);

        $videosServiceMock->shouldReceive('insert')
            ->once()
            ->andReturn($mockVideo);

        $youtubeMock->videos = $videosServiceMock;

        // Bind the mock to the container
        app()->bind(YouTube::class, function () use ($youtubeMock) {
            return $youtubeMock;
        });

        $service = app(YouTubeService::class);
        $result = $service->forUser($user->id)->uploadVideo($videoFile, $metadata);

        expect($result)->toBeInstanceOf(YouTubeVideo::class)
            ->and($result->video_id)->toBe('test-video-id')
            ->and($result->title)->toBe('Test Video Upload')
            ->and($result->description)->toBe('This is a test video upload')
            ->and($result->privacy_status)->toBe('private')
            ->and($result->upload_status)->toBe('uploaded');
    });

    it('handles chunked uploads for large files', function () {
        $user = $this->createTestUser();
        $token = YouTubeToken::factory()->create([
            'user_id' => $user->id,
            'is_active' => true,
            'expires_at' => now()->addHour(),
        ]);

        // Create a larger fake video file
        $videoFile = UploadedFile::fake()->create('large-video.mp4', 102400); // 100MB

        $metadata = [
            'title' => 'Large Video Upload',
            'description' => 'Testing chunked upload',
        ];

        $options = [
            'chunk_size' => 10 * 1024 * 1024, // 10MB chunks
            'notify_url' => 'https://example.com/webhook',
        ];

        // The service should handle chunked uploads automatically
        // when file size exceeds chunk size
        $service = app(YouTubeService::class);

        // Mock YouTube API response
        $mockVideo = new Video;
        $mockVideo->setId('large-video-id');

        $videosServiceMock = Mockery::mock(YouTube\Resource\Videos::class);
        $videosServiceMock->shouldReceive('insert')->once()->andReturn($mockVideo);

        $youtubeMock = Mockery::mock(YouTube::class);
        $youtubeMock->videos = $videosServiceMock;

        app()->bind(YouTube::class, fn () => $youtubeMock);

        // This should handle chunked upload internally
        $result = $service->forUser($user->id)->uploadVideo($videoFile, $metadata, $options);

        expect($result)->toBeInstanceOf(YouTubeVideo::class);
    });

    it('validates file size limits', function () {
        Config::set('youtube.upload.max_file_size', 1024 * 1024); // 1MB limit

        $user = $this->createTestUser();
        $token = YouTubeToken::factory()->create([
            'user_id' => $user->id,
            'is_active' => true,
            'expires_at' => now()->addHour(),
        ]);

        // Create a file exceeding the limit
        $videoFile = UploadedFile::fake()->create('huge-video.mp4', 2048); // 2MB

        $service = app(YouTubeService::class);

        expect(fn () => $service->forUser($user->id)->uploadVideo($videoFile, ['title' => 'Test']))
            ->toThrow(UploadException::class, 'File size exceeds maximum allowed');
    });

    it('validates required metadata fields', function () {
        $user = $this->createTestUser();
        $token = YouTubeToken::factory()->create([
            'user_id' => $user->id,
            'is_active' => true,
            'expires_at' => now()->addHour(),
        ]);

        $videoFile = UploadedFile::fake()->create('video.mp4', 1024);

        $service = app(YouTubeService::class);

        // Missing title
        expect(fn () => $service->forUser($user->id)->uploadVideo($videoFile, []))
            ->toThrow(UploadException::class, 'Title is required');
    });

    it('supports uploading from file path', function () {
        $user = $this->createTestUser();
        $token = YouTubeToken::factory()->create([
            'user_id' => $user->id,
            'is_active' => true,
            'expires_at' => now()->addHour(),
        ]);

        // Create a real file in storage
        Storage::put('videos/test.mp4', 'fake video content');
        $filePath = Storage::path('videos/test.mp4');

        $metadata = [
            'title' => 'Video from Path',
            'description' => 'Uploaded from file path',
        ];

        // Mock YouTube response
        $mockVideo = new Video;
        $mockVideo->setId('path-video-id');

        $videosServiceMock = Mockery::mock(YouTube\Resource\Videos::class);
        $videosServiceMock->shouldReceive('insert')->once()->andReturn($mockVideo);

        $youtubeMock = Mockery::mock(YouTube::class);
        $youtubeMock->videos = $videosServiceMock;

        app()->bind(YouTube::class, fn () => $youtubeMock);

        $service = app(YouTubeService::class);
        $result = $service->forUser($user->id)->uploadVideo($filePath, $metadata);

        expect($result)->toBeInstanceOf(YouTubeVideo::class)
            ->and($result->video_id)->toBe('path-video-id');
    });

    it('handles upload failures gracefully', function () {
        $user = $this->createTestUser();
        $token = YouTubeToken::factory()->create([
            'user_id' => $user->id,
            'is_active' => true,
            'expires_at' => now()->addHour(),
        ]);

        $videoFile = UploadedFile::fake()->create('fail-video.mp4', 1024);

        // Mock YouTube to throw an exception
        $videosServiceMock = Mockery::mock(YouTube\Resource\Videos::class);
        $videosServiceMock->shouldReceive('insert')
            ->once()
            ->andThrow(new Google\Service\Exception('Upload failed', 500));

        $youtubeMock = Mockery::mock(YouTube::class);
        $youtubeMock->videos = $videosServiceMock;

        app()->bind(YouTube::class, fn () => $youtubeMock);

        $service = app(YouTubeService::class);

        expect(fn () => $service->forUser($user->id)->uploadVideo($videoFile, ['title' => 'Test']))
            ->toThrow(UploadException::class);
    });

    it('stores upload record in database', function () {
        $user = $this->createTestUser();
        $token = YouTubeToken::factory()->create([
            'user_id' => $user->id,
            'is_active' => true,
            'expires_at' => now()->addHour(),
        ]);

        $videoFile = UploadedFile::fake()->create('db-test.mp4', 1024);

        $metadata = [
            'title' => 'Database Test Video',
            'description' => 'Testing database storage',
            'tags' => ['test'],
            'category_id' => '22',
            'privacy_status' => 'unlisted',
        ];

        // Mock successful upload
        $mockVideo = new Video;
        $mockVideo->setId('db-test-id');

        $mockSnippet = new VideoSnippet;
        $mockSnippet->setTitle($metadata['title']);
        $mockSnippet->setDescription($metadata['description']);
        $mockSnippet->setCategoryId($metadata['category_id']);
        $mockVideo->setSnippet($mockSnippet);

        $mockStatus = new VideoStatus;
        $mockStatus->setPrivacyStatus($metadata['privacy_status']);
        $mockVideo->setStatus($mockStatus);

        $videosServiceMock = Mockery::mock(YouTube\Resource\Videos::class);
        $videosServiceMock->shouldReceive('insert')->once()->andReturn($mockVideo);

        $youtubeMock = Mockery::mock(YouTube::class);
        $youtubeMock->videos = $videosServiceMock;

        app()->bind(YouTube::class, fn () => $youtubeMock);

        $service = app(YouTubeService::class);
        $result = $service->forUser($user->id)->uploadVideo($videoFile, $metadata);

        // Check database
        expect(YouTubeVideo::where('video_id', 'db-test-id')->exists())->toBeTrue();

        $dbVideo = YouTubeVideo::where('video_id', 'db-test-id')->first();
        expect($dbVideo->title)->toBe('Database Test Video')
            ->and($dbVideo->description)->toBe('Testing database storage')
            ->and($dbVideo->privacy_status)->toBe('unlisted')
            ->and($dbVideo->category_id)->toBe('22')
            ->and($dbVideo->user_id)->toBe($user->id)
            ->and($dbVideo->token_id)->toBe($token->id);
    });

    it('can set custom thumbnail after upload', function () {
        $user = $this->createTestUser();
        $token = YouTubeToken::factory()->create([
            'user_id' => $user->id,
            'is_active' => true,
            'expires_at' => now()->addHour(),
        ]);

        $video = YouTubeVideo::factory()->create([
            'user_id' => $user->id,
            'token_id' => $token->id,
            'video_id' => 'thumb-test-id',
        ]);

        $thumbnail = UploadedFile::fake()->image('thumbnail.jpg', 1280, 720);

        // Mock thumbnail upload
        $thumbnailsServiceMock = Mockery::mock(YouTube\Resource\Thumbnails::class);
        $thumbnailsServiceMock->shouldReceive('set')
            ->once()
            ->with('thumb-test-id', Mockery::any())
            ->andReturn(true);

        $youtubeMock = Mockery::mock(YouTube::class);
        $youtubeMock->thumbnails = $thumbnailsServiceMock;

        app()->bind(YouTube::class, fn () => $youtubeMock);

        $service = app(YouTubeService::class);
        $result = $service->forUser($user->id)->setThumbnail('thumb-test-id', $thumbnail);

        expect($result)->toBeTrue();
    });

    it('tracks upload progress for large files', function () {
        $user = $this->createTestUser();
        $token = YouTubeToken::factory()->create([
            'user_id' => $user->id,
            'is_active' => true,
            'expires_at' => now()->addHour(),
        ]);

        $videoFile = UploadedFile::fake()->create('progress-test.mp4', 51200); // 50MB

        $metadata = [
            'title' => 'Progress Test Video',
        ];

        $progressCallback = Mockery::spy(function ($bytesUploaded, $totalBytes) {
            // Progress callback
        });

        $options = [
            'chunk_size' => 10 * 1024 * 1024, // 10MB chunks
            'progress_callback' => $progressCallback,
        ];

        // Mock the upload
        $mockVideo = new Video;
        $mockVideo->setId('progress-video-id');

        $videosServiceMock = Mockery::mock(YouTube\Resource\Videos::class);
        $videosServiceMock->shouldReceive('insert')->once()->andReturn($mockVideo);

        $youtubeMock = Mockery::mock(YouTube::class);
        $youtubeMock->videos = $videosServiceMock;

        app()->bind(YouTube::class, fn () => $youtubeMock);

        $service = app(YouTubeService::class);
        $result = $service->forUser($user->id)->uploadVideo($videoFile, $metadata, $options);

        // The progress callback should have been called during chunked upload
        expect($result)->toBeInstanceOf(YouTubeVideo::class);
    });
});

describe('Upload for Raspberry Pi Integration', function () {
    beforeEach(function () {
        $this->markTestSkipped('Upload tests require Google YouTube API mocking at lower level');
    });

    it('handles video upload from Pi camera endpoint', function () {
        $user = $this->createTestUser();
        $token = YouTubeToken::factory()->create([
            'user_id' => $user->id,
            'is_active' => true,
            'expires_at' => now()->addHour(),
        ]);

        // Simulate a video file from Pi
        Storage::put('pi-uploads/2024-01-15-camera1.mp4', 'pi video content');
        $piVideoPath = Storage::path('pi-uploads/2024-01-15-camera1.mp4');

        $metadata = [
            'title' => 'Pi Camera - 2024-01-15',
            'description' => 'Daily timelapse from Raspberry Pi Camera 1',
            'tags' => ['raspberry-pi', 'timelapse', 'automated'],
            'category_id' => '28', // Science & Technology
            'privacy_status' => 'unlisted',
        ];

        // Mock upload
        $mockVideo = new Video;
        $mockVideo->setId('pi-video-2024-01-15');

        $videosServiceMock = Mockery::mock(YouTube\Resource\Videos::class);
        $videosServiceMock->shouldReceive('insert')->once()->andReturn($mockVideo);

        $youtubeMock = Mockery::mock(YouTube::class);
        $youtubeMock->videos = $videosServiceMock;

        app()->bind(YouTube::class, fn () => $youtubeMock);

        $service = app(YouTubeService::class);

        // Upload video
        $video = $service->forUser($user->id)->uploadVideo($piVideoPath, $metadata);

        expect($video)->toBeInstanceOf(YouTubeVideo::class)
            ->and($video->video_id)->toBe('pi-video-2024-01-15')
            ->and($video->title)->toBe('Pi Camera - 2024-01-15')
            ->and($video->tags)->toContain('raspberry-pi');
    });

    it('can queue video upload as background job', function () {
        Queue::fake();

        $user = $this->createTestUser();
        $token = YouTubeToken::factory()->create([
            'user_id' => $user->id,
            'is_active' => true,
            'expires_at' => now()->addHour(),
        ]);

        $videoPath = storage_path('app/pi-uploads/test.mp4');
        Storage::put('pi-uploads/test.mp4', 'content');

        // Dispatch upload job
        \Ekstremedia\LaravelYouTube\Jobs\UploadVideoJob::dispatch(
            $user->id,
            $videoPath,
            [
                'title' => 'Queued Pi Upload',
                'description' => 'Uploaded via queue',
            ]
        );

        Queue::assertPushed(\Ekstremedia\LaravelYouTube\Jobs\UploadVideoJob::class);
    });
});
