<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('subscription_admin_audit_decisions', function (Blueprint $table) {
            $table->timestamp('applied_at')->nullable()->after('decided_at');
            $table->foreignId('applied_by_user_id')->nullable()->after('applied_at')->constrained('users')->nullOnDelete();
            $table->string('applied_outcome', 64)->nullable()->after('applied_by_user_id');
            $table->index('applied_at', 'sad_applied_at_idx');
        });
    }

    public function down(): void
    {
        Schema::table('subscription_admin_audit_decisions', function (Blueprint $table) {
            $table->dropIndex('sad_applied_at_idx');
            $table->dropConstrainedForeignId('applied_by_user_id');
            $table->dropColumn(['applied_at', 'applied_outcome']);
        });
    }
};
