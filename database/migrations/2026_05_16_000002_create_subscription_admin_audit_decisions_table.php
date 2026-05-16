<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * `subscription_admin_audit_decisions` — temporary scratchpad for the admin
 * audit page introduced 2026-05-16. The page surfaces every subscription /
 * cycle / payment that requires human judgement (INV-D2 violations that can't
 * be auto-fixed, paused subs with corrupt state, etc.) and lets the admin
 * record their decision per case.
 *
 * The CASE list is computed live on each page load (so it stays in sync with
 * data changes). DECISIONS are persisted here, keyed on `case_key`
 * (e.g. "inv_d2_drift_payment_mismatch:cycle:143") so re-renders don't lose
 * the admin's input.
 *
 * Once the subscription-system cleanup is complete this table can be
 * dropped — it's purely an interim collection mechanism for the next
 * iteration of cleanup work.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('subscription_admin_audit_decisions', function (Blueprint $table) {
            $table->id();
            $table->string('case_type', 64)->index();
            $table->string('subject_type', 64)->nullable();
            $table->unsignedBigInteger('subject_id')->nullable();
            $table->string('case_key', 191)->unique();
            $table->string('selected_option', 128)->nullable();
            $table->text('free_text')->nullable();
            $table->foreignId('decided_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('decided_at')->nullable();
            $table->timestamps();

            $table->index(['subject_type', 'subject_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('subscription_admin_audit_decisions');
    }
};
