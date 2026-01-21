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
        Schema::table('brands', function (Blueprint $table) {
            $table->string('ses_configuration_set')->nullable()->after('email_domain_verified_at');
            $table->string('ses_dedicated_ip_pool')->nullable()->after('ses_configuration_set');
            $table->string('dedicated_ip_address')->nullable()->after('ses_dedicated_ip_pool');
            $table->string('dedicated_ip_status')->default('none')->after('dedicated_ip_address');
            $table->timestamp('dedicated_ip_provisioned_at')->nullable()->after('dedicated_ip_status');
            $table->timestamp('dedicated_ip_warmup_started_at')->nullable()->after('dedicated_ip_provisioned_at');
            $table->timestamp('dedicated_ip_warmup_completed_at')->nullable()->after('dedicated_ip_warmup_started_at');
            $table->unsignedTinyInteger('warmup_day')->nullable()->after('dedicated_ip_warmup_completed_at');
            $table->json('warmup_daily_stats')->nullable()->after('warmup_day');
            $table->timestamp('last_warmup_send_at')->nullable()->after('warmup_daily_stats');
            $table->boolean('warmup_paused')->default(false)->after('last_warmup_send_at');

            $table->index('dedicated_ip_status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('brands', function (Blueprint $table) {
            $table->dropIndex(['dedicated_ip_status']);

            $table->dropColumn([
                'ses_configuration_set',
                'ses_dedicated_ip_pool',
                'dedicated_ip_address',
                'dedicated_ip_status',
                'dedicated_ip_provisioned_at',
                'dedicated_ip_warmup_started_at',
                'dedicated_ip_warmup_completed_at',
                'warmup_day',
                'warmup_daily_stats',
                'last_warmup_send_at',
                'warmup_paused',
            ]);
        });
    }
};
