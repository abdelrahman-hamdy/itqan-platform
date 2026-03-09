<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Add missing foreign key constraints for academy_id columns added in earlier migrations.
 *
 * Affected tables:
 *  - password_reset_tokens.academy_id  (added 2026-02-21)
 *  - student_profiles.academy_id       (added 2026-02-19)
 *
 * Both use nullOnDelete() so orphaned rows are set to NULL rather than blocked
 * when an academy is deleted.
 */
return new class extends Migration
{
    public function up(): void
    {
        // password_reset_tokens — academy_id is part of the composite PK
        // MySQL treats PK columns as NOT NULL even when declared nullable in schema,
        // so we use cascadeOnDelete to avoid the NOT NULL / SET NULL conflict.
        Schema::table('password_reset_tokens', function (Blueprint $table) {
            $table->foreign('academy_id')
                ->references('id')
                ->on('academies')
                ->cascadeOnDelete();
        });

        // student_profiles — academy_id is a regular nullable indexed column
        Schema::table('student_profiles', function (Blueprint $table) {
            $table->foreign('academy_id')
                ->references('id')
                ->on('academies')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('student_profiles', function (Blueprint $table) {
            $table->dropForeign(['academy_id']);
        });

        Schema::table('password_reset_tokens', function (Blueprint $table) {
            $table->dropForeign(['academy_id']);
        });
    }
};
