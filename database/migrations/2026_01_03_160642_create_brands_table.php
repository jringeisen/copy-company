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
        Schema::create('brands', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');

            // Brand identity
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('tagline')->nullable();
            $table->text('description')->nullable();
            $table->string('logo_path')->nullable();
            $table->string('favicon_path')->nullable();

            // Custom domain
            $table->string('custom_domain')->nullable()->unique();
            $table->boolean('domain_verified')->default(false);

            // Branding colors
            $table->string('primary_color')->default('#6366f1');
            $table->string('secondary_color')->default('#1f2937');

            // Industry for AI context
            $table->string('industry')->nullable();

            // Voice/tone settings for AI
            $table->json('voice_settings')->nullable();

            // Newsletter settings
            $table->string('newsletter_provider')->default('built_in');
            $table->text('newsletter_credentials')->nullable();

            // Social connections
            $table->text('social_connections')->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('brands');
    }
};
