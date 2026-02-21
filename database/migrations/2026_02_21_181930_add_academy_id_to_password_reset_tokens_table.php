<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * MT-004: Add academy_id to password_reset_tokens for multi-tenant scoping.
 *
 * Without this, a password reset token from one academy could be used to
 * reset the password for the same email in a different academy.
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('password_reset_tokens', function (Blueprint $table) {
            $table->unsignedBigInteger('academy_id')->nullable()->after('email');
            $table->index('academy_id');

            // Drop old unique on email, add composite unique on email + academy_id
            $table->dropPrimary();
            $table->primary(['email', 'academy_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('password_reset_tokens', function (Blueprint $table) {
            $table->dropPrimary();
            $table->primary('email');
            $table->dropIndex(['academy_id']);
            $table->dropColumn('academy_id');
        });
    }
};
