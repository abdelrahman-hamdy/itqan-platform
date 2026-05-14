#!/usr/bin/env bash
# Phase E cleanup runner — itqan subscription v2 refactor
#
# DO NOT RUN unless Phase D sign-off has been recorded
# (5 consecutive days of zero invariant violations on prod + operator approval).
#
# Usage:
#   tools/cleanup/run-phase-e.sh --dry-run    # list every action, change nothing
#   tools/cleanup/run-phase-e.sh --apply      # execute, one step at a time, with prompts
#
# This script is the executable counterpart of:
#   docs/subscription-cleanup-inventory.md       (the what)
#   docs/subscription-cleanup-migration-plan.md  (the order)
#
# Per-step PR boundaries follow the migration plan. Each step prompts for
# confirmation in --apply mode and writes its action log to
# storage/logs/phase-e/<step>-<timestamp>.log so the operator can audit /
# revert each step independently.

set -euo pipefail

# --------------------------------------------------------------------------
# Argument parsing
# --------------------------------------------------------------------------
MODE="${1:-}"
if [[ "$MODE" != "--dry-run" && "$MODE" != "--apply" ]]; then
    echo "Usage: $0 --dry-run | --apply"
    echo
    echo "  --dry-run  Print every action without changing anything."
    echo "  --apply    Execute each step with a per-step confirmation prompt."
    exit 1
fi

DRY_RUN=false
if [[ "$MODE" == "--dry-run" ]]; then
    DRY_RUN=true
fi

# --------------------------------------------------------------------------
# Paths + logging
# --------------------------------------------------------------------------
REPO_ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")/../.." && pwd)"
LOG_DIR="$REPO_ROOT/storage/logs/phase-e"
TIMESTAMP="$(date -u +%Y%m%dT%H%M%SZ)"
mkdir -p "$LOG_DIR"

log()       { echo "[phase-e] $*"; }
log_step()  { echo; echo "════════════════════════════════════════"; echo "STEP: $*"; echo "════════════════════════════════════════"; }
log_action(){ echo "  → $*"; }

confirm() {
    if $DRY_RUN; then
        log_action "(dry-run) would prompt: $1 — auto-skip"
        return 1
    fi
    read -r -p "  $1 [y/N] " ans
    [[ "$ans" =~ ^[Yy]$ ]]
}

run_or_print() {
    # Print the command in dry-run, run it in apply mode.
    if $DRY_RUN; then
        log_action "(dry-run) $*"
    else
        log_action "running: $*"
        eval "$@"
    fi
}

# --------------------------------------------------------------------------
# Pre-flight checks
# --------------------------------------------------------------------------
cd "$REPO_ROOT"

if [[ ! -d ".git" ]]; then
    log "ERROR: must run from repo root with a clean git checkout."
    exit 2
fi

CURRENT_BRANCH="$(git symbolic-ref --short HEAD 2>/dev/null || echo 'detached')"
log "Working dir: $REPO_ROOT"
log "Current branch: $CURRENT_BRANCH"
log "Mode: $MODE"
log "Log dir: $LOG_DIR"
echo

if [[ "$CURRENT_BRANCH" == "main" || "$CURRENT_BRANCH" == "master" ]]; then
    log "ERROR: refusing to run on $CURRENT_BRANCH. Check out a Phase E PR branch."
    exit 3
fi

# Check that the working tree is clean (no staged or unstaged changes) when applying.
if ! $DRY_RUN; then
    if [[ -n "$(git status --porcelain)" ]]; then
        log "ERROR: working tree has uncommitted changes. Commit or stash first."
        exit 4
    fi
fi

# --------------------------------------------------------------------------
# Step registry — keyed to PRs in the migration plan
# --------------------------------------------------------------------------
# Each step is a function. The dispatcher at the bottom runs them in order.
# In --apply mode each step prompts independently so the operator can stop
# mid-run after any step completes.

