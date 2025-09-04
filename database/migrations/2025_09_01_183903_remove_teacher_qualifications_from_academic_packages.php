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
        Schema::table('academic_packages', function (Blueprint $table) {
            $table->dropColumn('teacher_qualifications');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('academic_packages', function (Blueprint $table) {
            $table->json('teacher_qualifications')->nullable();
        });
    }
};
