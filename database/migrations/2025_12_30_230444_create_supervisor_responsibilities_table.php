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
        Schema::create('supervisor_responsibilities', function (Blueprint $table) {
            $table->id();
            $table->foreignId('supervisor_profile_id')->constrained('supervisor_profiles')->cascadeOnDelete();
            // Manual morph columns to avoid long index name
            $table->string('responsable_type');
            $table->unsignedBigInteger('responsable_id');
            $table->timestamps();

            // Custom shorter index name for morphs columns
            $table->index(['responsable_type', 'responsable_id'], 'sup_resp_morph_idx');

            // Composite unique index to prevent duplicate assignments
            $table->unique(
                ['supervisor_profile_id', 'responsable_type', 'responsable_id'],
                'supervisor_resp_unique'
            );
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('supervisor_responsibilities');
    }
};
