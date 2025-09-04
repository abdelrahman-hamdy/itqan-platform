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
        Schema::table('academic_subscriptions', function (Blueprint $table) {
            $table->foreignId('academic_package_id')
                  ->nullable()
                  ->after('session_request_id')
                  ->constrained('academic_packages')
                  ->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('academic_subscriptions', function (Blueprint $table) {
            $table->dropForeign(['academic_package_id']);
            $table->dropColumn('academic_package_id');
        });
    }
};
