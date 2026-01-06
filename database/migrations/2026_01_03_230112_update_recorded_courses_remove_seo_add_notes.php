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
        Schema::table('recorded_courses', function (Blueprint $table) {
            // Drop SEO, category, and discount_price fields
            if (Schema::hasColumn('recorded_courses', 'category')) {
                $table->dropColumn('category');
            }
            if (Schema::hasColumn('recorded_courses', 'meta_description')) {
                $table->dropColumn('meta_description');
            }
            if (Schema::hasColumn('recorded_courses', 'notes')) {
                $table->dropColumn('notes');
            }
            if (Schema::hasColumn('recorded_courses', 'discount_price')) {
                $table->dropColumn('discount_price');
            }
        });

        Schema::table('recorded_courses', function (Blueprint $table) {
            // Add admin and supervisor notes fields
            if (! Schema::hasColumn('recorded_courses', 'admin_notes')) {
                $table->text('admin_notes')->nullable()->after('tags');
            }
            if (! Schema::hasColumn('recorded_courses', 'supervisor_notes')) {
                $table->text('supervisor_notes')->nullable()->after('admin_notes');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('recorded_courses', function (Blueprint $table) {
            // Drop the new notes fields
            if (Schema::hasColumn('recorded_courses', 'admin_notes')) {
                $table->dropColumn('admin_notes');
            }
            if (Schema::hasColumn('recorded_courses', 'supervisor_notes')) {
                $table->dropColumn('supervisor_notes');
            }
        });

        Schema::table('recorded_courses', function (Blueprint $table) {
            // Restore the old fields
            if (! Schema::hasColumn('recorded_courses', 'category')) {
                $table->string('category', 100)->nullable()->after('difficulty_level');
            }
            if (! Schema::hasColumn('recorded_courses', 'meta_description')) {
                $table->text('meta_description')->nullable()->after('tags');
            }
            if (! Schema::hasColumn('recorded_courses', 'notes')) {
                $table->text('notes')->nullable()->after('meta_description');
            }
            if (! Schema::hasColumn('recorded_courses', 'discount_price')) {
                $table->decimal('discount_price', 10, 2)->nullable()->after('price');
            }
        });
    }
};
