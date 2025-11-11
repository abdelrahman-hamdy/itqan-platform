<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up()
    {
        // Clear all existing sessions as requested
        DB::table('quran_sessions')->delete();
        
        // Reset auto increment
        DB::statement('ALTER TABLE quran_sessions AUTO_INCREMENT = 1');
    }

    public function down()
    {
        // Cannot restore deleted sessions
        // This is intentional as per requirements
    }
};
