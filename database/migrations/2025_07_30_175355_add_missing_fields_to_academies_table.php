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
        Schema::table('academies', function (Blueprint $table) {
            // Basic Information
            $table->string('name_en')->nullable()->after('name');
            $table->string('email')->nullable()->after('description');
            $table->string('phone')->nullable()->after('email');
            $table->string('website')->nullable()->after('phone');
            
            // Visual Identity
            $table->string('secondary_color')->default('#10B981')->after('brand_color');
            $table->enum('theme', ['light', 'dark', 'auto'])->default('light')->after('secondary_color');
            
            // Settings
            $table->string('timezone')->default('Asia/Riyadh')->after('theme');
            $table->string('currency', 3)->default('SAR')->after('timezone');
            $table->boolean('allow_registration')->default(true)->after('currency');
            $table->boolean('maintenance_mode')->default(false)->after('allow_registration');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('academies', function (Blueprint $table) {
            $table->dropColumn([
                'name_en',
                'email', 
                'phone',
                'website',
                'secondary_color',
                'theme',
                'timezone',
                'currency',
                'allow_registration',
                'maintenance_mode'
            ]);
        });
    }
};
