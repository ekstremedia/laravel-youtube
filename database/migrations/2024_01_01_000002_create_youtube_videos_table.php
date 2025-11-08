<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('youtube_videos', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->nullable()->index();
            $table->unsignedBigInteger('token_id')->index();
            $table->string('video_id')->unique();
            $table->string('channel_id')->index();
            $table->string('title');
            $table->text('description')->nullable();
            $table->json('tags')->nullable();
            $table->string('category_id')->nullable();
            $table->string('privacy_status')->default('private');
            $table->string('license')->nullable();
            $table->boolean('embeddable')->default(true);
            $table->boolean('public_stats_viewable')->default(true);
            $table->boolean('made_for_kids')->default(false);
            $table->string('default_language')->nullable();
            $table->string('default_audio_language')->nullable();
            $table->string('recording_date')->nullable();
            $table->string('video_url')->nullable();
            $table->string('embed_url')->nullable();
            $table->text('thumbnail_default')->nullable();
            $table->text('thumbnail_medium')->nullable();
            $table->text('thumbnail_high')->nullable();
            $table->text('thumbnail_standard')->nullable();
            $table->text('thumbnail_maxres')->nullable();
            $table->string('duration')->nullable();
            $table->string('definition')->nullable(); // hd or sd
            $table->string('caption')->nullable();
            $table->boolean('licensed_content')->default(false);
            $table->string('projection')->nullable(); // rectangular or 360
            $table->bigInteger('view_count')->default(0);
            $table->bigInteger('like_count')->default(0);
            $table->bigInteger('dislike_count')->default(0);
            $table->bigInteger('comment_count')->default(0);
            $table->string('upload_status')->nullable();
            $table->string('failure_reason')->nullable();
            $table->string('rejection_reason')->nullable();
            $table->string('processing_status')->nullable();
            $table->integer('processing_progress')->nullable();
            $table->text('processing_details')->nullable();
            $table->timestamp('published_at')->nullable();
            $table->timestamp('scheduled_start_time')->nullable();
            $table->timestamp('scheduled_end_time')->nullable();
            $table->timestamp('actual_start_time')->nullable();
            $table->timestamp('actual_end_time')->nullable();
            $table->json('live_streaming_details')->nullable();
            $table->json('statistics')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamp('synced_at')->nullable();
            $table->timestamps();

            // Foreign keys
            $table->foreign('user_id')
                  ->references('id')
                  ->on('users')
                  ->onDelete('cascade');

            $table->foreign('token_id')
                  ->references('id')
                  ->on('youtube_tokens')
                  ->onDelete('cascade');

            // Indexes for performance
            $table->index(['user_id', 'privacy_status', 'created_at']);
            $table->index(['channel_id', 'privacy_status']);
            $table->index(['upload_status', 'processing_status']);
            $table->index('published_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('youtube_videos');
    }
};