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
        Schema::create('academy_google_settings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('academy_id')->constrained()->cascadeOnDelete();
            
            // Google Cloud Project Settings
            $table->string('google_project_id')->nullable();
            $table->string('google_client_id')->nullable();
            $table->text('google_client_secret')->nullable(); // encrypted
            $table->text('google_service_account_key')->nullable(); // encrypted JSON
            
            // OAuth Settings
            $table->string('oauth_redirect_uri')->nullable();
            $table->json('oauth_scopes')->nullable();
            
            // Platform Fallback Account
            $table->string('fallback_account_email')->nullable();
            $table->text('fallback_account_credentials')->nullable(); // encrypted
            $table->boolean('fallback_account_enabled')->default(false);
            $table->integer('fallback_daily_limit')->default(100);
            
            // Meeting Settings
            $table->boolean('auto_create_meetings')->default(true);
            $table->integer('meeting_prep_minutes')->default(60); // 1 hour before
            $table->boolean('auto_record_sessions')->default(false);
            $table->integer('default_session_duration')->default(60); // minutes
            
            // Notification Settings
            $table->boolean('notify_on_teacher_disconnect')->default(true);
            $table->boolean('send_meeting_reminders')->default(true);
            $table->json('reminder_times')->nullable(); // [60, 15] minutes before
            
            // Integration Status
            $table->boolean('is_configured')->default(false);
            $table->timestamp('last_tested_at')->nullable();
            $table->text('last_test_result')->nullable();
            $table->timestamp('configured_at')->nullable();
            $table->foreignId('configured_by')->nullable()->constrained('users')->nullOnDelete();
            
            $table->timestamps();
            
            // Ensure one setting per academy
            $table->unique('academy_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('academy_google_settings');
    }
};