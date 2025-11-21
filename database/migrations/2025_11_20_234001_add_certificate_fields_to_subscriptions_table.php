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
        // Add certificate fields to quran_subscriptions
        Schema::table('quran_subscriptions', function (Blueprint $table) {
            $table->boolean('certificate_issued')->default(false)->after('subscription_status');
            $table->timestamp('certificate_issued_at')->nullable()->after('certificate_issued');
            $table->index('certificate_issued');
        });

        // Add certificate fields to academic_subscriptions
        Schema::table('academic_subscriptions', function (Blueprint $table) {
            $table->boolean('certificate_issued')->default(false)->after('status');
            $table->timestamp('certificate_issued_at')->nullable()->after('certificate_issued');
            $table->index('certificate_issued');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('quran_subscriptions', function (Blueprint $table) {
            $table->dropIndex(['certificate_issued']);
            $table->dropColumn(['certificate_issued', 'certificate_issued_at']);
        });

        Schema::table('academic_subscriptions', function (Blueprint $table) {
            $table->dropIndex(['certificate_issued']);
            $table->dropColumn(['certificate_issued', 'certificate_issued_at']);
        });
    }
};
