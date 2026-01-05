<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('supervisor_profiles', function (Blueprint $table) {
            $table->boolean('can_manage_teachers')->default(false)->after('notes');
        });

        // Clean up old responsibility types that are no longer used
        // Old types: QuranCircle, QuranIndividualCircle, AcademicIndividualLesson
        // We'll delete these since we're moving to teacher-based supervision
        DB::table('supervisor_responsibilities')
            ->whereIn('responsable_type', [
                'App\\Models\\QuranCircle',
                'App\\Models\\QuranIndividualCircle',
                'App\\Models\\AcademicIndividualLesson',
            ])
            ->delete();
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('supervisor_profiles', function (Blueprint $table) {
            $table->dropColumn('can_manage_teachers');
        });
    }
};
