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
        Schema::create('email_usages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('account_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('emails_sent')->default(0);
            $table->date('period_date');
            $table->boolean('reported_to_stripe')->default(false);
            $table->timestamp('reported_at')->nullable();
            $table->timestamps();

            $table->unique(['account_id', 'period_date']);
            $table->index(['reported_to_stripe', 'period_date']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('email_usages');
    }
};
