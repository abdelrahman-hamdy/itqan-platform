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
        // Safety: set any remaining NULLs to 'male' before adding NOT NULL constraint
        DB::table('quran_teacher_profiles')->whereNull('gender')->update(['gender' => 'male']);

        Schema::table('quran_teacher_profiles', function (Blueprint $table) {
            $table->string('gender')->nullable(false)->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('quran_teacher_profiles', function (Blueprint $table) {
            $table->string('gender')->nullable()->change();
        });
    }
};
