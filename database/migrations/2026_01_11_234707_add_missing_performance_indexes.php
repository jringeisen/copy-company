<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Performance indexes for common query patterns identified through codebase analysis.
     */
    public function up(): void
    {
        // Phase 1: Critical indexes for most frequent queries

        // Subscribers - most critical for newsletter operations
        Schema::table('subscribers', function (Blueprint $table) {
            $table->index('brand_id', 'idx_subscribers_brand_id');
            $table->index(['brand_id', 'status'], 'idx_subscribers_brand_status');
        });

        // Posts - brand listing and filtering
        Schema::table('posts', function (Blueprint $table) {
            $table->index('brand_id', 'idx_posts_brand_id');
        });

        // Social posts - brand listing and queue operations
        Schema::table('social_posts', function (Blueprint $table) {
            $table->index('brand_id', 'idx_social_posts_brand_id');
        });

        // Phase 2: High priority indexes

        // Newsletter sends - listing and management
        Schema::table('newsletter_sends', function (Blueprint $table) {
            $table->index('brand_id', 'idx_newsletter_sends_brand_id');
        });

        // Loop items - social post exclusion queries
        Schema::table('loop_items', function (Blueprint $table) {
            $table->index('social_post_id', 'idx_loop_items_social_post_id');
        });

        // Content sprints - brand listing
        Schema::table('content_sprints', function (Blueprint $table) {
            $table->index('brand_id', 'idx_content_sprints_brand_id');
        });

        // Phase 3: Medium priority indexes

        // Email events - subscriber bounce tracking
        Schema::table('email_events', function (Blueprint $table) {
            $table->index('subscriber_id', 'idx_email_events_subscriber_id');
        });

        // Account user - user's accounts lookup
        Schema::table('account_user', function (Blueprint $table) {
            $table->index('user_id', 'idx_account_user_user_id');
        });

        // Media folders - brand folder listing
        Schema::table('media_folders', function (Blueprint $table) {
            $table->index('brand_id', 'idx_media_folders_brand_id');
        });

        // Media - user uploaded media tracking
        Schema::table('media', function (Blueprint $table) {
            $table->index('user_id', 'idx_media_user_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('subscribers', function (Blueprint $table) {
            $table->dropIndex('idx_subscribers_brand_id');
            $table->dropIndex('idx_subscribers_brand_status');
        });

        Schema::table('posts', function (Blueprint $table) {
            $table->dropIndex('idx_posts_brand_id');
        });

        Schema::table('social_posts', function (Blueprint $table) {
            $table->dropIndex('idx_social_posts_brand_id');
        });

        Schema::table('newsletter_sends', function (Blueprint $table) {
            $table->dropIndex('idx_newsletter_sends_brand_id');
        });

        Schema::table('loop_items', function (Blueprint $table) {
            $table->dropIndex('idx_loop_items_social_post_id');
        });

        Schema::table('content_sprints', function (Blueprint $table) {
            $table->dropIndex('idx_content_sprints_brand_id');
        });

        Schema::table('email_events', function (Blueprint $table) {
            $table->dropIndex('idx_email_events_subscriber_id');
        });

        Schema::table('account_user', function (Blueprint $table) {
            $table->dropIndex('idx_account_user_user_id');
        });

        Schema::table('media_folders', function (Blueprint $table) {
            $table->dropIndex('idx_media_folders_brand_id');
        });

        Schema::table('media', function (Blueprint $table) {
            $table->dropIndex('idx_media_user_id');
        });
    }
};
