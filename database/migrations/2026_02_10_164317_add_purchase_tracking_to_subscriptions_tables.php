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
        // Add purchase tracking fields to quran_subscriptions
        Schema::table('quran_subscriptions', function (Blueprint $table) {
            if (!Schema::hasColumn('quran_subscriptions', 'purchase_source')) {
                $table->string('purchase_source', 20)->default('web')
                    ->after('payment_status')
                    ->comment('web|admin|legacy');
            }

            if (!Schema::hasColumn('quran_subscriptions', 'last_accessed_at')) {
                $table->timestamp('last_accessed_at')->nullable()
                    ->after('purchase_source')
                    ->comment('Last content access timestamp');
            }

            if (!Schema::hasColumn('quran_subscriptions', 'last_accessed_platform')) {
                $table->string('last_accessed_platform', 20)->nullable()
                    ->after('last_accessed_at')
                    ->comment('web|mobile');
            }
        });

        // Add index (ignoring if already exists)
        try {
            Schema::table('quran_subscriptions', function (Blueprint $table) {
                $table->index(['purchase_source', 'status']);
            });
        } catch (\Exception $e) {
            // Index might already exist, ignore
        }

        // Add purchase tracking fields to academic_subscriptions
        Schema::table('academic_subscriptions', function (Blueprint $table) {
            if (!Schema::hasColumn('academic_subscriptions', 'purchase_source')) {
                $table->string('purchase_source', 20)->default('web')
                    ->after('payment_status')
                    ->comment('web|admin|legacy');
            }

            if (!Schema::hasColumn('academic_subscriptions', 'last_accessed_at')) {
                $table->timestamp('last_accessed_at')->nullable()
                    ->after('purchase_source')
                    ->comment('Last content access timestamp');
            }

            if (!Schema::hasColumn('academic_subscriptions', 'last_accessed_platform')) {
                $table->string('last_accessed_platform', 20)->nullable()
                    ->after('last_accessed_at')
                    ->comment('web|mobile');
            }
        });

        // Add index (ignoring if already exists)
        try {
            Schema::table('academic_subscriptions', function (Blueprint $table) {
                $table->index(['purchase_source', 'status']);
            });
        } catch (\Exception $e) {
            // Index might already exist, ignore
        }

        // Add purchase tracking fields to course_subscriptions
        Schema::table('course_subscriptions', function (Blueprint $table) {
            if (!Schema::hasColumn('course_subscriptions', 'purchase_source')) {
                $table->string('purchase_source', 20)->default('web')
                    ->after('payment_status')
                    ->comment('web|admin|legacy');
            }

            if (!Schema::hasColumn('course_subscriptions', 'last_accessed_at')) {
                $table->timestamp('last_accessed_at')->nullable()
                    ->after('purchase_source')
                    ->comment('Last content access timestamp');
            }

            if (!Schema::hasColumn('course_subscriptions', 'last_accessed_platform')) {
                $table->string('last_accessed_platform', 20)->nullable()
                    ->after('last_accessed_at')
                    ->comment('web|mobile');
            }
        });

        // Add index (ignoring if already exists)
        try {
            Schema::table('course_subscriptions', function (Blueprint $table) {
                $table->index(['purchase_source', 'status']);
            });
        } catch (\Exception $e) {
            // Index might already exist, ignore
        }

        // Backfill existing subscriptions with 'legacy' purchase_source
        DB::table('quran_subscriptions')
            ->whereNull('purchase_source')
            ->orWhere('purchase_source', '')
            ->update(['purchase_source' => 'legacy']);

        DB::table('academic_subscriptions')
            ->whereNull('purchase_source')
            ->orWhere('purchase_source', '')
            ->update(['purchase_source' => 'legacy']);

        DB::table('course_subscriptions')
            ->whereNull('purchase_source')
            ->orWhere('purchase_source', '')
            ->update(['purchase_source' => 'legacy']);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('quran_subscriptions', function (Blueprint $table) {
            $table->dropIndex(['purchase_source', 'status']);
            $table->dropColumn(['purchase_source', 'last_accessed_at', 'last_accessed_platform']);
        });

        Schema::table('academic_subscriptions', function (Blueprint $table) {
            $table->dropIndex(['purchase_source', 'status']);
            $table->dropColumn(['purchase_source', 'last_accessed_at', 'last_accessed_platform']);
        });

        Schema::table('course_subscriptions', function (Blueprint $table) {
            $table->dropIndex(['purchase_source', 'status']);
            $table->dropColumn(['purchase_source', 'last_accessed_at', 'last_accessed_platform']);
        });
    }
};
