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
        Schema::table('quran_individual_circles', function (Blueprint $table) {
            // Add lifetime tracking columns (persist across subscription renewals)
            $table->integer('lifetime_sessions_completed')->default(0)->after('sessions_completed');
            $table->decimal('lifetime_pages_memorized', 10, 2)->default(0)->after('papers_memorized_precise');
            $table->integer('subscription_renewal_count')->default(0)->after('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('quran_individual_circles', function (Blueprint $table) {
            $table->dropColumn([
                'lifetime_sessions_completed',
                'lifetime_pages_memorized',
                'subscription_renewal_count',
            ]);
        });
    }
};
