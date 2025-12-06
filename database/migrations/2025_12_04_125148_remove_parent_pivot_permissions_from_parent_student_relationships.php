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
        Schema::table('parent_student_relationships', function (Blueprint $table) {
            // Remove granular permission columns - simplified to parent sees all child data
            $table->dropColumn(['can_view_grades', 'can_receive_notifications', 'is_primary_contact']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('parent_student_relationships', function (Blueprint $table) {
            // Restore columns if migration is rolled back
            $table->boolean('can_view_grades')->default(true)->after('relationship_type');
            $table->boolean('can_receive_notifications')->default(true)->after('can_view_grades');
            $table->boolean('is_primary_contact')->default(false)->after('can_receive_notifications');
        });
    }
};
