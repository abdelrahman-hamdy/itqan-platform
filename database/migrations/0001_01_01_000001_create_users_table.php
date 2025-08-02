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
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('academy_id')->nullable();
            $table->string('first_name');
            $table->string('last_name');
            $table->string('name')->virtualAs("CONCAT(first_name, ' ', last_name)");
            $table->string('email')->unique();
            $table->string('phone', 20);
            $table->timestamp('email_verified_at')->nullable();
            $table->string('password');
            // Role field removed - using user_type instead
            $table->enum('status', ['pending', 'active', 'inactive', 'rejected'])->default('pending');
            $table->text('bio')->nullable();
            
            // Teacher-specific fields
            $table->enum('teacher_type', ['quran', 'academic'])->nullable();
            $table->enum('qualification_degree', ['bachelor', 'master', 'phd', 'other'])->nullable();
            $table->text('qualification_text')->nullable();
            $table->string('university')->nullable();
            $table->integer('years_experience')->nullable();
            $table->boolean('has_ijazah')->default(false);
            $table->decimal('student_session_price', 8, 2)->nullable();
            $table->decimal('teacher_session_price', 8, 2)->nullable();
            
            // Student-specific fields
            $table->string('parent_phone', 20)->nullable();
            $table->unsignedBigInteger('parent_id')->nullable();
            
            $table->string('avatar')->nullable();
            $table->rememberToken();
            $table->timestamps();
            
            // Index on academy_id, user_type will be added by later migration
            $table->index(['academy_id', 'status']);
            $table->index('teacher_type');
            $table->index('email');
        });

        Schema::create('password_reset_tokens', function (Blueprint $table) {
            $table->string('email')->primary();
            $table->string('token');
            $table->timestamp('created_at')->nullable();
        });

        Schema::create('sessions', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->foreignId('user_id')->nullable()->index();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->longText('payload');
            $table->integer('last_activity')->index();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('users');
        Schema::dropIfExists('password_reset_tokens');
        Schema::dropIfExists('sessions');
    }
};
