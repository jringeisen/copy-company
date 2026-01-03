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
        Schema::create('newsletter_sends', function (Blueprint $table) {
            $table->id();
            $table->foreignId('post_id')->constrained()->onDelete('cascade');
            $table->foreignId('brand_id')->constrained()->onDelete('cascade');

            // Email details
            $table->string('subject_line');
            $table->string('preview_text')->nullable();

            // Provider tracking
            $table->string('provider')->default('built_in');
            $table->string('external_campaign_id')->nullable();

            // Status
            $table->string('status')->default('draft');
            $table->timestamp('scheduled_at')->nullable();
            $table->timestamp('sent_at')->nullable();

            // Analytics
            $table->integer('recipients_count')->default(0);
            $table->integer('opens')->default(0);
            $table->integer('unique_opens')->default(0);
            $table->integer('clicks')->default(0);
            $table->integer('unique_clicks')->default(0);
            $table->integer('unsubscribes')->default(0);

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('newsletter_sends');
    }
};
