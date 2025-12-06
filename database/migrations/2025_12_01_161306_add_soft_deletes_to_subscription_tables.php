<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Add soft deletes (deleted_at) column to subscription tables
 *
 * BaseSubscription uses SoftDeletes trait, so all subscription tables need
 * the deleted_at column.
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Add deleted_at to academic_subscriptions if missing
        if (!Schema::hasColumn('academic_subscriptions', 'deleted_at')) {
            Schema::table('academic_subscriptions', function (Blueprint $table) {
                $table->softDeletes();
            });
        }

        // Add deleted_at to quran_subscriptions if missing
        if (!Schema::hasColumn('quran_subscriptions', 'deleted_at')) {
            Schema::table('quran_subscriptions', function (Blueprint $table) {
                $table->softDeletes();
            });
        }

        // Add deleted_at to course_subscriptions if missing
        if (!Schema::hasColumn('course_subscriptions', 'deleted_at')) {
            Schema::table('course_subscriptions', function (Blueprint $table) {
                $table->softDeletes();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('academic_subscriptions', function (Blueprint $table) {
            $table->dropSoftDeletes();
        });

        Schema::table('quran_subscriptions', function (Blueprint $table) {
            $table->dropSoftDeletes();
        });

        Schema::table('course_subscriptions', function (Blueprint $table) {
            $table->dropSoftDeletes();
        });
    }
};
