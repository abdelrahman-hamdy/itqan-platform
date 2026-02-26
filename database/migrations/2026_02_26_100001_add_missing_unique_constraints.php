<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Check whether a named unique/index constraint already exists.
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
        // Certificate numbers must be globally unique across all tenants.
        if (Schema::hasTable('certificates') && Schema::hasColumn('certificates', 'certificate_number')) {
            if (! $this->indexExists('certificates', 'uk_certificates_number')) {
                Schema::table('certificates', function (Blueprint $table) {
                    $table->unique('certificate_number', 'uk_certificates_number');
                });
            }
        }

        // Subscription codes must be unique per academy (tenant-scoped uniqueness).
        if (Schema::hasTable('quran_subscriptions') && Schema::hasColumn('quran_subscriptions', 'subscription_code')) {
            if (! $this->indexExists('quran_subscriptions', 'uk_quran_sub_academy_code')) {
                Schema::table('quran_subscriptions', function (Blueprint $table) {
                    $table->unique(['academy_id', 'subscription_code'], 'uk_quran_sub_academy_code');
                });
            }
        }

        if (Schema::hasTable('academic_subscriptions') && Schema::hasColumn('academic_subscriptions', 'subscription_code')) {
            if (! $this->indexExists('academic_subscriptions', 'uk_academic_sub_academy_code')) {
                Schema::table('academic_subscriptions', function (Blueprint $table) {
                    $table->unique(['academy_id', 'subscription_code'], 'uk_academic_sub_academy_code');
                });
            }
        }

        // Circle codes must be unique per academy.
        if (Schema::hasTable('quran_circles') && Schema::hasColumn('quran_circles', 'circle_code')) {
            if (! $this->indexExists('quran_circles', 'uk_quran_circles_academy_code')) {
                Schema::table('quran_circles', function (Blueprint $table) {
                    $table->unique(['academy_id', 'circle_code'], 'uk_quran_circles_academy_code');
                });
            }
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('certificates')) {
            if ($this->indexExists('certificates', 'uk_certificates_number')) {
                Schema::table('certificates', function (Blueprint $table) {
                    $table->dropUnique('uk_certificates_number');
                });
            }
        }

        if (Schema::hasTable('quran_subscriptions')) {
            if ($this->indexExists('quran_subscriptions', 'uk_quran_sub_academy_code')) {
                Schema::table('quran_subscriptions', function (Blueprint $table) {
                    $table->dropUnique('uk_quran_sub_academy_code');
                });
            }
        }

        if (Schema::hasTable('academic_subscriptions')) {
            if ($this->indexExists('academic_subscriptions', 'uk_academic_sub_academy_code')) {
                Schema::table('academic_subscriptions', function (Blueprint $table) {
                    $table->dropUnique('uk_academic_sub_academy_code');
                });
            }
        }

        if (Schema::hasTable('quran_circles')) {
            if ($this->indexExists('quran_circles', 'uk_quran_circles_academy_code')) {
                Schema::table('quran_circles', function (Blueprint $table) {
                    $table->dropUnique('uk_quran_circles_academy_code');
                });
            }
        }
    }
};
