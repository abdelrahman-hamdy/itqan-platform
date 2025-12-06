<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Add missing columns expected by BaseSubscription model to academic_subscriptions table.
     */
    public function up(): void
    {
        Schema::table('academic_subscriptions', function (Blueprint $table) {
            // Progress tracking (if not exists)
            if (! Schema::hasColumn('academic_subscriptions', 'progress_percentage')) {
                $table->decimal('progress_percentage', 5, 2)->default(0)->after('completion_rate');
            }

            // Last session timestamp (if not exists)
            if (! Schema::hasColumn('academic_subscriptions', 'last_session_at')) {
                $table->timestamp('last_session_at')->nullable()->after('progress_percentage');
            }

            // Rating & Review fields (if not exist)
            if (! Schema::hasColumn('academic_subscriptions', 'rating')) {
                $table->unsignedTinyInteger('rating')->nullable()->after('notes');
            }

            if (! Schema::hasColumn('academic_subscriptions', 'review_text')) {
                $table->text('review_text')->nullable()->after('rating');
            }

            if (! Schema::hasColumn('academic_subscriptions', 'reviewed_at')) {
                $table->timestamp('reviewed_at')->nullable()->after('review_text');
            }

            // Metadata (if not exists)
            if (! Schema::hasColumn('academic_subscriptions', 'metadata')) {
                $table->json('metadata')->nullable()->after('reviewed_at');
            }

            // Created/updated by tracking (if not exist)
            if (! Schema::hasColumn('academic_subscriptions', 'created_by')) {
                $table->foreignId('created_by')->nullable()->after('metadata')
                    ->constrained('users')->nullOnDelete();
            }

            if (! Schema::hasColumn('academic_subscriptions', 'updated_by')) {
                $table->foreignId('updated_by')->nullable()->after('created_by')
                    ->constrained('users')->nullOnDelete();
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('academic_subscriptions', function (Blueprint $table) {
            $columns = [
                'progress_percentage',
                'last_session_at',
                'rating',
                'review_text',
                'reviewed_at',
                'metadata',
            ];

            foreach ($columns as $column) {
                if (Schema::hasColumn('academic_subscriptions', $column)) {
                    $table->dropColumn($column);
                }
            }

            // Drop foreign keys first
            if (Schema::hasColumn('academic_subscriptions', 'created_by')) {
                $table->dropForeign(['created_by']);
                $table->dropColumn('created_by');
            }

            if (Schema::hasColumn('academic_subscriptions', 'updated_by')) {
                $table->dropForeign(['updated_by']);
                $table->dropColumn('updated_by');
            }
        });
    }
};
