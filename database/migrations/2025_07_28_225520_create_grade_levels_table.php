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
        Schema::create('grade_levels', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('academy_id');
            $table->string('name'); // Arabic name (ابتدائي، إعدادي، ثانوي، جامعي)
            $table->string('name_en')->nullable(); // English name
            $table->text('description')->nullable();
            $table->integer('level')->default(1); // Ordering level (1, 2, 3, etc.)
            $table->integer('min_age')->nullable(); // Minimum age for this level
            $table->integer('max_age')->nullable(); // Maximum age for this level
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['academy_id', 'is_active']);
            $table->index(['level']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('grade_levels');
    }
};
