<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Helper method to add index only if it doesn't exist
     */
    private function addIndexIfNotExists(string $tableName, string $indexName, array $columns): void
    {
        try {
            Schema::table($tableName, function (Blueprint $table) use ($columns) {
                $table->index($columns);
            });
        } catch (\Exception $e) {
            // Index might already exist or there's another issue, ignore
            // We could log this if needed: \Log::info("Index creation skipped: " . $e->getMessage());
        }
    }
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Update quran_individual_circles table
        Schema::table('quran_individual_circles', function (Blueprint $table) {
            // Add new paper-based fields only if they don't exist
            if (!Schema::hasColumn('quran_individual_circles', 'current_page')) {
                $table->integer('current_page')->nullable()->after('current_verse')->comment('الصفحة الحالية في المصحف');
            }
            if (!Schema::hasColumn('quran_individual_circles', 'current_face')) {
                $table->integer('current_face')->nullable()->after('current_page')->comment('الوجه الحالي (1 أو 2)');
            }
            if (!Schema::hasColumn('quran_individual_circles', 'papers_memorized')) {
                $table->integer('papers_memorized')->default(0)->after('verses_memorized')->comment('عدد الأوجه المحفوظة');
            }
            if (!Schema::hasColumn('quran_individual_circles', 'papers_memorized_precise')) {
                $table->decimal('papers_memorized_precise', 8, 2)->default(0)->after('papers_memorized')->comment('عدد الأوجه المحفوظة بدقة (مع الكسور)');
            }
        });
        
        // Add indexes after column creation (only if they don't exist)
        if (Schema::hasColumn('quran_individual_circles', 'current_page') && 
            Schema::hasColumn('quran_individual_circles', 'current_face')) {
            $this->addIndexIfNotExists('quran_individual_circles', 'quran_individual_circles_current_page_current_face_index', ['current_page', 'current_face']);
        }
        if (Schema::hasColumn('quran_individual_circles', 'papers_memorized')) {
            $this->addIndexIfNotExists('quran_individual_circles', 'quran_individual_circles_papers_memorized_index', ['papers_memorized']);
        }

        // Update quran_sessions table
        Schema::table('quran_sessions', function (Blueprint $table) {
            // Add new paper-based fields only if they don't exist
            if (!Schema::hasColumn('quran_sessions', 'current_page')) {
                $table->integer('current_page')->nullable()->after('current_verse')->comment('الصفحة الحالية');
            }
            if (!Schema::hasColumn('quran_sessions', 'current_face')) {
                $table->integer('current_face')->nullable()->after('current_page')->comment('الوجه الحالي (1 أو 2)');
            }
            if (!Schema::hasColumn('quran_sessions', 'page_covered_start')) {
                $table->integer('page_covered_start')->nullable()->after('verses_covered_end')->comment('بداية الصفحة المغطاة');
            }
            if (!Schema::hasColumn('quran_sessions', 'face_covered_start')) {
                $table->integer('face_covered_start')->nullable()->after('page_covered_start')->comment('بداية الوجه المغطى (1 أو 2)');
            }
            if (!Schema::hasColumn('quran_sessions', 'page_covered_end')) {
                $table->integer('page_covered_end')->nullable()->after('face_covered_start')->comment('نهاية الصفحة المغطاة');
            }
            if (!Schema::hasColumn('quran_sessions', 'face_covered_end')) {
                $table->integer('face_covered_end')->nullable()->after('page_covered_end')->comment('نهاية الوجه المغطى (1 أو 2)');
            }
            if (!Schema::hasColumn('quran_sessions', 'papers_memorized_today')) {
                $table->decimal('papers_memorized_today', 5, 2)->default(0)->after('verses_memorized_today')->comment('عدد الأوجه المحفوظة اليوم');
            }
            if (!Schema::hasColumn('quran_sessions', 'papers_covered_today')) {
                $table->decimal('papers_covered_today', 5, 2)->default(0)->after('papers_memorized_today')->comment('عدد الأوجه المراجعة اليوم');
            }
        });
        
        // Add indexes for quran_sessions (only if they don't exist)
        if (Schema::hasColumn('quran_sessions', 'current_page') && 
            Schema::hasColumn('quran_sessions', 'current_face')) {
            $this->addIndexIfNotExists('quran_sessions', 'quran_sessions_current_page_current_face_index', ['current_page', 'current_face']);
        }
        if (Schema::hasColumn('quran_sessions', 'papers_memorized_today')) {
            $this->addIndexIfNotExists('quran_sessions', 'quran_sessions_papers_memorized_today_index', ['papers_memorized_today']);
        }

        // Update quran_progress table (if it exists)
        if (Schema::hasTable('quran_progress')) {
            Schema::table('quran_progress', function (Blueprint $table) {
                // Add new paper-based fields only if they don't exist
                if (!Schema::hasColumn('quran_progress', 'current_page')) {
                    $table->integer('current_page')->nullable()->after('current_verse')->comment('الصفحة الحالية');
                }
                if (!Schema::hasColumn('quran_progress', 'current_face')) {
                    $table->integer('current_face')->nullable()->after('current_page')->comment('الوجه الحالي (1 أو 2)');
                }
                if (!Schema::hasColumn('quran_progress', 'target_page')) {
                    $table->integer('target_page')->nullable()->after('target_verse')->comment('الصفحة المستهدفة');
                }
                if (!Schema::hasColumn('quran_progress', 'target_face')) {
                    $table->integer('target_face')->nullable()->after('target_page')->comment('الوجه المستهدف (1 أو 2)');
                }
                if (!Schema::hasColumn('quran_progress', 'papers_memorized')) {
                    $table->decimal('papers_memorized', 8, 2)->default(0)->after('verses_memorized')->comment('عدد الأوجه المحفوظة');
                }
                if (!Schema::hasColumn('quran_progress', 'papers_reviewed')) {
                    $table->decimal('papers_reviewed', 8, 2)->default(0)->after('verses_reviewed')->comment('عدد الأوجه المراجعة');
                }
                if (!Schema::hasColumn('quran_progress', 'papers_perfect')) {
                    $table->decimal('papers_perfect', 8, 2)->default(0)->after('verses_perfect')->comment('عدد الأوجه المتقنة');
                }
                if (!Schema::hasColumn('quran_progress', 'papers_need_work')) {
                    $table->decimal('papers_need_work', 8, 2)->default(0)->after('verses_need_work')->comment('عدد الأوجه التي تحتاج عمل');
                }
                if (!Schema::hasColumn('quran_progress', 'total_papers_memorized')) {
                    $table->decimal('total_papers_memorized', 8, 2)->default(0)->after('total_verses_memorized')->comment('إجمالي الأوجه المحفوظة');
                }
                if (!Schema::hasColumn('quran_progress', 'weekly_goal_papers')) {
                    $table->decimal('weekly_goal_papers', 5, 2)->nullable()->after('weekly_goal')->comment('الهدف الأسبوعي بالأوجه');
                }
                if (!Schema::hasColumn('quran_progress', 'monthly_goal_papers')) {
                    $table->decimal('monthly_goal_papers', 5, 2)->nullable()->after('monthly_goal')->comment('الهدف الشهري بالأوجه');
                }
            });
            
            // Add indexes for quran_progress (only if they don't exist)
            if (Schema::hasColumn('quran_progress', 'current_page') && 
                Schema::hasColumn('quran_progress', 'current_face')) {
                $this->addIndexIfNotExists('quran_progress', 'quran_progress_current_page_current_face_index', ['current_page', 'current_face']);
            }
            if (Schema::hasColumn('quran_progress', 'papers_memorized')) {
                $this->addIndexIfNotExists('quran_progress', 'quran_progress_papers_memorized_index', ['papers_memorized']);
            }
            if (Schema::hasColumn('quran_progress', 'total_papers_memorized')) {
                $this->addIndexIfNotExists('quran_progress', 'quran_progress_total_papers_memorized_index', ['total_papers_memorized']);
            }
        }

        // Update quran_homework table (if it exists)
        if (Schema::hasTable('quran_homework')) {
            Schema::table('quran_homework', function (Blueprint $table) {
                // Add new paper-based fields for homework assignments only if they don't exist
                if (!Schema::hasColumn('quran_homework', 'assigned_page_start')) {
                    $table->integer('assigned_page_start')->nullable()->after('total_verses')->comment('بداية الصفحة المكلفة');
                }
                if (!Schema::hasColumn('quran_homework', 'assigned_face_start')) {
                    $table->integer('assigned_face_start')->nullable()->after('assigned_page_start')->comment('بداية الوجه المكلف (1 أو 2)');
                }
                if (!Schema::hasColumn('quran_homework', 'assigned_page_end')) {
                    $table->integer('assigned_page_end')->nullable()->after('assigned_face_start')->comment('نهاية الصفحة المكلفة');
                }
                if (!Schema::hasColumn('quran_homework', 'assigned_face_end')) {
                    $table->integer('assigned_face_end')->nullable()->after('assigned_page_end')->comment('نهاية الوجه المكلف (1 أو 2)');
                }
                if (!Schema::hasColumn('quran_homework', 'assigned_papers_count')) {
                    $table->decimal('assigned_papers_count', 5, 2)->default(0)->after('assigned_face_end')->comment('عدد الأوجه المكلفة');
                }
                if (!Schema::hasColumn('quran_homework', 'completed_papers_count')) {
                    $table->decimal('completed_papers_count', 5, 2)->default(0)->after('assigned_papers_count')->comment('عدد الأوجه المكتملة');
                }
            });
            
            // Add indexes for quran_homework (only if they don't exist)
            if (Schema::hasColumn('quran_homework', 'assigned_page_start') && 
                Schema::hasColumn('quran_homework', 'assigned_face_start')) {
                $this->addIndexIfNotExists('quran_homework', 'quran_homework_assigned_page_start_assigned_face_start_index', ['assigned_page_start', 'assigned_face_start']);
            }
            if (Schema::hasColumn('quran_homework', 'assigned_papers_count')) {
                $this->addIndexIfNotExists('quran_homework', 'quran_homework_assigned_papers_count_index', ['assigned_papers_count']);
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Remove paper-based fields from quran_individual_circles
        Schema::table('quran_individual_circles', function (Blueprint $table) {
            // Drop indexes first (with try-catch to handle non-existent indexes)
            try {
                $table->dropIndex(['current_page', 'current_face']);
            } catch (\Exception $e) {
                // Index might not exist, ignore
            }
            try {
                $table->dropIndex(['papers_memorized']);
            } catch (\Exception $e) {
                // Index might not exist, ignore
            }
        });
        
        Schema::table('quran_individual_circles', function (Blueprint $table) {
            // Drop columns if they exist
            $columnsToCheck = ['current_page', 'current_face', 'papers_memorized', 'papers_memorized_precise'];
            $existingColumns = [];
            foreach ($columnsToCheck as $column) {
                if (Schema::hasColumn('quran_individual_circles', $column)) {
                    $existingColumns[] = $column;
                }
            }
            if (!empty($existingColumns)) {
                $table->dropColumn($existingColumns);
            }
        });

        // Remove paper-based fields from quran_sessions
        Schema::table('quran_sessions', function (Blueprint $table) {
            try {
                $table->dropIndex(['current_page', 'current_face']);
            } catch (\Exception $e) {
                // Index might not exist, ignore
            }
            try {
                $table->dropIndex(['papers_memorized_today']);
            } catch (\Exception $e) {
                // Index might not exist, ignore
            }
        });
        
        Schema::table('quran_sessions', function (Blueprint $table) {
            $columnsToCheck = [
                'current_page', 'current_face', 'page_covered_start',
                'face_covered_start', 'page_covered_end', 'face_covered_end',
                'papers_memorized_today', 'papers_covered_today'
            ];
            $existingColumns = [];
            foreach ($columnsToCheck as $column) {
                if (Schema::hasColumn('quran_sessions', $column)) {
                    $existingColumns[] = $column;
                }
            }
            if (!empty($existingColumns)) {
                $table->dropColumn($existingColumns);
            }
        });

        // Remove paper-based fields from quran_progress (if it exists)
        if (Schema::hasTable('quran_progress')) {
            Schema::table('quran_progress', function (Blueprint $table) {
                try {
                    $table->dropIndex(['current_page', 'current_face']);
                } catch (\Exception $e) {
                    // Index might not exist, ignore
                }
                try {
                    $table->dropIndex(['papers_memorized']);
                } catch (\Exception $e) {
                    // Index might not exist, ignore
                }
                try {
                    $table->dropIndex(['total_papers_memorized']);
                } catch (\Exception $e) {
                    // Index might not exist, ignore
                }
            });
            
            Schema::table('quran_progress', function (Blueprint $table) {
                $columnsToCheck = [
                    'current_page', 'current_face', 'target_page', 'target_face',
                    'papers_memorized', 'papers_reviewed', 'papers_perfect',
                    'papers_need_work', 'total_papers_memorized', 'weekly_goal_papers',
                    'monthly_goal_papers'
                ];
                $existingColumns = [];
                foreach ($columnsToCheck as $column) {
                    if (Schema::hasColumn('quran_progress', $column)) {
                        $existingColumns[] = $column;
                    }
                }
                if (!empty($existingColumns)) {
                    $table->dropColumn($existingColumns);
                }
            });
        }

        // Remove paper-based fields from quran_homework (if it exists)
        if (Schema::hasTable('quran_homework')) {
            Schema::table('quran_homework', function (Blueprint $table) {
                try {
                    $table->dropIndex(['assigned_page_start', 'assigned_face_start']);
                } catch (\Exception $e) {
                    // Index might not exist, ignore
                }
                try {
                    $table->dropIndex(['assigned_papers_count']);
                } catch (\Exception $e) {
                    // Index might not exist, ignore
                }
            });
            
            Schema::table('quran_homework', function (Blueprint $table) {
                $columnsToCheck = [
                    'assigned_page_start', 'assigned_face_start', 'assigned_page_end',
                    'assigned_face_end', 'assigned_papers_count', 'completed_papers_count'
                ];
                $existingColumns = [];
                foreach ($columnsToCheck as $column) {
                    if (Schema::hasColumn('quran_homework', $column)) {
                        $existingColumns[] = $column;
                    }
                }
                if (!empty($existingColumns)) {
                    $table->dropColumn($existingColumns);
                }
            });
        }
    }
};