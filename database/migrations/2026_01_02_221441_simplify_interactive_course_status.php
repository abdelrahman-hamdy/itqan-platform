<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Simplify InteractiveCourseStatus enum from 5 to 3 values:
 * - DRAFT → PUBLISHED (with is_published=false to preserve visibility)
 * - CANCELLED → COMPLETED
 *
 * Final values: PUBLISHED, ACTIVE, COMPLETED
 */
return new class extends Migration
{
    public function up(): void
    {
        // Convert DRAFT courses to PUBLISHED with is_published=false
        // This preserves the visibility behavior (hidden from students)
        DB::table('interactive_courses')
            ->where('status', 'draft')
            ->update([
                'status' => 'published',
                'is_published' => false,
            ]);

        // Convert CANCELLED courses to COMPLETED
        DB::table('interactive_courses')
            ->where('status', 'cancelled')
            ->update([
                'status' => 'completed',
            ]);
    }

    public function down(): void
    {
        // Note: This is a lossy migration - we cannot perfectly restore DRAFT/CANCELLED
        // Best effort: Convert PUBLISHED with is_published=false back to DRAFT
        DB::table('interactive_courses')
            ->where('status', 'published')
            ->where('is_published', false)
            ->update([
                'status' => 'draft',
            ]);
    }
};
