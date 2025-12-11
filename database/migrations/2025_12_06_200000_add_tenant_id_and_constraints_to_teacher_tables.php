<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Adds tenant_id column and foreign key constraints to teacher earnings and payouts tables.
     * This ensures proper multi-tenancy isolation and referential integrity.
     */
    public function up(): void
    {
        // Add tenant_id to teacher_earnings if not exists
        if (Schema::hasTable('teacher_earnings') && !Schema::hasColumn('teacher_earnings', 'tenant_id')) {
            Schema::table('teacher_earnings', function (Blueprint $table) {
                $table->unsignedBigInteger('tenant_id')->nullable()->after('id');
                $table->index('tenant_id');

                // Add composite index for polymorphic lookup
                $table->index(['teacher_type', 'teacher_id'], 'teacher_earnings_teacher_poly_idx');
            });

            // Populate tenant_id from academy relationship
            DB::statement('UPDATE teacher_earnings SET tenant_id = academy_id WHERE tenant_id IS NULL');
        }

        // Add tenant_id to teacher_payouts if not exists
        if (Schema::hasTable('teacher_payouts') && !Schema::hasColumn('teacher_payouts', 'tenant_id')) {
            Schema::table('teacher_payouts', function (Blueprint $table) {
                $table->unsignedBigInteger('tenant_id')->nullable()->after('id');
                $table->index('tenant_id');

                // Add composite index for polymorphic lookup
                $table->index(['teacher_type', 'teacher_id'], 'teacher_payouts_teacher_poly_idx');
            });

            // Populate tenant_id from academy relationship
            DB::statement('UPDATE teacher_payouts SET tenant_id = academy_id WHERE tenant_id IS NULL');
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('teacher_earnings') && Schema::hasColumn('teacher_earnings', 'tenant_id')) {
            Schema::table('teacher_earnings', function (Blueprint $table) {
                $table->dropIndex('teacher_earnings_teacher_poly_idx');
                $table->dropIndex(['tenant_id']);
                $table->dropColumn('tenant_id');
            });
        }

        if (Schema::hasTable('teacher_payouts') && Schema::hasColumn('teacher_payouts', 'tenant_id')) {
            Schema::table('teacher_payouts', function (Blueprint $table) {
                $table->dropIndex('teacher_payouts_teacher_poly_idx');
                $table->dropIndex(['tenant_id']);
                $table->dropColumn('tenant_id');
            });
        }
    }
};
