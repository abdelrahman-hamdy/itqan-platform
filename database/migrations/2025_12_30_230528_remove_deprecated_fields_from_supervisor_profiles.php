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
        Schema::table('supervisor_profiles', function (Blueprint $table) {
            // Remove deprecated fields
            $table->dropColumn(['salary', 'hired_date', 'assigned_teachers']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('supervisor_profiles', function (Blueprint $table) {
            // Restore deprecated fields for rollback
            $table->decimal('salary', 10, 2)->nullable()->after('supervisor_code');
            $table->date('hired_date')->nullable()->after('salary');
            $table->json('assigned_teachers')->nullable()->after('hired_date');
        });
    }
};
