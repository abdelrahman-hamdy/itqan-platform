<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Phase A.2 — `session_consumption` table.
 *
 * Single source of truth for "this session consumed quota from this cycle of
 * this subscription". Replaces the dual-write counters + dual idempotency
 * (R2) — see docs/subscription-invariants.md §5.
 *
 * Schema MUST match §5 of the invariants doc exactly:
 *   - Unique key `(session_id, session_type, subscription_id, subscription_type)`
 *     enforcing INV-B1 at the DB level.
 *   - `idx_cycle_active (cycle_id, reversed_at)` powers INV-B3 reconciler reads.
 *   - `idx_student_consumed (student_user_id, consumed_at)` powers per-student
 *     timelines (Phase B fixtures + Phase C audit views).
 *   - ENUMs on `consumption_type` and `source` keep writers honest. The
 *     `source` ladder (admin_manual > teacher_report > auto_attendance) is the
 *     P5 precedence cascade implemented in SubscriptionConsumption::record().
 *
 * Polymorphic on both ends: `session_type` is the morph alias for the session
 * class (e.g. `quran_session`); `subscription_type` is the morph alias for
 * the subscription class (e.g. `quran_subscription`). Morph aliases live in
 * AppServiceProvider's Relation::morphMap.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('session_consumption', function (Blueprint $table) {
            $table->id();

            // Polymorphic session reference. `morphs()` would create an index
            // on (session_type, session_id); we want the index on (session_id,
            // session_type, subscription_id, subscription_type) to back the
            // unique key, so declare columns explicitly.
            $table->unsignedBigInteger('session_id');
            $table->string('session_type', 50);

            // Polymorphic subscription reference (mirrors the morph alias from
            // AppServiceProvider's morphMap: quran_subscription /
            // academic_subscription / course_subscription).
            $table->unsignedBigInteger('subscription_id');
            $table->string('subscription_type', 50);

            // Cycle this consumption charges. INV-B3 derives
            // cycle.sessions_used from non-reversed rows of this cycle.
            $table->unsignedBigInteger('cycle_id');

            // Student whose quota is being consumed (the User this consumption
            // is "for" — for group sessions this varies per row).
            $table->unsignedBigInteger('student_user_id');

            // Outcome stamped at write time. `absent_counted` exists for the
            // matrix decision where the student missed but the teacher
            // explicitly counted the session against quota.
            $table->enum('consumption_type', [
                'attended',
                'late',
                'left',
                'absent_counted',
            ]);

            // P5 precedence cascade — admin_manual beats teacher_report beats
            // auto_attendance. SubscriptionConsumption::record() enforces.
            $table->enum('source', [
                'admin_manual',
                'teacher_report',
                'auto_attendance',
            ]);

            // Who wrote the row (NULL for system auto-attendance with no
            // attributable user, e.g. LiveKit join-time auto-marking).
            $table->unsignedBigInteger('source_user_id')->nullable();

            // When the consumption was recorded (NOT when the session
            // started/ended — that lives on the session row).
            $table->timestamp('consumed_at');

            // Reversal trio (INV-B5: populated atomically or not at all).
            $table->timestamp('reversed_at')->nullable();
            $table->string('reversed_reason', 255)->nullable();
            $table->unsignedBigInteger('reversed_by_user_id')->nullable();

            $table->timestamps();

            // INV-B1: exactly one row per (session, subscription). Including
            // both morph types keeps the constraint correct even if two
            // different subscription classes ever share an id space.
            $table->unique(
                ['session_id', 'session_type', 'subscription_id', 'subscription_type'],
                'uniq_session_subscription',
            );

            // Reconciler hot-path: count active rows for a cycle.
            $table->index(['cycle_id', 'reversed_at'], 'idx_cycle_active');

            // Per-student consumption timeline (Phase B + Phase C readers).
            $table->index(['student_user_id', 'consumed_at'], 'idx_student_consumed');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('session_consumption');
    }
};
