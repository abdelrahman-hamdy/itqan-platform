<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        foreach (['quran_subscriptions', 'academic_subscriptions', 'course_subscriptions'] as $table) {
            Schema::table($table, function (Blueprint $table) {
                $table->boolean('is_recurring_discount')->default(false)->after('discount_amount');
            });
        }
    }

    public function down(): void
    {
        foreach (['quran_subscriptions', 'academic_subscriptions', 'course_subscriptions'] as $table) {
            Schema::table($table, function (Blueprint $table) {
                $table->dropColumn('is_recurring_discount');
            });
        }
    }
};
