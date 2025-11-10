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
        Schema::table('interactive_course_sessions', function (Blueprint $table) {
            // Add homework-related fields
            $table->text('homework_description')->nullable()->after('homework_assigned')
                ->comment('Description/instructions for the homework');
            $table->timestamp('homework_due_date')->nullable()->after('homework_description')
                ->comment('When the homework is due');
            $table->integer('homework_max_score')->nullable()->after('homework_due_date')
                ->comment('Maximum score for this homework');
            $table->boolean('allow_late_submissions')->default(false)->after('homework_max_score')
                ->comment('Whether students can submit after due date');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('interactive_course_sessions', function (Blueprint $table) {
            $table->dropColumn([
                'homework_description',
                'homework_due_date',
                'homework_max_score',
                'allow_late_submissions',
            ]);
        });
    }
};
