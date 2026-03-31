<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('academies', function (Blueprint $table) {
            $table->string('teacher_earnings_currency', 10)->nullable()->after('currency')
                ->comment('Currency for teacher earnings. Null = use academy currency.');
        });
    }

    public function down(): void
    {
        Schema::table('academies', function (Blueprint $table) {
            $table->dropColumn('teacher_earnings_currency');
        });
    }
};
