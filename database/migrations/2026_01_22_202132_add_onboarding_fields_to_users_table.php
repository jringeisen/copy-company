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
        Schema::table('users', function (Blueprint $table) {
            $table->string('industry')->nullable()->after('email');
            $table->string('biggest_struggle')->nullable()->after('industry');
            $table->string('referral_source')->nullable()->after('biggest_struggle');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['industry', 'biggest_struggle', 'referral_source']);
        });
    }
};
