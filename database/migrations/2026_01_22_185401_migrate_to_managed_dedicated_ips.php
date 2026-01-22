<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Migrates from Standard Dedicated IPs (manual IP management) to
     * Managed Dedicated IPs (AWS handles warmup, scaling, IP allocation).
     */
    public function up(): void
    {
        // Drop warmup-related columns from brands table
        Schema::table('brands', function (Blueprint $table) {
            $table->dropColumn([
                'ses_dedicated_ip_pool',
                'dedicated_ip_address',
                'dedicated_ip_warmup_started_at',
                'dedicated_ip_warmup_completed_at',
                'warmup_day',
                'warmup_daily_stats',
                'last_warmup_send_at',
                'warmup_paused',
            ]);
        });

        // Drop the dedicated_ips table (no longer tracking individual IPs)
        Schema::dropIfExists('dedicated_ips');

        // Update existing brands with 'warming' or 'provisioning' status to 'active'
        // since managed IPs don't require warmup tracking
        DB::table('brands')
            ->whereIn('dedicated_ip_status', ['warming', 'provisioning'])
            ->update(['dedicated_ip_status' => 'active']);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Recreate the dedicated_ips table
        Schema::create('dedicated_ips', function (Blueprint $table) {
            $table->id();
            $table->string('ip_address')->unique();
            $table->string('status')->index();
            $table->foreignId('brand_id')->nullable()->constrained()->nullOnDelete();
            $table->timestamp('purchased_at')->nullable();
            $table->timestamp('assigned_at')->nullable();
            $table->timestamp('released_at')->nullable();
            $table->timestamps();
        });

        // Add back the warmup-related columns to brands table
        Schema::table('brands', function (Blueprint $table) {
            $table->string('ses_dedicated_ip_pool')->nullable()->after('ses_configuration_set');
            $table->string('dedicated_ip_address')->nullable()->after('ses_dedicated_ip_pool');
            $table->timestamp('dedicated_ip_warmup_started_at')->nullable()->after('dedicated_ip_provisioned_at');
            $table->timestamp('dedicated_ip_warmup_completed_at')->nullable()->after('dedicated_ip_warmup_started_at');
            $table->unsignedTinyInteger('warmup_day')->nullable()->after('dedicated_ip_warmup_completed_at');
            $table->json('warmup_daily_stats')->nullable()->after('warmup_day');
            $table->timestamp('last_warmup_send_at')->nullable()->after('warmup_daily_stats');
            $table->boolean('warmup_paused')->default(false)->after('last_warmup_send_at');
        });
    }
};
