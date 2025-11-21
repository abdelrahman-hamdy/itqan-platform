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
        Schema::table('academic_session_reports', function (Blueprint $table) {
            $table->dropColumn('participation_degree');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('academic_session_reports', function (Blueprint $table) {
            $table->decimal('participation_degree', 3, 1)->nullable()->after('homework_completion_degree');
        });
    }
};
