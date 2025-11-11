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
        Schema::create('youtube_uploads', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('token_id')->constrained('youtube_tokens')->cascadeOnDelete();
            $table->string('file_path');
            $table->string('file_name');
            $table->bigInteger('file_size');
            $table->string('title');
            $table->text('description')->nullable();
            $table->json('tags')->nullable();
            $table->string('privacy_status')->default('private');
            $table->string('category_id')->nullable();
            $table->string('playlist_id')->nullable();
            $table->string('upload_status')->default('pending'); // pending, uploading, processing, completed, failed
            $table->string('youtube_video_id')->nullable();
            $table->text('error_message')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->integer('progress')->default(0);
            $table->timestamps();

            $table->index('upload_status');
            $table->index('user_id');
            $table->index('token_id');
            $table->index('youtube_video_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('youtube_uploads');
    }
};
