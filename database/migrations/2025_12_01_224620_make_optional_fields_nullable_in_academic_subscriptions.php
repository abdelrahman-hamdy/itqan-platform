<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Makes optional fields nullable to prevent "doesn't have a default value" errors
     */
    public function up(): void
    {
        Schema::table('academic_subscriptions', function (Blueprint $table) {
            // Sessions per week - default to 2 if not provided
            if (Schema::hasColumn('academic_subscriptions', 'sessions_per_week')) {
                $table->integer('sessions_per_week')->default(2)->change();
            }

            // Hourly rate - default to 0 if not provided
            if (Schema::hasColumn('academic_subscriptions', 'hourly_rate')) {
                $table->decimal('hourly_rate', 8, 2)->default(0)->change();
            }

            // Sessions per month - default to 8 if not provided
            if (Schema::hasColumn('academic_subscriptions', 'sessions_per_month')) {
                $table->decimal('sessions_per_month', 5, 2)->default(8)->change();
            }

            // Weekly schedule - should be nullable (JSON)
            if (Schema::hasColumn('academic_subscriptions', 'weekly_schedule')) {
                $table->json('weekly_schedule')->nullable()->change();
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('academic_subscriptions', function (Blueprint $table) {
            if (Schema::hasColumn('academic_subscriptions', 'sessions_per_week')) {
                $table->integer('sessions_per_week')->nullable(false)->change();
            }

            if (Schema::hasColumn('academic_subscriptions', 'hourly_rate')) {
                $table->decimal('hourly_rate', 8, 2)->nullable(false)->change();
            }

            if (Schema::hasColumn('academic_subscriptions', 'sessions_per_month')) {
                $table->decimal('sessions_per_month', 5, 2)->nullable(false)->change();
            }

            if (Schema::hasColumn('academic_subscriptions', 'weekly_schedule')) {
                $table->json('weekly_schedule')->nullable(false)->change();
            }
        });
    }
};
