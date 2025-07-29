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
        Schema::create('subjects', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('academy_id');
            $table->string('name'); // Arabic name
            $table->string('name_en')->nullable(); // English name
            $table->text('description')->nullable();
            $table->string('category')->default('general'); // general, science, language, arts, etc.
            $table->boolean('is_academic')->default(true); // false for Quran subjects
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['academy_id', 'is_active']);
            $table->index(['is_academic', 'is_active']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('subjects');
    }
};
