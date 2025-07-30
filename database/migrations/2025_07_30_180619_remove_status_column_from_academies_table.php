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
            // Drop the composite index first
            $table->dropIndex(['status', 'is_active']);
            
            // Drop the status column
            $table->dropColumn('status');
            
            // Recreate the index for is_active only
            $table->index('is_active');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('academies', function (Blueprint $table) {
            // Drop the single column index
            $table->dropIndex(['is_active']);
            
            // Re-add the status column
            $table->enum('status', ['active', 'suspended', 'maintenance'])->default('active')->after('brand_color');
            
            // Recreate the composite index
            $table->index(['status', 'is_active']);
        });
    }
};
