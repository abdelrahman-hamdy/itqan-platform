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
        // Disable foreign key checks temporarily
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');

        try {
            // Step 1: Migrate all data from subjects to academic_subjects
            $subjects = DB::table('subjects')->get();

            foreach ($subjects as $subject) {
                // Check if record already exists
                $exists = DB::table('academic_subjects')->where('id', $subject->id)->exists();

                if (!$exists) {
                    DB::table('academic_subjects')->insert([
                        'id' => $subject->id,
                        'academy_id' => $subject->academy_id,
                        'name' => $subject->name,
                        'name_en' => $subject->name_en ?? null,
                        'description' => $subject->description ?? null,
                        'is_active' => $subject->is_active ?? true,
                        'created_at' => $subject->created_at ?? now(),
                        'updated_at' => $subject->updated_at ?? now(),
                    ]);
                } else {
                    // Update existing record
                    DB::table('academic_subjects')->where('id', $subject->id)->update([
                        'academy_id' => $subject->academy_id,
                        'name' => $subject->name,
                        'name_en' => $subject->name_en ?? null,
                        'description' => $subject->description ?? null,
                        'is_active' => $subject->is_active ?? true,
                        'updated_at' => $subject->updated_at ?? now(),
                    ]);
                }
            }

            // Step 2: Drop the subjects table
            Schema::dropIfExists('subjects');

            // Step 3: Drop related pivot tables that were using subjects
            Schema::dropIfExists('teacher_subjects');
            Schema::dropIfExists('subject_grade_levels');

        } finally {
            // Re-enable foreign key checks
            DB::statement('SET FOREIGN_KEY_CHECKS=1;');
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Recreate subjects table
        Schema::create('subjects', function (Blueprint $table) {
            $table->id();
            $table->foreignId('academy_id')->constrained()->onDelete('cascade');
            $table->string('name');
            $table->string('name_en')->nullable();
            $table->string('subject_code')->nullable();
            $table->text('description')->nullable();
            $table->text('admin_notes')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // Migrate data back from academic_subjects to subjects
        $academicSubjects = DB::table('academic_subjects')->get();

        foreach ($academicSubjects as $subject) {
            DB::table('subjects')->insert([
                'id' => $subject->id,
                'academy_id' => $subject->academy_id,
                'name' => $subject->name,
                'name_en' => $subject->name_en,
                'description' => $subject->description,
                'is_active' => $subject->is_active,
                'created_at' => $subject->created_at,
                'updated_at' => $subject->updated_at,
            ]);
        }

        // Recreate pivot tables
        Schema::create('teacher_subjects', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('subject_id')->constrained('subjects')->onDelete('cascade');
            $table->string('role')->default('teacher');
            $table->timestamps();
        });

        Schema::create('subject_grade_levels', function (Blueprint $table) {
            $table->id();
            $table->foreignId('subject_id')->constrained('subjects')->onDelete('cascade');
            $table->foreignId('academic_grade_level_id')->constrained()->onDelete('cascade');
            $table->timestamps();
        });
    }
};
