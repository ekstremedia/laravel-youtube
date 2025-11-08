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
        Schema::create('youtube_tokens', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->nullable()->index();
            $table->string('channel_id')->nullable()->index();
            $table->string('channel_title')->nullable();
            $table->string('channel_handle')->nullable();
            $table->text('channel_thumbnail')->nullable();
            $table->text('access_token');
            $table->text('refresh_token');
            $table->string('token_type')->default('Bearer');
            $table->integer('expires_in')->nullable();
            $table->timestamp('expires_at')->nullable()->index();
            $table->json('scopes')->nullable();
            $table->json('channel_metadata')->nullable();
            $table->boolean('is_active')->default(true)->index();
            $table->timestamp('last_refreshed_at')->nullable();
            $table->integer('refresh_count')->default(0);
            $table->string('error')->nullable();
            $table->timestamp('error_at')->nullable();
            $table->timestamps();

            // Add foreign key if users table exists
            $table->foreign('user_id')
                  ->references('id')
                  ->on('users')
                  ->onDelete('cascade');

            // Composite index for efficient queries
            $table->index(['user_id', 'is_active', 'expires_at']);
            $table->index(['channel_id', 'is_active']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('youtube_tokens');
    }
};