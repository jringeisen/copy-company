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
        Schema::create('email_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('subscriber_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('newsletter_send_id')->nullable()->constrained()->nullOnDelete();
            $table->string('ses_message_id')->index();
            $table->string('event_type'); // sent, delivery, bounce, complaint, open, click
            $table->json('event_data')->nullable();
            $table->string('link_url')->nullable();
            $table->timestamp('event_at');
            $table->timestamps();

            $table->index(['newsletter_send_id', 'event_type']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('email_events');
    }
};
