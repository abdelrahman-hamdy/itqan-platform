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
        Schema::create('supervisor_profiles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->onDelete('cascade');
            $table->string('email')->unique();
            $table->string('first_name');
            $table->string('last_name');
            $table->string('phone');
            $table->string('avatar')->nullable();
            $table->string('supervisor_code')->unique();
            $table->enum('department', ['quran', 'academic', 'recorded_courses', 'general'])->default('general');
            $table->enum('supervision_level', ['junior', 'senior', 'lead'])->default('junior');
            $table->json('assigned_teachers')->nullable();
            $table->json('monitoring_permissions')->nullable();
            $table->enum('reports_access_level', ['basic', 'detailed', 'full'])->default('basic');
            $table->date('hired_date');
            $table->date('contract_end_date')->nullable();
            $table->decimal('salary', 10, 2)->nullable();
            $table->decimal('performance_rating', 3, 2)->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('supervisor_profiles');
    }
};
