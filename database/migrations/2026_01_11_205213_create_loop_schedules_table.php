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
        Schema::create('loop_schedules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('loop_id')->constrained()->onDelete('cascade');

            // Schedule definition
            $table->unsignedTinyInteger('day_of_week'); // 0=Sunday, 1=Monday, etc.
            $table->time('time_of_day'); // e.g., '07:00:00'

            // Platform override (optional - if null, uses loop's platforms)
            $table->string('platform')->nullable();

            $table->timestamps();

            $table->index(['loop_id', 'day_of_week']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('loop_schedules');
    }
};
