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
        Schema::create('disputes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('account_id')->constrained()->cascadeOnDelete();
            $table->string('stripe_dispute_id')->unique();
            $table->string('stripe_charge_id')->nullable();
            $table->string('stripe_payment_intent_id')->nullable();
            $table->unsignedBigInteger('amount');
            $table->string('currency', 3)->default('usd');
            $table->string('status');
            $table->string('reason');
            $table->timestamp('evidence_due_at')->nullable();
            $table->timestamp('disputed_at')->nullable();
            $table->timestamp('resolved_at')->nullable();
            $table->string('resolution')->nullable();
            $table->boolean('funds_withdrawn')->default(false);
            $table->boolean('funds_reinstated')->default(false);
            $table->boolean('evidence_submitted')->default(false);
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index('account_id');
            $table->index('status');
            $table->index('stripe_charge_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('disputes');
    }
};
