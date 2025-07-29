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
        // Update quran_teachers table
        Schema::table('quran_teachers', function (Blueprint $table) {
            // Drop foreign key first
            $table->dropForeign(['user_id']);
            
            // Drop columns we don't need
            $table->dropColumn([
                'user_id',
                'available_grade_levels',
                'teaching_methods', 
                'max_students_per_circle',
                'preferred_session_duration',
                'available_times',
                'certifications',
                'teaching_philosophy'
            ]);
            
            // Add new columns
            $table->string('first_name')->after('academy_id');
            $table->string('last_name')->after('first_name');
            $table->string('email')->unique()->after('last_name');
            $table->string('phone')->after('email');
            $table->enum('educational_qualification', ['bachelor', 'master', 'phd', 'other'])->after('teaching_experience_years');
            $table->time('available_time_start')->nullable()->after('available_days');
            $table->time('available_time_end')->nullable()->after('available_time_start');
        });

        // Update quran_subscriptions table
        Schema::table('quran_subscriptions', function (Blueprint $table) {
            // Drop columns we don't need
            $table->dropColumn([
                'package_name',
                'package_type',
                'price_per_session'
            ]);
            
            // Add new columns
            $table->foreignId('package_id')->nullable()->after('quran_teacher_id')->constrained('quran_packages')->onDelete('set null');
            $table->decimal('discount_amount', 10, 2)->default(0)->after('total_price');
            $table->decimal('final_price', 10, 2)->after('discount_amount');
            
            // Update billing cycle enum
            $table->enum('billing_cycle', ['monthly', 'quarterly', 'yearly'])->change();
        });

        // Update quran_circles table
        Schema::table('quran_circles', function (Blueprint $table) {
            // Drop columns we don't need
            $table->dropColumn([
                'grade_levels',
                'age_range_min',
                'age_range_max',
                'weekly_schedule',
                'schedule_times',
                'price_per_student',
                'total_sessions_planned',
                'teaching_method',
                'assessment_methods',
                'start_date',
                'end_date',
                'registration_deadline'
            ]);
            
            // Rename columns
            $table->renameColumn('current_students', 'enrolled_students');
            
            // Add new columns
            $table->enum('age_group', ['children', 'youth', 'adults', 'all_ages'])->after('memorization_level');
            $table->enum('gender_type', ['male', 'female', 'mixed'])->after('age_group');
            $table->time('schedule_time')->nullable()->after('schedule_days');
            $table->date('actual_start_date')->nullable()->after('enrollment_status');
            $table->date('actual_end_date')->nullable()->after('actual_start_date');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Reverse quran_teachers changes
        Schema::table('quran_teachers', function (Blueprint $table) {
            $table->dropColumn([
                'first_name',
                'last_name', 
                'email',
                'phone',
                'educational_qualification',
                'available_time_start',
                'available_time_end'
            ]);
            
            $table->foreignId('user_id')->after('academy_id')->constrained()->onDelete('cascade');
            $table->json('available_grade_levels')->nullable();
            $table->json('teaching_methods')->nullable();
            $table->integer('max_students_per_circle')->default(10);
            $table->integer('preferred_session_duration')->default(60);
            $table->json('available_times')->nullable();
            $table->json('certifications')->nullable();
            $table->text('teaching_philosophy')->nullable();
        });

        // Reverse quran_subscriptions changes
        Schema::table('quran_subscriptions', function (Blueprint $table) {
            $table->dropForeign(['package_id']);
            $table->dropColumn([
                'package_id',
                'discount_amount',
                'final_price'
            ]);
            
            $table->string('package_name')->after('subscription_code');
            $table->string('package_type')->after('package_name');
            $table->decimal('price_per_session', 10, 2)->after('sessions_remaining');
        });

        // Reverse quran_circles changes
        Schema::table('quran_circles', function (Blueprint $table) {
            $table->dropColumn([
                'age_group',
                'gender_type',
                'schedule_time',
                'actual_start_date',
                'actual_end_date'
            ]);
            
            $table->renameColumn('enrolled_students', 'current_students');
            
            $table->json('grade_levels')->nullable();
            $table->integer('age_range_min')->nullable();
            $table->integer('age_range_max')->nullable();
            $table->json('weekly_schedule')->nullable();
            $table->json('schedule_times')->nullable();
            $table->decimal('price_per_student', 10, 2)->nullable();
            $table->integer('total_sessions_planned')->nullable();
            $table->string('teaching_method')->nullable();
            $table->json('assessment_methods')->nullable();
            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();
            $table->date('registration_deadline')->nullable();
        });
    }
};
