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
            $table->boolean('can_manage_parents')->default(false)->after('can_manage_students');
            $table->boolean('can_reset_passwords')->default(false)->after('can_manage_parents');
            $table->boolean('can_manage_subscriptions')->default(false)->after('can_reset_passwords');
            $table->boolean('can_manage_payments')->default(false)->after('can_manage_subscriptions');
            $table->boolean('can_manage_teacher_earnings')->default(false)->after('can_manage_payments');
            $table->boolean('can_monitor_sessions')->default(false)->after('can_manage_teacher_earnings');
        });

        // Migrate existing permissions to new granular columns
        // Supervisors with can_manage_students get parents, subscriptions, payments
        DB::table('supervisor_profiles')
            ->where('can_manage_students', true)
            ->update([
                'can_manage_parents' => true,
                'can_manage_subscriptions' => true,
                'can_manage_payments' => true,
            ]);

        // Supervisors with can_manage_teachers get teacher earnings
        DB::table('supervisor_profiles')
            ->where('can_manage_teachers', true)
            ->update([
                'can_manage_teacher_earnings' => true,
            ]);

        // Supervisors with either permission get password reset
        DB::table('supervisor_profiles')
            ->where(function ($q) {
                $q->where('can_manage_teachers', true)
                    ->orWhere('can_manage_students', true);
            })
            ->update([
                'can_reset_passwords' => true,
            ]);
    }

    public function down(): void
    {
        Schema::table('supervisor_profiles', function (Blueprint $table) {
            $table->dropColumn([
                'can_manage_parents',
                'can_reset_passwords',
                'can_manage_subscriptions',
                'can_manage_payments',
                'can_manage_teacher_earnings',
                'can_monitor_sessions',
            ]);
        });
    }
};
