<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('quran_packages', function (Blueprint $table) {
            $table->decimal('sale_monthly_price', 10, 2)->nullable()->after('monthly_price');
            $table->decimal('sale_quarterly_price', 10, 2)->nullable()->after('quarterly_price');
            $table->decimal('sale_yearly_price', 10, 2)->nullable()->after('yearly_price');
        });

        Schema::table('academic_packages', function (Blueprint $table) {
            $table->decimal('sale_monthly_price', 10, 2)->nullable()->after('monthly_price');
            $table->decimal('sale_quarterly_price', 10, 2)->nullable()->after('quarterly_price');
            $table->decimal('sale_yearly_price', 10, 2)->nullable()->after('yearly_price');
        });
    }

    public function down(): void
    {
        Schema::table('quran_packages', function (Blueprint $table) {
            $table->dropColumn(['sale_monthly_price', 'sale_quarterly_price', 'sale_yearly_price']);
        });

        Schema::table('academic_packages', function (Blueprint $table) {
            $table->dropColumn(['sale_monthly_price', 'sale_quarterly_price', 'sale_yearly_price']);
        });
    }
};
