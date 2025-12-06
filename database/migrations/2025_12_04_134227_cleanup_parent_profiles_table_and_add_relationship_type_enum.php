<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. Drop unwanted columns from parent_profiles
        Schema::table('parent_profiles', function (Blueprint $table) {
            $table->dropColumn([
                'workplace',
                'national_id',
                'passport_number',
                'emergency_contact_name',
                'emergency_contact_phone',
            ]);
        });

        // 2. Rename notes to admin_notes in parent_profiles
        Schema::table('parent_profiles', function (Blueprint $table) {
            $table->renameColumn('notes', 'admin_notes');
        });

        // 3. Update relationship_type enum in parent_student_relationships to only have: father, mother, other
        // First, update any existing values to match the new enum
        DB::table('parent_student_relationships')
            ->whereNotIn('relationship_type', ['father', 'mother', 'other'])
            ->update(['relationship_type' => 'other']);

        // Then modify the enum column
        DB::statement("ALTER TABLE parent_student_relationships MODIFY relationship_type ENUM('father', 'mother', 'other') DEFAULT 'other'");

        // 4. Drop relationship_type from parent_profiles as it should only be in the pivot table
        if (Schema::hasColumn('parent_profiles', 'relationship_type')) {
            Schema::table('parent_profiles', function (Blueprint $table) {
                $table->dropColumn('relationship_type');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Add back the dropped columns
        Schema::table('parent_profiles', function (Blueprint $table) {
            $table->string('workplace')->nullable();
            $table->string('national_id')->nullable();
            $table->string('passport_number')->nullable();
            $table->string('emergency_contact_name')->nullable();
            $table->string('emergency_contact_phone')->nullable();
        });

        // Rename admin_notes back to notes
        Schema::table('parent_profiles', function (Blueprint $table) {
            $table->renameColumn('admin_notes', 'notes');
        });

        // Add back relationship_type to parent_profiles
        Schema::table('parent_profiles', function (Blueprint $table) {
            $table->enum('relationship_type', ['father', 'mother', 'guardian', 'relative', 'other'])->nullable();
        });

        // Restore original relationship_type enum in pivot table
        DB::statement("ALTER TABLE parent_student_relationships MODIFY relationship_type ENUM('father', 'mother', 'guardian', 'relative', 'other') DEFAULT NULL");
    }
};
