<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('academic_teacher_profiles', function (Blueprint $table) {
            $table->enum('gender', ['male', 'female'])->nullable()->after('email');
        });

        // Set default gender for existing teachers (can be updated manually later)
        DB::table('academic_teacher_profiles')
            ->whereNull('gender')
            ->update(['gender' => 'male']);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('academic_teacher_profiles', function (Blueprint $table) {
            $table->dropColumn('gender');
        });
    }
};
