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
        Schema::create('social_posts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('post_id')->nullable()->constrained()->onDelete('cascade');
            $table->foreignId('brand_id')->constrained()->onDelete('cascade');

            // Platform details
            $table->string('platform');
            $table->string('format')->default('feed');

            // Content
            $table->text('content');
            $table->json('media')->nullable();
            $table->json('hashtags')->nullable();
            $table->string('link')->nullable();

            // Status
            $table->string('status')->default('draft');
            $table->timestamp('scheduled_at')->nullable();
            $table->timestamp('published_at')->nullable();

            // External tracking
            $table->string('external_id')->nullable();
            $table->json('analytics')->nullable();
            $table->text('failure_reason')->nullable();

            // AI tracking
            $table->boolean('ai_generated')->default(false);
            $table->boolean('user_edited')->default(false);

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('social_posts');
    }
};
