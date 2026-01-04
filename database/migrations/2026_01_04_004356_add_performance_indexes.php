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
        // Posts table indexes
        Schema::table('posts', function (Blueprint $table) {
            $table->index('status');
            $table->index('scheduled_at');
            $table->index('published_at');
        });

        // Social posts table indexes
        Schema::table('social_posts', function (Blueprint $table) {
            $table->index('status');
            $table->index('scheduled_at');
            $table->index(['brand_id', 'platform']);
        });

        // Newsletter sends table indexes
        Schema::table('newsletter_sends', function (Blueprint $table) {
            $table->index('status');
            $table->index('scheduled_at');
            $table->index('sent_at');
        });

        // Content sprints table indexes
        Schema::table('content_sprints', function (Blueprint $table) {
            $table->index('status');
        });

        // Subscribers table indexes
        Schema::table('subscribers', function (Blueprint $table) {
            $table->index('status');
        });

        // AI prompts table indexes
        Schema::table('ai_prompts', function (Blueprint $table) {
            $table->index(['type', 'active']);
            $table->index('industry');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('posts', function (Blueprint $table) {
            $table->dropIndex(['status']);
            $table->dropIndex(['scheduled_at']);
            $table->dropIndex(['published_at']);
        });

        Schema::table('social_posts', function (Blueprint $table) {
            $table->dropIndex(['status']);
            $table->dropIndex(['scheduled_at']);
            $table->dropIndex(['brand_id', 'platform']);
        });

        Schema::table('newsletter_sends', function (Blueprint $table) {
            $table->dropIndex(['status']);
            $table->dropIndex(['scheduled_at']);
            $table->dropIndex(['sent_at']);
        });

        Schema::table('content_sprints', function (Blueprint $table) {
            $table->dropIndex(['status']);
        });

        Schema::table('subscribers', function (Blueprint $table) {
            $table->dropIndex(['status']);
        });

        Schema::table('ai_prompts', function (Blueprint $table) {
            $table->dropIndex(['type', 'active']);
            $table->dropIndex(['industry']);
        });
    }
};
