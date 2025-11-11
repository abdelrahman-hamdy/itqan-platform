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
        Schema::create('academy_settings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('academy_id')->constrained()->onDelete('cascade');
            $table->string('timezone')->default('Asia/Riyadh');
            $table->unsignedInteger('default_session_duration')->default(60);
            $table->unsignedInteger('default_preparation_minutes')->default(15);
            $table->unsignedInteger('default_buffer_minutes')->default(5);
            $table->unsignedInteger('default_late_tolerance_minutes')->default(10);
            $table->decimal('default_attendance_threshold_percentage', 5, 2)->default(80.00);
            $table->unsignedInteger('trial_session_duration')->default(30);
            $table->unsignedInteger('trial_expiration_days')->default(7);
            $table->json('settings')->nullable();
            $table->timestamps();
            
            $table->unique('academy_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('academy_settings');
    }
};
