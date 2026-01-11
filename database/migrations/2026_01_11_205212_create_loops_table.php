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
        Schema::create('loops', function (Blueprint $table) {
            $table->id();
            $table->foreignId('brand_id')->constrained()->onDelete('cascade');

            $table->string('name');
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);

            // Target platforms (JSON array of platform identifiers)
            $table->json('platforms');

            // Current position tracking
            $table->unsignedBigInteger('current_position')->default(0);
            $table->unsignedBigInteger('total_cycles_completed')->default(0);
            $table->timestamp('last_posted_at')->nullable();

            $table->timestamps();

            $table->index(['brand_id', 'is_active']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('loops');
    }
};
