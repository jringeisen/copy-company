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
            $table->string('custom_email_domain')->nullable()->after('custom_domain');
            $table->string('custom_email_from')->nullable()->after('custom_email_domain');
            $table->string('email_domain_verification_status')->default('none')->after('custom_email_from');
            $table->json('email_domain_dns_records')->nullable()->after('email_domain_verification_status');
            $table->timestamp('email_domain_verified_at')->nullable()->after('email_domain_dns_records');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('brands', function (Blueprint $table) {
            $table->dropColumn([
                'custom_email_domain',
                'custom_email_from',
                'email_domain_verification_status',
                'email_domain_dns_records',
                'email_domain_verified_at',
            ]);
        });
    }
};
