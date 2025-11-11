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
        Schema::table('quran_circles', function (Blueprint $table) {
            // Remove educational content fields
            $table->dropColumn([
                'current_surah',
                'current_verse',
                'materials_used',
                'requirements',
                'learning_objectives',
            ]);

            // Remove start/end date fields and teacher notes
            $table->dropColumn([
                'actual_start_date',
                'actual_end_date',
                'notes',
            ]);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('quran_circles', function (Blueprint $table) {
            // Add back educational content fields
            $table->integer('current_surah')->nullable()->after('sessions_completed');
            $table->integer('current_verse')->nullable()->after('current_surah');
            $table->json('materials_used')->nullable()->after('current_verse');
            $table->json('requirements')->nullable()->after('materials_used');
            $table->json('learning_objectives')->nullable()->after('requirements');

            // Add back start/end date fields and teacher notes
            $table->date('actual_start_date')->nullable()->after('enrollment_status');
            $table->date('actual_end_date')->nullable()->after('actual_start_date');
            $table->text('notes')->nullable()->after('dropout_rate');
        });
    }
};