# --------------------------------------------------------------------------
# Step 1 — Archive backfill commands (PR 1)
# --------------------------------------------------------------------------
step_archive_backfills() {
    log_step "PR 1 — Archive backfill commands"
    if ! confirm "Run PR 1 (archive backfill commands)?"; then
        log_action "skipped"
        return
    fi

    # Files to git-mv into Archived/.
    local archive_targets=(
        "app/Console/Commands/Backfill/FixDoubleRenewalUnpaidCommand.php"
        "app/Console/Commands/Backfill/FixLieStateSubsCommand.php"
        "app/Console/Commands/Backfill/FixCorruptCurrencyValuesCommand.php"
        "app/Console/Commands/Backfill/EarningsFixBug5KnownTuplesCommand.php"
        "app/Console/Commands/Backfill/SupervisorPauseResumeBackfillCommand.php"
        "app/Console/Commands/BackfillSessionCycles.php"
        "app/Console/Commands/BackfillSessionNumbers.php"
        "app/Console/Commands/BackfillEarningCycleMonths.php"
        "app/Console/Commands/BackfillTeacherAttendance.php"
        "app/Console/Commands/BackfillAttendanceMatrixData.php"
        "app/Console/Commands/BootstrapSubscriptionCycles.php"
        "app/Console/Commands/DeploySubscriptionCycles.php"
        "app/Console/Commands/FixAbsentSessionsWithAttendance.php"
        "app/Console/Commands/FixPostRecoveryData.php"
        "app/Console/Commands/FixPostRecoveryPaymentStatus.php"
        "app/Console/Commands/AuditFqcnAliasEarningPairsCommand.php"
    )
    # Files to delete outright (audit-only, replaced by invariant-check).
    local delete_targets=(
        "app/Console/Commands/Backfill/AuditGhostSubscriptionPairsCommand.php"
        "app/Console/Commands/Backfill/AuditResurrectedCancelledCommand.php"
        "app/Console/Commands/Backfill/AuditSkewedResubscribeCyclesCommand.php"
        "app/Console/Commands/AuditCycleCounts.php"
        "app/Console/Commands/ReconcileSessionCounting.php"
        "app/Console/Commands/ResyncSubscriptionScheduledCounts.php"
    )

    mkdir -p "app/Console/Commands/Archived"

    # Special-case: FixSessionDurations.php exists in BOTH root and Archived/.
    # Rename the root variant to FixSessionDurations_v2.php on move.
    if [[ -f "app/Console/Commands/FixSessionDurations.php" ]]; then
        if [[ -f "app/Console/Commands/Archived/FixSessionDurations.php" ]]; then
            run_or_print "git mv app/Console/Commands/FixSessionDurations.php app/Console/Commands/Archived/FixSessionDurations_v2.php"
        else
            run_or_print "git mv app/Console/Commands/FixSessionDurations.php app/Console/Commands/Archived/FixSessionDurations.php"
        fi
    fi

    for f in "${archive_targets[@]}"; do
        if [[ -f "$f" ]]; then
            local base
            base="$(basename "$f")"
            run_or_print "git mv $f app/Console/Commands/Archived/$base"
        else
            log_action "skipped (not found): $f"
        fi
    done

    for f in "${delete_targets[@]}"; do
        if [[ -f "$f" ]]; then
            run_or_print "git rm $f"
        else
            log_action "skipped (not found): $f"
        fi
    done

    log_action "PR 1 staging complete. Review with: git status --short | tee $LOG_DIR/pr1-archive-$TIMESTAMP.log"
}

# --------------------------------------------------------------------------
# Step 2 — Predicate sweep on BaseSubscription (PR 2a + 2b)
# --------------------------------------------------------------------------
step_predicate_sweep() {
    log_step "PR 2 — Predicate sweep on BaseSubscription"
    if ! confirm "Run PR 2 (predicate sweep — runs the PHP codemod)?"; then
        log_action "skipped"
        return
    fi

    # The actual predicate removals are encoded in remove-predicates.php so the
    # logic is reviewable. This step invokes it via php with the same
    # --dry-run / --apply contract.
    local mode_flag
    if $DRY_RUN; then
        mode_flag="--dry-run"
    else
        mode_flag="--apply"
    fi
    run_or_print "php $(dirname "${BASH_SOURCE[0]}")/remove-predicates.php $mode_flag 2>&1 | tee $LOG_DIR/pr2-predicates-$TIMESTAMP.log"
}

