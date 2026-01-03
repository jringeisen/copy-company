<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('newsletter_sends', function (Blueprint $table) {
            $table->string('batch_id')->nullable()->after('status');
            $table->unsignedInteger('total_recipients')->default(0)->after('batch_id');
            $table->unsignedInteger('sent_count')->default(0)->after('total_recipients');
            $table->unsignedInteger('failed_count')->default(0)->after('sent_count');
        });
    }

    public function down(): void
    {
        Schema::table('newsletter_sends', function (Blueprint $table) {
            $table->dropColumn(['batch_id', 'total_recipients', 'sent_count', 'failed_count']);
        });
    }
};
