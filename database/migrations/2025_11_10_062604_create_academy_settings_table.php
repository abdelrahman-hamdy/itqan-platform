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

            // Academy relationship (one-to-one)
            $table->foreignId('academy_id')->unique()->constrained()->onDelete('cascade');

            // General settings
            $table->string('timezone', 50)->default('Asia/Riyadh');
            $table->integer('default_session_duration')->default(60); // in minutes

            // Attendance settings
            $table->integer('default_preparation_minutes')->default(15);
            $table->integer('default_buffer_minutes')->default(5);
            $table->integer('default_late_tolerance_minutes')->default(10);
            $table->decimal('default_attendance_threshold_percentage', 5, 2)->default(80.00);

            // Trial session settings
            $table->integer('trial_session_duration')->default(30);
            $table->integer('trial_expiration_days')->default(7);

            // Additional flexible settings (JSON)
            $table->json('settings')->nullable();

            $table->timestamps();
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