# --------------------------------------------------------------------------
# Step 3 — Dual-write removal in BaseSubscription (PR 3)
# --------------------------------------------------------------------------
step_dual_write_removal() {
    log_step "PR 3 — Delete dual-write writers (BaseSubscription::useSession + ::returnSession)"
    if ! confirm "Run PR 3 (dual-write writer removal)?"; then
        log_action "skipped"
        return
    fi
    log_action "PR 3 is a multi-step migration:"
    log_action "  1. Migrate CountsTowardsSubscription trait → SubscriptionConsumption::record"
    log_action "  2. Migrate SessionCountingService → SubscriptionConsumption::record/::reverse"
    log_action "  3. Replace MeetingAttendance.subscription_counted_at writers with accessor"
    log_action "  4. Replace BaseSession.subscription_counted writers with accessor"
    log_action "  5. Delete BaseSubscription::useSession + ::returnSession"
    log_action "  6. Delete BaseSubscription mutators activate/settleCurrentCycle/cancel/pause/resume"
    log_action ""
    log_action "Each sub-step needs human review — this script does NOT auto-apply PR 3."
    log_action "Use the migration plan + remove-predicates.php as the reference."
}

# --------------------------------------------------------------------------
# Step 4 — Legacy service deletions (PR 4a–4g)
# --------------------------------------------------------------------------
step_service_deletes() {
    log_step "PR 4 — Legacy service deletions"
    log_action "Each sub-PR (4a-4g) must run independently; this step lists the targets."
    log_action ""
    log_action "PR 4a — git rm app/Services/Subscription/SubscriptionRenewalService.php"
    log_action "PR 4b — git rm app/Services/StudentSubscriptionService.php"
    log_action "PR 4c — git rm app/Services/SubscriptionService.php + app/Contracts/SubscriptionServiceInterface.php"
    log_action "PR 4d — git rm app/Services/Subscription/SubscriptionCreationService.php + AdminSubscriptionWizardService.php"
    log_action "PR 4e — git rm app/Services/Subscription/SubscriptionMaintenanceService.php"
    log_action "PR 4f — git rm app/Services/*SubscriptionDetailsService.php (4 files)"
    log_action "PR 4g — git rm app/Services/Subscription/SubscriptionFailureCounter.php + SubscriptionTypeResolver.php + SubscriptionQueryService.php (after rename decision)"
    log_action ""
    log_action "Each must be paired with caller-migration commits (see migration plan)."
    log_action "Do NOT auto-run; call-site migrations require review."
}

# --------------------------------------------------------------------------
# Step 5 — Cron schedule cleanup (PR 5)
# --------------------------------------------------------------------------
step_cron_cleanup() {
    log_step "PR 5 — Cron schedule cleanup"
    if ! confirm "Run PR 5b (delete superseded crons; PR 5a/5c are manual)?"; then
        log_action "skipped"
        return
    fi
    # PR 5b — superseded crons replaced by subscriptions:invariant-check.
    # Note: AuditCycleCounts.php and ReconcileSessionCounting.php are already
    # deleted in PR 1 (audit-only). The schedule entries in routes/console.php
    # still need a hand edit.
    log_action "Schedule entries to remove from routes/console.php (manual edit):"
    log_action "  - lines 281-296: subscriptions:reconcile-missed + subscriptions:audit-cycle-counts"
    log_action ""
    log_action "Schedule entries to KEEP but rewrite (PR 5a — manual):"
    log_action "  - lines 183-189: subscriptions:expire-active   (must call SubscriptionLifecycle::expire)"
    log_action "  - lines 193-199: subscriptions:advance-cycles  (must call SubscriptionLifecycle::advanceCycle)"
    log_action "  - lines 203-209: subscriptions:send-expiry-reminders (must use SubscriptionViewState)"
    log_action ""
    log_action "Schedule entries gated on REOPEN (PR 5c — verify reconciler first):"
    log_action "  - lines 214-219: subscriptions:cleanup-expired-pending"
    log_action "  - lines 225-231: subscriptions:cleanup-abandoned-queued"
}

