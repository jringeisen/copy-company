<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Additional performance indexes for common query patterns.
     */
    public function up(): void
    {
        // Posts - public blog filtering
        Schema::table('posts', function (Blueprint $table) {
            $table->index('publish_to_blog', 'idx_posts_publish_to_blog');
        });

        // Social posts - brand + status compound index for listing with filters
        Schema::table('social_posts', function (Blueprint $table) {
            $table->index(['brand_id', 'status'], 'idx_social_posts_brand_status');
        });

        // Newsletter sends - brand + status compound index for listing with filters
        Schema::table('newsletter_sends', function (Blueprint $table) {
            $table->index(['brand_id', 'status'], 'idx_newsletter_sends_brand_status');
        });

        // Content sprints - brand + status compound index for listing with filters
        Schema::table('content_sprints', function (Blueprint $table) {
            $table->index(['brand_id', 'status'], 'idx_content_sprints_brand_status');
        });

        // Account invitations - expires_at for validity checks
        Schema::table('account_invitations', function (Blueprint $table) {
            $table->index('expires_at', 'idx_account_invitations_expires_at');
        });

        // Email usages - account + reported status for unreported usage queries
        Schema::table('email_usages', function (Blueprint $table) {
            $table->index(['account_id', 'reported_to_stripe'], 'idx_email_usages_account_reported');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('posts', function (Blueprint $table) {
            $table->dropIndex('idx_posts_publish_to_blog');
        });

        Schema::table('social_posts', function (Blueprint $table) {
            $table->dropIndex('idx_social_posts_brand_status');
        });

        Schema::table('newsletter_sends', function (Blueprint $table) {
            $table->dropIndex('idx_newsletter_sends_brand_status');
        });

        Schema::table('content_sprints', function (Blueprint $table) {
            $table->dropIndex('idx_content_sprints_brand_status');
        });

        Schema::table('account_invitations', function (Blueprint $table) {
            $table->dropIndex('idx_account_invitations_expires_at');
        });

        Schema::table('email_usages', function (Blueprint $table) {
            $table->dropIndex('idx_email_usages_account_reported');
        });
    }
};
