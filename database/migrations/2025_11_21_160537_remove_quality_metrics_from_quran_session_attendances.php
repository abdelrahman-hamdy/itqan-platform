<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Removes 5 quality metric fields from quran_session_attendances:
     * - papers_memorized_today
     * - pages_memorized_today
     * - recitation_quality
     * - tajweed_accuracy
     * - verses_memorized_today
     *
     * User confirmed: These are not shown in UI and are covered by homework grading system.
     *
     * KEEPS review-related fields:
     * - homework_completion
     * - papers_reviewed_today
     * - pages_reviewed_today
     * - verses_reviewed
     */
    public function up(): void
    {
        Schema::table('quran_session_attendances', function (Blueprint $table) {
            if (Schema::hasColumn('quran_session_attendances', 'papers_memorized_today')) {
                $table->dropColumn('papers_memorized_today');
            }
            if (Schema::hasColumn('quran_session_attendances', 'pages_memorized_today')) {
                $table->dropColumn('pages_memorized_today');
            }
            if (Schema::hasColumn('quran_session_attendances', 'recitation_quality')) {
                $table->dropColumn('recitation_quality');
            }
            if (Schema::hasColumn('quran_session_attendances', 'tajweed_accuracy')) {
                $table->dropColumn('tajweed_accuracy');
            }
            if (Schema::hasColumn('quran_session_attendances', 'verses_memorized_today')) {
                $table->dropColumn('verses_memorized_today');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('quran_session_attendances', function (Blueprint $table) {
            $table->decimal('papers_memorized_today', 5, 2)->nullable();
            $table->decimal('pages_memorized_today', 5, 2)->nullable();
            $table->decimal('recitation_quality', 3, 1)->nullable();
            $table->decimal('tajweed_accuracy', 3, 1)->nullable();
            $table->integer('verses_memorized_today')->nullable();
        });
    }
};