# --------------------------------------------------------------------------
# Step 6 — Translation key sweep (PR 6)
# --------------------------------------------------------------------------
step_lang_sweep() {
    log_step "PR 6 — Translation key sweep"
    if ! confirm "Run PR 6 (drop orphaned lang keys after caller-migration is complete)?"; then
        log_action "skipped"
        return
    fi

    # Candidate keys (per inventory §5, Y or Y, REOPEN).
    # We grep-check each one across the whole codebase first; if a caller
    # still references it, we abort the sweep and report.
    local lang_keys=(
        "awaiting_payment"
        "awaiting_payment_long"
        "cancel_reason_student"
        "renewal_payment_pending"
        "sessions_exhausted"
        "sessions_exhausted_message"
        "grace_period_label"
    )

    log_action "Checking caller references for each key..."
    local missing=0
    for key in "${lang_keys[@]}"; do
        # Match both `subscriptions.<key>` and `__('subscriptions.<key>')`.
        local hits
        hits=$(grep -rln "subscriptions\\.$key" app/ resources/ 2>/dev/null | wc -l | tr -d ' ')
        if [[ "$hits" -gt 0 ]]; then
            log_action "  KEEP — $key has $hits caller(s); abort delete (some PR 2/PR 4 caller wasn't migrated)"
            missing=$((missing + 1))
        else
            log_action "  DROP — $key has zero callers; safe to remove from lang files"
        fi
    done

    if [[ "$missing" -gt 0 ]]; then
        log_action ""
        log_action "$missing key(s) still referenced. Fix the callers before re-running PR 6."
        return
    fi

    log_action ""
    log_action "All orphaned keys verified — operator must hand-edit lang/{ar,en}/subscriptions.php to drop them."
    log_action "(Not auto-edited here because lang files contain multi-line PHP arrays that sed garbles.)"
}

# --------------------------------------------------------------------------
# Step 7 — Memory file rename (PR 7)
# --------------------------------------------------------------------------
step_memory_rename() {
    log_step "PR 7 — Memory file rename (_archived_ prefix)"
    if ! confirm "Run PR 7 (rename superseded memory files)?"; then
        log_action "skipped"
        return
    fi

    local memory_dir="$HOME/.claude/projects/-Users-abdelrahmanhamdy-web-itqan/memory"
    if [[ ! -d "$memory_dir" ]]; then
        log_action "ERROR: $memory_dir not found; skip"
        return
    fi

    local rename_targets=(
        "resolved_renew_unpaid_current_cycle_gate.md"
        "followup_active_subs_unpaid_current_cycle.md"
        "followup_payment_routing_to_cancelled_sub.md"
        "followup_cycle_counter_drift_repair.md"
        "followup_session_cycle_id.md"
    )

    for f in "${rename_targets[@]}"; do
        local src="$memory_dir/$f"
        local dst="$memory_dir/_archived_$f"
        if [[ -f "$src" ]]; then
            run_or_print "mv \"$src\" \"$dst\""
        else
            log_action "skipped (not found): $src"
        fi
    done
}

# --------------------------------------------------------------------------
# Dispatcher
# --------------------------------------------------------------------------
log_step "Phase E cleanup runner — start"
step_archive_backfills
step_predicate_sweep
step_dual_write_removal
step_service_deletes
step_cron_cleanup
step_lang_sweep
step_memory_rename

echo
log "Final git status:"
git status --short || true
echo
log "Done. Logs in $LOG_DIR/"
