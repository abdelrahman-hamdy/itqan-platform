<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

/**
 * Add BaseSubscription fields to all subscription tables
 *
 * This migration adds the common fields defined in BaseSubscription to:
 * - quran_subscriptions
 * - academic_subscriptions
 * - course_subscriptions
 *
 * It also standardizes field names:
 * - subscription_status → status (quran_subscriptions)
 * - auto_renewal → auto_renew (academic_subscriptions)
 * - start_date/end_date → starts_at/ends_at (academic_subscriptions)
 *
 * Package Data Snapshot:
 * Adds package snapshot fields so subscription is self-contained
 * and doesn't depend on package table for historical accuracy
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // ================================================================
        // QURAN SUBSCRIPTIONS
        // ================================================================
        Schema::table('quran_subscriptions', function (Blueprint $table) {
            // Rename subscription_status to status for consistency
            // Note: We'll handle this with raw SQL to preserve data

            // Add missing base fields
            if (!Schema::hasColumn('quran_subscriptions', 'ends_at')) {
                $table->timestamp('ends_at')->nullable()->after('starts_at');
            }
            if (!Schema::hasColumn('quran_subscriptions', 'ended_at')) {
                $table->timestamp('ended_at')->nullable()->after('ends_at');
            }
            if (!Schema::hasColumn('quran_subscriptions', 'next_billing_date')) {
                $table->timestamp('next_billing_date')->nullable()->after('next_payment_at');
            }
            if (!Schema::hasColumn('quran_subscriptions', 'last_payment_date')) {
                $table->timestamp('last_payment_date')->nullable()->after('last_payment_at');
            }
            if (!Schema::hasColumn('quran_subscriptions', 'renewal_reminder_sent_at')) {
                $table->timestamp('renewal_reminder_sent_at')->nullable();
            }

            // Package snapshot fields
            if (!Schema::hasColumn('quran_subscriptions', 'package_name_ar')) {
                $table->string('package_name_ar')->nullable()->after('package_id');
            }
            if (!Schema::hasColumn('quran_subscriptions', 'package_name_en')) {
                $table->string('package_name_en')->nullable()->after('package_name_ar');
            }
            if (!Schema::hasColumn('quran_subscriptions', 'package_price_monthly')) {
                $table->decimal('package_price_monthly', 10, 2)->nullable();
            }
            if (!Schema::hasColumn('quran_subscriptions', 'package_price_quarterly')) {
                $table->decimal('package_price_quarterly', 10, 2)->nullable();
            }
            if (!Schema::hasColumn('quran_subscriptions', 'package_price_yearly')) {
                $table->decimal('package_price_yearly', 10, 2)->nullable();
            }
            if (!Schema::hasColumn('quran_subscriptions', 'package_sessions_per_week')) {
                $table->integer('package_sessions_per_week')->nullable();
            }
            if (!Schema::hasColumn('quran_subscriptions', 'package_session_duration_minutes')) {
                $table->integer('package_session_duration_minutes')->nullable();
            }
        });

        // Rename subscription_status to status
        if (Schema::hasColumn('quran_subscriptions', 'subscription_status') && !Schema::hasColumn('quran_subscriptions', 'status')) {
            DB::statement('ALTER TABLE quran_subscriptions CHANGE subscription_status status VARCHAR(50)');
        }

        // Copy next_payment_at to next_billing_date if empty
        DB::statement('UPDATE quran_subscriptions SET next_billing_date = next_payment_at WHERE next_billing_date IS NULL AND next_payment_at IS NOT NULL');

        // Copy last_payment_at to last_payment_date if empty
        DB::statement('UPDATE quran_subscriptions SET last_payment_date = last_payment_at WHERE last_payment_date IS NULL AND last_payment_at IS NOT NULL');

        // ================================================================
        // ACADEMIC SUBSCRIPTIONS
        // ================================================================
        Schema::table('academic_subscriptions', function (Blueprint $table) {
            // Add missing base fields
            if (!Schema::hasColumn('academic_subscriptions', 'starts_at')) {
                $table->timestamp('starts_at')->nullable()->after('end_date');
            }
            if (!Schema::hasColumn('academic_subscriptions', 'ends_at')) {
                $table->timestamp('ends_at')->nullable()->after('starts_at');
            }
            if (!Schema::hasColumn('academic_subscriptions', 'ended_at')) {
                $table->timestamp('ended_at')->nullable()->after('ends_at');
            }
            if (!Schema::hasColumn('academic_subscriptions', 'cancelled_at')) {
                $table->timestamp('cancelled_at')->nullable();
            }
            if (!Schema::hasColumn('academic_subscriptions', 'cancellation_reason')) {
                $table->text('cancellation_reason')->nullable();
            }
            if (!Schema::hasColumn('academic_subscriptions', 'auto_renew')) {
                $table->boolean('auto_renew')->default(true)->after('auto_renewal');
            }
            if (!Schema::hasColumn('academic_subscriptions', 'renewal_reminder_sent_at')) {
                $table->timestamp('renewal_reminder_sent_at')->nullable();
            }
            if (!Schema::hasColumn('academic_subscriptions', 'final_price')) {
                $table->decimal('final_price', 10, 2)->nullable();
            }

            // Package snapshot fields
            if (!Schema::hasColumn('academic_subscriptions', 'package_name_ar')) {
                $table->string('package_name_ar')->nullable()->after('academic_package_id');
            }
            if (!Schema::hasColumn('academic_subscriptions', 'package_name_en')) {
                $table->string('package_name_en')->nullable()->after('package_name_ar');
            }
            if (!Schema::hasColumn('academic_subscriptions', 'package_price_monthly')) {
                $table->decimal('package_price_monthly', 10, 2)->nullable();
            }
            if (!Schema::hasColumn('academic_subscriptions', 'package_price_quarterly')) {
                $table->decimal('package_price_quarterly', 10, 2)->nullable();
            }
            if (!Schema::hasColumn('academic_subscriptions', 'package_price_yearly')) {
                $table->decimal('package_price_yearly', 10, 2)->nullable();
            }
            if (!Schema::hasColumn('academic_subscriptions', 'package_sessions_per_week')) {
                $table->integer('package_sessions_per_week')->nullable();
            }
            if (!Schema::hasColumn('academic_subscriptions', 'package_session_duration_minutes')) {
                $table->integer('package_session_duration_minutes')->nullable();
            }
        });

        // Copy auto_renewal to auto_renew if exists
        if (Schema::hasColumn('academic_subscriptions', 'auto_renewal')) {
            DB::statement('UPDATE academic_subscriptions SET auto_renew = auto_renewal WHERE auto_renew IS NULL OR auto_renew = 0');
        }

        // Copy start_date/end_date to starts_at/ends_at
        DB::statement('UPDATE academic_subscriptions SET starts_at = start_date WHERE starts_at IS NULL AND start_date IS NOT NULL');
        DB::statement('UPDATE academic_subscriptions SET ends_at = end_date WHERE ends_at IS NULL AND end_date IS NOT NULL');

        // Copy final_monthly_amount to final_price if empty
        DB::statement('UPDATE academic_subscriptions SET final_price = final_monthly_amount WHERE final_price IS NULL AND final_monthly_amount IS NOT NULL');

        // ================================================================
        // COURSE SUBSCRIPTIONS
        // ================================================================
        Schema::table('course_subscriptions', function (Blueprint $table) {
            // Add missing base fields
            if (!Schema::hasColumn('course_subscriptions', 'starts_at')) {
                $table->timestamp('starts_at')->nullable()->after('enrolled_at');
            }
            if (!Schema::hasColumn('course_subscriptions', 'ends_at')) {
                $table->timestamp('ends_at')->nullable()->after('starts_at');
            }
            if (!Schema::hasColumn('course_subscriptions', 'ended_at')) {
                $table->timestamp('ended_at')->nullable()->after('ends_at');
            }
            if (!Schema::hasColumn('course_subscriptions', 'billing_cycle')) {
                $table->string('billing_cycle')->default('lifetime')->after('currency');
            }
            if (!Schema::hasColumn('course_subscriptions', 'auto_renew')) {
                $table->boolean('auto_renew')->default(false);
            }
            if (!Schema::hasColumn('course_subscriptions', 'next_billing_date')) {
                $table->timestamp('next_billing_date')->nullable();
            }
            if (!Schema::hasColumn('course_subscriptions', 'last_payment_date')) {
                $table->timestamp('last_payment_date')->nullable();
            }
            if (!Schema::hasColumn('course_subscriptions', 'final_price')) {
                $table->decimal('final_price', 10, 2)->nullable();
            }

            // Course-specific fields for interactive courses
            if (!Schema::hasColumn('course_subscriptions', 'interactive_course_id')) {
                $table->unsignedBigInteger('interactive_course_id')->nullable()->after('recorded_course_id');
            }
            if (!Schema::hasColumn('course_subscriptions', 'course_type')) {
                $table->string('course_type')->default('recorded')->after('subscription_code');
            }
            if (!Schema::hasColumn('course_subscriptions', 'attendance_count')) {
                $table->integer('attendance_count')->default(0);
            }
            if (!Schema::hasColumn('course_subscriptions', 'total_possible_attendance')) {
                $table->integer('total_possible_attendance')->default(0);
            }

            // Package snapshot fields
            if (!Schema::hasColumn('course_subscriptions', 'package_name_ar')) {
                $table->string('package_name_ar')->nullable();
            }
            if (!Schema::hasColumn('course_subscriptions', 'package_name_en')) {
                $table->string('package_name_en')->nullable();
            }
        });

        // Copy enrolled_at to starts_at
        DB::statement('UPDATE course_subscriptions SET starts_at = enrolled_at WHERE starts_at IS NULL AND enrolled_at IS NOT NULL');

        // Copy expires_at to ends_at
        DB::statement('UPDATE course_subscriptions SET ends_at = expires_at WHERE ends_at IS NULL AND expires_at IS NOT NULL');

        // Copy price_paid to final_price
        DB::statement('UPDATE course_subscriptions SET final_price = price_paid WHERE final_price IS NULL AND price_paid IS NOT NULL');

        // Add index for new fields
        Schema::table('quran_subscriptions', function (Blueprint $table) {
            $table->index(['status', 'auto_renew', 'next_billing_date'], 'quran_sub_renewal_idx');
        });

        Schema::table('academic_subscriptions', function (Blueprint $table) {
            $table->index(['status', 'auto_renew', 'next_billing_date'], 'academic_sub_renewal_idx');
        });

        Schema::table('course_subscriptions', function (Blueprint $table) {
            $table->index('course_type', 'course_sub_type_idx');
            $table->index('interactive_course_id', 'course_sub_interactive_idx');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Drop indexes first
        Schema::table('quran_subscriptions', function (Blueprint $table) {
            $table->dropIndex('quran_sub_renewal_idx');
        });

        Schema::table('academic_subscriptions', function (Blueprint $table) {
            $table->dropIndex('academic_sub_renewal_idx');
        });

        Schema::table('course_subscriptions', function (Blueprint $table) {
            $table->dropIndex('course_sub_type_idx');
            $table->dropIndex('course_sub_interactive_idx');
        });

        // Quran subscriptions
        Schema::table('quran_subscriptions', function (Blueprint $table) {
            $table->dropColumn([
                'ends_at', 'ended_at', 'next_billing_date', 'last_payment_date',
                'renewal_reminder_sent_at', 'package_name_ar', 'package_name_en',
                'package_price_monthly', 'package_price_quarterly', 'package_price_yearly',
                'package_sessions_per_week', 'package_session_duration_minutes',
            ]);
        });

        // Rename status back to subscription_status
        if (Schema::hasColumn('quran_subscriptions', 'status')) {
            DB::statement('ALTER TABLE quran_subscriptions CHANGE status subscription_status VARCHAR(50)');
        }

        // Academic subscriptions
        Schema::table('academic_subscriptions', function (Blueprint $table) {
            $table->dropColumn([
                'starts_at', 'ends_at', 'ended_at', 'cancelled_at', 'cancellation_reason',
                'auto_renew', 'renewal_reminder_sent_at', 'final_price',
                'package_name_ar', 'package_name_en', 'package_price_monthly',
                'package_price_quarterly', 'package_price_yearly',
                'package_sessions_per_week', 'package_session_duration_minutes',
            ]);
        });

        // Course subscriptions
        Schema::table('course_subscriptions', function (Blueprint $table) {
            $table->dropColumn([
                'starts_at', 'ends_at', 'ended_at', 'billing_cycle', 'auto_renew',
                'next_billing_date', 'last_payment_date', 'final_price',
                'interactive_course_id', 'course_type', 'attendance_count',
                'total_possible_attendance', 'package_name_ar', 'package_name_en',
            ]);
        });
    }
};
