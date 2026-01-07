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
        Schema::create('posts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('brand_id')->constrained()->onDelete('cascade');
            $table->foreignId('user_id')->constrained()->onDelete('cascade');

            // Core content
            $table->string('title');
            $table->string('slug');
            $table->text('excerpt')->nullable();
            $table->longText('content');
            $table->longText('content_html')->nullable();
            $table->string('featured_image')->nullable();

            // Publishing status
            $table->string('status')->default('draft');
            $table->timestamp('published_at')->nullable();
            $table->timestamp('scheduled_at')->nullable();

            // Distribution controls
            $table->boolean('publish_to_blog')->default(true);
            $table->boolean('send_as_newsletter')->default(true);
            $table->boolean('generate_social')->default(true);

            // SEO
            $table->string('seo_title')->nullable();
            $table->text('seo_description')->nullable();
            $table->json('tags')->nullable();

            // AI tracking
            $table->integer('ai_assistance_percentage')->default(0);

            // Analytics cache
            $table->integer('view_count')->default(0);
            $table->integer('email_open_count')->default(0);
            $table->integer('email_click_count')->default(0);

            $table->unique(['brand_id', 'slug']);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('posts');
    }
};
