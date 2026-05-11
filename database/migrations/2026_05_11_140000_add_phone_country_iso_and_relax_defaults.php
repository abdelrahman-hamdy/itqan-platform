<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Phone-country data overhaul.
 *
 * Adds ISO alpha-2 columns (`phone_country`, `secondary_phone_country`,
 * `emergency_contact_country`) alongside the existing dial-code columns,
 * and relaxes the `NOT NULL DEFAULT '+966' / 'SA'` defaults that were
 * silently labelling every omitted-on-insert user as Saudi.
 *
 * No backfill: nationality ≠ phone country ≠ residence. See the companion
 * `app:audit-phone-data` / `app:apply-phone-corrections` artisan commands
 * for manual per-row review of historical rows.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('users')) {
            if (! Schema::hasColumn('users', 'phone_country')) {
                Schema::table('users', function ($table) {
                    $table->string('phone_country', 2)->nullable()->after('phone_country_code');
                });
            }

            DB::statement("ALTER TABLE `users` MODIFY `phone_country_code` VARCHAR(5) NULL DEFAULT NULL COMMENT 'Country calling code (e.g. +966 for Saudi Arabia)'");
        }

        if (Schema::hasTable('student_profiles')) {
            if (! Schema::hasColumn('student_profiles', 'phone_country')) {
                Schema::table('student_profiles', function ($table) {
                    $table->string('phone_country', 2)->nullable()->after('phone_country_code');
                });
            }

            if (! Schema::hasColumn('student_profiles', 'emergency_contact_country')) {
                Schema::table('student_profiles', function ($table) {
                    $table->string('emergency_contact_country', 2)->nullable()->after('emergency_contact_country_code');
                });
            }

            DB::statement("ALTER TABLE `student_profiles` MODIFY `phone_country_code` VARCHAR(5) NULL DEFAULT NULL COMMENT 'Country calling code for student phone'");
            DB::statement("ALTER TABLE `student_profiles` MODIFY `parent_phone_country_code` VARCHAR(5) NULL DEFAULT NULL COMMENT 'Country calling code for parent phone'");
            DB::statement("ALTER TABLE `student_profiles` MODIFY `parent_phone_country` VARCHAR(2) NULL DEFAULT NULL COMMENT 'ISO 3166-1 alpha-2 country code for parent phone'");
        }

        if (Schema::hasTable('parent_profiles')) {
            if (! Schema::hasColumn('parent_profiles', 'phone_country')) {
                Schema::table('parent_profiles', function ($table) {
                    $table->string('phone_country', 2)->nullable()->after('phone_country_code');
                });
            }

            if (! Schema::hasColumn('parent_profiles', 'secondary_phone_country')) {
                Schema::table('parent_profiles', function ($table) {
                    $table->string('secondary_phone_country', 2)->nullable()->after('secondary_phone_country_code');
                });
            }

            DB::statement("ALTER TABLE `parent_profiles` MODIFY `phone_country_code` VARCHAR(5) NULL DEFAULT NULL COMMENT 'Country calling code for primary phone'");
            DB::statement("ALTER TABLE `parent_profiles` MODIFY `secondary_phone_country_code` VARCHAR(5) NULL DEFAULT NULL COMMENT 'Country calling code for secondary phone'");
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('users')) {
            if (Schema::hasColumn('users', 'phone_country')) {
                Schema::table('users', function ($table) {
                    $table->dropColumn('phone_country');
                });
            }
            DB::statement("ALTER TABLE `users` MODIFY `phone_country_code` VARCHAR(5) NOT NULL DEFAULT '+966' COMMENT 'Country calling code (e.g., +966 for Saudi Arabia)'");
        }

        if (Schema::hasTable('student_profiles')) {
            if (Schema::hasColumn('student_profiles', 'phone_country')) {
                Schema::table('student_profiles', function ($table) {
                    $table->dropColumn('phone_country');
                });
            }
            if (Schema::hasColumn('student_profiles', 'emergency_contact_country')) {
                Schema::table('student_profiles', function ($table) {
                    $table->dropColumn('emergency_contact_country');
                });
            }
            DB::statement("ALTER TABLE `student_profiles` MODIFY `phone_country_code` VARCHAR(5) NOT NULL DEFAULT '+966' COMMENT 'Country calling code for student phone'");
            DB::statement("ALTER TABLE `student_profiles` MODIFY `parent_phone_country_code` VARCHAR(5) NOT NULL DEFAULT '+966' COMMENT 'Country calling code (e.g., +966 for Saudi Arabia)'");
            DB::statement("ALTER TABLE `student_profiles` MODIFY `parent_phone_country` VARCHAR(2) NOT NULL DEFAULT 'SA' COMMENT 'ISO 3166-1 alpha-2 country code'");
        }

        if (Schema::hasTable('parent_profiles')) {
            if (Schema::hasColumn('parent_profiles', 'phone_country')) {
                Schema::table('parent_profiles', function ($table) {
                    $table->dropColumn('phone_country');
                });
            }
            if (Schema::hasColumn('parent_profiles', 'secondary_phone_country')) {
                Schema::table('parent_profiles', function ($table) {
                    $table->dropColumn('secondary_phone_country');
                });
            }
            DB::statement("ALTER TABLE `parent_profiles` MODIFY `phone_country_code` VARCHAR(5) NOT NULL DEFAULT '+966' COMMENT 'Country calling code for primary phone'");
            DB::statement("ALTER TABLE `parent_profiles` MODIFY `secondary_phone_country_code` VARCHAR(5) NULL DEFAULT NULL COMMENT 'Country calling code for secondary phone'");
        }
    }
};
