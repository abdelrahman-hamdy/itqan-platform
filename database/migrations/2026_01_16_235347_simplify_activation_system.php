<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Simplify Activation System Migration
 *
 * This migration consolidates the over-engineered activation system into a single
 * source of truth: User.active_status (boolean).
 *
 * BEFORE:
 * - User: active_status, is_active (redundant)
 * - QuranTeacherProfile: is_active, approval_status, approved_by, approved_at
 * - AcademicTeacherProfile: is_active, approval_status, approved_by, approved_at
 *
 * AFTER:
 * - User: active_status (single source of truth for ALL user types)
 * - TeacherProfiles: No activation fields (rely on User.active_status)
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Step 1: Sync User.active_status from teacher profiles BEFORE dropping columns
        // For Quran teachers: set User.active_status = true if profile.is_active = true
        if (Schema::hasColumn('quran_teacher_profiles', 'is_active')) {
            DB::statement("
                UPDATE users u
                INNER JOIN quran_teacher_profiles qtp ON u.id = qtp.user_id
                SET u.active_status = qtp.is_active
                WHERE qtp.user_id IS NOT NULL
            ");
        }

        // For Academic teachers: set User.active_status = true if profile.is_active = true
        if (Schema::hasColumn('academic_teacher_profiles', 'is_active')) {
            DB::statement("
                UPDATE users u
                INNER JOIN academic_teacher_profiles atp ON u.id = atp.user_id
                SET u.active_status = atp.is_active
                WHERE atp.user_id IS NOT NULL
            ");
        }

        // Step 2: Remove redundant columns from quran_teacher_profiles
        Schema::table('quran_teacher_profiles', function (Blueprint $table) {
            if (Schema::hasColumn('quran_teacher_profiles', 'is_active')) {
                $table->dropColumn('is_active');
            }
            if (Schema::hasColumn('quran_teacher_profiles', 'approval_status')) {
                $table->dropColumn('approval_status');
            }
            if (Schema::hasColumn('quran_teacher_profiles', 'approved_by')) {
                // Drop foreign key first if it exists
                try {
                    $table->dropForeign(['approved_by']);
                } catch (\Exception $e) {
                    // Foreign key may not exist
                }
                $table->dropColumn('approved_by');
            }
            if (Schema::hasColumn('quran_teacher_profiles', 'approved_at')) {
                $table->dropColumn('approved_at');
            }
        });

        // Step 3: Remove redundant columns from academic_teacher_profiles
        Schema::table('academic_teacher_profiles', function (Blueprint $table) {
            if (Schema::hasColumn('academic_teacher_profiles', 'is_active')) {
                $table->dropColumn('is_active');
            }
            if (Schema::hasColumn('academic_teacher_profiles', 'approval_status')) {
                $table->dropColumn('approval_status');
            }
            if (Schema::hasColumn('academic_teacher_profiles', 'approved_by')) {
                // Drop foreign key first if it exists
                try {
                    $table->dropForeign(['approved_by']);
                } catch (\Exception $e) {
                    // Foreign key may not exist
                }
                $table->dropColumn('approved_by');
            }
            if (Schema::hasColumn('academic_teacher_profiles', 'approved_at')) {
                $table->dropColumn('approved_at');
            }
        });

        // Step 4: Remove redundant is_active from users table (keeping active_status only)
        Schema::table('users', function (Blueprint $table) {
            if (Schema::hasColumn('users', 'is_active')) {
                $table->dropColumn('is_active');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Restore is_active to users table
        Schema::table('users', function (Blueprint $table) {
            if (! Schema::hasColumn('users', 'is_active')) {
                $table->boolean('is_active')->default(true)->after('active_status');
            }
        });

        // Restore columns to quran_teacher_profiles
        Schema::table('quran_teacher_profiles', function (Blueprint $table) {
            if (! Schema::hasColumn('quran_teacher_profiles', 'is_active')) {
                $table->boolean('is_active')->default(true)->after('package_ids');
            }
            if (! Schema::hasColumn('quran_teacher_profiles', 'approval_status')) {
                $table->string('approval_status')->default('approved')->after('is_active');
            }
            if (! Schema::hasColumn('quran_teacher_profiles', 'approved_by')) {
                $table->unsignedBigInteger('approved_by')->nullable()->after('approval_status');
            }
            if (! Schema::hasColumn('quran_teacher_profiles', 'approved_at')) {
                $table->timestamp('approved_at')->nullable()->after('approved_by');
            }
        });

        // Restore columns to academic_teacher_profiles
        Schema::table('academic_teacher_profiles', function (Blueprint $table) {
            if (! Schema::hasColumn('academic_teacher_profiles', 'is_active')) {
                $table->boolean('is_active')->default(true)->after('package_ids');
            }
            if (! Schema::hasColumn('academic_teacher_profiles', 'approval_status')) {
                $table->string('approval_status')->default('approved')->after('is_active');
            }
            if (! Schema::hasColumn('academic_teacher_profiles', 'approved_by')) {
                $table->unsignedBigInteger('approved_by')->nullable()->after('approval_status');
            }
            if (! Schema::hasColumn('academic_teacher_profiles', 'approved_at')) {
                $table->timestamp('approved_at')->nullable()->after('approved_by');
            }
        });

        // Sync back from User.active_status to profile.is_active
        DB::statement("
            UPDATE quran_teacher_profiles qtp
            INNER JOIN users u ON qtp.user_id = u.id
            SET qtp.is_active = u.active_status
            WHERE qtp.user_id IS NOT NULL
        ");

        DB::statement("
            UPDATE academic_teacher_profiles atp
            INNER JOIN users u ON atp.user_id = u.id
            SET atp.is_active = u.active_status
            WHERE atp.user_id IS NOT NULL
        ");
    }
};
