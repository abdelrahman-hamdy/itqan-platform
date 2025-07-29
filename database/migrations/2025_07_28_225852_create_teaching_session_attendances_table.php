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
        Schema::create('teaching_session_attendances', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id'); // Student ID
            $table->unsignedBigInteger('teaching_session_id');
            $table->enum('status', ['present', 'absent', 'late', 'excused'])->default('present');
            $table->datetime('joined_at')->nullable(); // When student joined the session
            $table->datetime('left_at')->nullable(); // When student left the session
            $table->text('notes')->nullable(); // Teacher notes about student attendance
            $table->timestamps();

            $table->unique(['user_id', 'teaching_session_id']);
            $table->index(['user_id']);
            $table->index(['teaching_session_id', 'status']);
            $table->index(['joined_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('teaching_session_attendances');
    }
};
