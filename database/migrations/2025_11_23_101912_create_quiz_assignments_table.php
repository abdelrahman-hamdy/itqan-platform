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
        Schema::create('quiz_assignments', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('quiz_id')->constrained()->cascadeOnDelete();
            $table->uuidMorphs('assignable');
            $table->boolean('is_visible')->default(true);
            $table->timestamp('available_from')->nullable();
            $table->timestamp('available_until')->nullable();
            $table->unsignedTinyInteger('max_attempts')->default(1);
            $table->timestamps();

            $table->index(['assignable_type', 'assignable_id', 'is_visible'], 'quiz_assignments_assignable_visible_index');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('quiz_assignments');
    }
};
