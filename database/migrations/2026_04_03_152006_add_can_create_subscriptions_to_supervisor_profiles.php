<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('supervisor_profiles', function (Blueprint $table) {
            $table->boolean('can_create_subscriptions')->default(false)->after('can_manage_subscriptions');
        });

        // Auto-grant to supervisors who already have manage subscriptions permission
        DB::table('supervisor_profiles')
            ->where('can_manage_subscriptions', true)
            ->update(['can_create_subscriptions' => true]);
    }

    public function down(): void
    {
        Schema::table('supervisor_profiles', function (Blueprint $table) {
            $table->dropColumn('can_create_subscriptions');
        });
    }
};
