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
        Schema::create('oauth_token_contexts', function (Blueprint $table) {
            $table->char('access_token_id', 100)->primary();
            $table->foreignId('brand_id')->constrained()->cascadeOnDelete();
            $table->timestamps();

            $table->index('brand_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('oauth_token_contexts');
    }
};
