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
        Schema::create('loop_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('loop_id')->constrained()->onDelete('cascade');
            $table->foreignId('social_post_id')->nullable()->constrained()->onDelete('set null');

            // Position/ordering within the loop
            $table->unsignedInteger('position')->default(0);

            // Standalone content (for imported CSV content not tied to SocialPost)
            $table->text('content')->nullable();
            $table->string('platform')->nullable();
            $table->string('format')->default('feed');
            $table->json('hashtags')->nullable();
            $table->string('link')->nullable();
            $table->json('media')->nullable();

            // Tracking
            $table->unsignedInteger('times_posted')->default(0);
            $table->timestamp('last_posted_at')->nullable();

            $table->timestamps();

            $table->index(['loop_id', 'position']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('loop_items');
    }
};
