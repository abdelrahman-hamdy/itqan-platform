<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Add sponsorship fields to quran_subscriptions
        Schema::table('quran_subscriptions', function (Blueprint $table) {
            $table->boolean('is_sponsored')->default(false)->after('is_trial_active');
            $table->text('sponsorship_reason')->nullable()->after('is_sponsored');
        });

        // Add allow_sponsored_requests to quran_circles
        Schema::table('quran_circles', function (Blueprint $table) {
            $table->boolean('allow_sponsored_requests')->default(false)->after('certificates_enabled');
        });

        // Create sponsored enrollment requests table
        Schema::create('sponsored_enrollment_requests', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('academy_id');
            $table->unsignedBigInteger('student_id');
            $table->unsignedBigInteger('circle_id');
            $table->string('status', 20)->default('pending'); // pending, approved, rejected
            $table->unsignedBigInteger('reviewed_by')->nullable();
            $table->timestamp('reviewed_at')->nullable();
            $table->text('rejection_reason')->nullable();
            $table->timestamps();

            $table->foreign('academy_id')->references('id')->on('academies')->cascadeOnDelete();
            $table->foreign('student_id')->references('id')->on('users')->cascadeOnDelete();
            $table->foreign('circle_id')->references('id')->on('quran_circles')->cascadeOnDelete();
            $table->foreign('reviewed_by')->references('id')->on('users')->nullOnDelete();

            $table->index(['circle_id', 'status']);
            $table->index(['student_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sponsored_enrollment_requests');

        Schema::table('quran_circles', function (Blueprint $table) {
            $table->dropColumn('allow_sponsored_requests');
        });

        Schema::table('quran_subscriptions', function (Blueprint $table) {
            $table->dropColumn(['is_sponsored', 'sponsorship_reason']);
        });
    }
};
