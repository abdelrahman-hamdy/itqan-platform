<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('admin_code')->nullable()->unique()->after('user_type');
        });

        // Generate admin_code for existing admin users
        $admins = DB::table('users')
            ->where('user_type', 'admin')
            ->orderBy('id')
            ->get();

        foreach ($admins as $admin) {
            $academyId = $admin->academy_id ?: 1;
            $prefix = 'ADM-'.str_pad($academyId, 2, '0', STR_PAD_LEFT).'-';

            // Find the highest existing sequence number for this academy
            $maxCode = DB::table('users')
                ->where('admin_code', 'like', $prefix.'%')
                ->orderByRaw('CAST(SUBSTRING(admin_code, -4) AS UNSIGNED) DESC')
                ->value('admin_code');

            if ($maxCode) {
                $sequence = (int) substr($maxCode, -4) + 1;
            } else {
                $sequence = 1;
            }

            $adminCode = $prefix.str_pad($sequence, 4, '0', STR_PAD_LEFT);

            DB::table('users')
                ->where('id', $admin->id)
                ->update(['admin_code' => $adminCode]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('admin_code');
        });
    }
};
