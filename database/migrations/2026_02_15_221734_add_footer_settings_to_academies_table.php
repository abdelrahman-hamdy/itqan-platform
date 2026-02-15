<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('academies', function (Blueprint $table) {
            $table->string('footer_photo')->nullable()->after('features_show_in_nav');
            $table->boolean('footer_show_academy_info')->default(true)->after('footer_photo');
            $table->boolean('footer_show_main_sections')->default(true)->after('footer_show_academy_info');
            $table->boolean('footer_show_important_links')->default(true)->after('footer_show_main_sections');
            $table->boolean('footer_show_contact_info')->default(true)->after('footer_show_important_links');
        });
    }

    public function down(): void
    {
        Schema::table('academies', function (Blueprint $table) {
            $table->dropColumn([
                'footer_photo',
                'footer_show_academy_info',
                'footer_show_main_sections',
                'footer_show_important_links',
                'footer_show_contact_info',
            ]);
        });
    }
};
