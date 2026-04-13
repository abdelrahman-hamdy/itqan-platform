<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Add new can_view_subscriptions column
        Schema::table('supervisor_profiles', function (Blueprint $table) {
            $table->boolean('can_view_subscriptions')->default(false)->after('can_manage_subscriptions');
        });

        // 2. Data migration:
        // - manage=true AND create=true → keep manage=true (full control)
        // - manage=true AND create=false → view=true, manage=false (view-only)
        DB::table('supervisor_profiles')
            ->where('can_manage_subscriptions', true)
            ->where('can_create_subscriptions', false)
            ->update([
                'can_view_subscriptions' => true,
                'can_manage_subscriptions' => false,
            ]);

        // 3. Drop old can_create_subscriptions column
        Schema::table('supervisor_profiles', function (Blueprint $table) {
            $table->dropColumn('can_create_subscriptions');
        });
    }

    public function down(): void
    {
        // 1. Add back can_create_subscriptions
        Schema::table('supervisor_profiles', function (Blueprint $table) {
            $table->boolean('can_create_subscriptions')->default(false)->after('can_manage_subscriptions');
        });

        // 2. Reverse data:
        // - manage=true → also set create=true
        DB::table('supervisor_profiles')
            ->where('can_manage_subscriptions', true)
            ->update(['can_create_subscriptions' => true]);

        // - view=true → set manage=true (restore old view+actions permission)
        DB::table('supervisor_profiles')
            ->where('can_view_subscriptions', true)
            ->update(['can_manage_subscriptions' => true]);

        // 3. Drop can_view_subscriptions
        Schema::table('supervisor_profiles', function (Blueprint $table) {
            $table->dropColumn('can_view_subscriptions');
        });
    }
};
