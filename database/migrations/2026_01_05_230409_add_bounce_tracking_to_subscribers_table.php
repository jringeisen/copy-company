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
        Schema::table('subscribers', function (Blueprint $table) {
            $table->string('bounce_type')->nullable()->after('status');
            $table->unsignedTinyInteger('soft_bounce_count')->default(0)->after('bounce_type');
            $table->timestamp('last_bounce_at')->nullable()->after('soft_bounce_count');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('subscribers', function (Blueprint $table) {
            $table->dropColumn(['bounce_type', 'soft_bounce_count', 'last_bounce_at']);
        });
    }
};
