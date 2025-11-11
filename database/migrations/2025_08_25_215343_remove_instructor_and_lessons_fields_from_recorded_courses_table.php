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
        Schema::table('recorded_courses', function (Blueprint $table) {
            // Check if instructor_id column exists before trying to remove it
            if (Schema::hasColumn('recorded_courses', 'instructor_id')) {
                // Check if foreign key exists before trying to drop it
                $foreignKeys = $this->getForeignKeys('recorded_courses');
                if (in_array('recorded_courses_instructor_id_foreign', $foreignKeys)) {
                    $table->dropForeign(['instructor_id']);
                }
                $table->dropColumn('instructor_id');
            }

            // Remove total_lessons field as it should be calculated dynamically
            if (Schema::hasColumn('recorded_courses', 'total_lessons')) {
                $table->dropColumn('total_lessons');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('recorded_courses', function (Blueprint $table) {
            // Add back instructor_id field
            if (! Schema::hasColumn('recorded_courses', 'instructor_id')) {
                $table->foreignId('instructor_id')->nullable()->constrained('users')->onDelete('set null');
            }

            // Add back total_lessons field
            if (! Schema::hasColumn('recorded_courses', 'total_lessons')) {
                $table->integer('total_lessons')->default(0);
            }
        });
    }

    /**
     * Get foreign keys for a table
     */
    private function getForeignKeys(string $tableName): array
    {
        $foreignKeys = [];
        $constraints = DB::select("
            SELECT CONSTRAINT_NAME 
            FROM information_schema.TABLE_CONSTRAINTS 
            WHERE TABLE_SCHEMA = DATABASE() 
            AND TABLE_NAME = ? 
            AND CONSTRAINT_TYPE = 'FOREIGN KEY'
        ", [$tableName]);

        foreach ($constraints as $constraint) {
            $foreignKeys[] = $constraint->CONSTRAINT_NAME;
        }

        return $foreignKeys;
    }
};
