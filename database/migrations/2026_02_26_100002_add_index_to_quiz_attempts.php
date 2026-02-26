<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Check whether a named index already exists.
     */
    private function indexExists(string $table, string $indexName): bool
    {
        $database = config('database.connections.mysql.database');
        $result = DB::select(
            'SELECT COUNT(*) as count FROM information_schema.statistics
             WHERE table_schema = ? AND table_name = ? AND index_name = ?',
            [$database, $table, $indexName]
        );

        return $result[0]->count > 0;
    }

    public function up(): void
    {
        if (Schema::hasTable('quiz_attempts')) {
            if (! $this->indexExists('quiz_attempts', 'idx_quiz_attempts_student_assignment')) {
                Schema::table('quiz_attempts', function (Blueprint $table) {
                    $table->index(
                        ['student_id', 'quiz_assignment_id'],
                        'idx_quiz_attempts_student_assignment'
                    );
                });
            }
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('quiz_attempts')) {
            if ($this->indexExists('quiz_attempts', 'idx_quiz_attempts_student_assignment')) {
                Schema::table('quiz_attempts', function (Blueprint $table) {
                    $table->dropIndex('idx_quiz_attempts_student_assignment');
                });
            }
        }
    }
};
