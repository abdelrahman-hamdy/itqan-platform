<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Append-only structured audit row written by every subscription mutator
 * (Phase C of the subscription v2 refactor; see docs/subscription-invariants.md §9
 * and docs/subscription-recovery-plan.md Phase C).
 *
 * One row per (action, sub, before-snapshot, after-snapshot) — written
 * inside the locked mutator's transaction so it commits or rolls back
 * with the mutation it describes.
 *
 * NEVER write to this table directly; always go through ::record() so:
 *   1. before_state / after_state snapshot shape stays consistent
 *      (use SubscriptionSnapshot::capture() to build them).
 *   2. has_violations stays in sync with invariant_violations.
 *   3. a corrupt payload never breaks the user-facing write.
 */
class SubscriptionAuditLog extends Model
{
    protected $table = 'subscription_audit_log';

    public $timestamps = false;

    protected $fillable = [
        'subscription_id',
        'subscription_type',
        'cycle_id',
        'action',
        'source',
        'actor_user_id',
        'before_state',
        'after_state',
        'view_state_before',
        'view_state_after',
        'invariant_violations',
        'has_violations',
        'latency_ms',
        'created_at',
    ];

    protected $casts = [
        'before_state' => 'array',
        'after_state' => 'array',
        'invariant_violations' => 'array',
        'has_violations' => 'boolean',
        'latency_ms' => 'integer',
        'created_at' => 'datetime',
    ];

    // ─────────────────────────────────────────────────────────────────────
    // Relations
    // ─────────────────────────────────────────────────────────────────────

    /**
     * Polymorphic subscription pointer. The `subscription_type` column
     * holds the morph class (Eloquent resolves it via the morph map / FQCN).
     */
    public function subscription(): MorphTo
    {
        return $this->morphTo('subscription', 'subscription_type', 'subscription_id');
    }

    public function cycle(): BelongsTo
    {
        return $this->belongsTo(SubscriptionCycle::class, 'cycle_id');
    }

    public function actorUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'actor_user_id');
    }

    // ─────────────────────────────────────────────────────────────────────
    // Scopes
    // ─────────────────────────────────────────────────────────────────────

    /**
     * Restrict to a single subscription (polymorphic).
     */
    public function scopeForSubscription(Builder $query, BaseSubscription $sub): Builder
    {
        return $query
            ->where('subscription_type', $sub->getMorphClass())
            ->where('subscription_id', $sub->getKey());
    }

    /**
     * Only rows whose invariant_violations payload is non-empty.
     *
     * Uses the regular boolean column `has_violations` (see migration —
     * we replaced the proposed JSON_LENGTH functional index with a generated
     * boolean for portability).
     */
    public function scopeWithViolations(Builder $query): Builder
    {
        return $query->where('has_violations', true);
    }

    // ─────────────────────────────────────────────────────────────────────
    // Writer
    // ─────────────────────────────────────────────────────────────────────

    /**
     * Append one audit row. NEVER throws — audit failures must not break
     * user-facing writes. On any error we log to the `subscriptions` channel
     * and swallow.
     *
     * Accepted payload keys (anything else is ignored):
     *   - subscription:        BaseSubscription (required)
     *   - cycle_id:            int|null
     *   - action:              string (required) e.g. 'pay', 'renew'
     *   - source:              string (required) e.g. 'web', 'cron', 'webhook'
     *   - actor_user_id:       int|null
     *   - before_state:        array (already a SubscriptionSnapshot::capture() result)
     *   - after_state:         array
     *   - view_state_before:   string|null
     *   - view_state_after:    string|null
     *   - invariant_violations: array|null
     *   - latency_ms:          int|null
     *
     * @param  array<string, mixed>  $payload
     */
    public static function record(array $payload): self
    {
        try {
            /** @var BaseSubscription|null $sub */
            $sub = $payload['subscription'] ?? null;

            if (! $sub instanceof BaseSubscription) {
                throw new \InvalidArgumentException(
                    'SubscriptionAuditLog::record requires a BaseSubscription under the "subscription" key.'
                );
            }

            $action = $payload['action'] ?? null;
            $source = $payload['source'] ?? null;

            if (! is_string($action) || $action === '') {
                throw new \InvalidArgumentException('SubscriptionAuditLog::record requires a non-empty "action".');
            }

            if (! is_string($source) || $source === '') {
                throw new \InvalidArgumentException('SubscriptionAuditLog::record requires a non-empty "source".');
            }

            $before = is_array($payload['before_state'] ?? null) ? $payload['before_state'] : [];
            $after = is_array($payload['after_state'] ?? null) ? $payload['after_state'] : [];

            $violations = $payload['invariant_violations'] ?? null;
            if ($violations !== null && ! is_array($violations)) {
                $violations = null;
            }
            $hasViolations = is_array($violations) && count($violations) > 0;

            return static::create([
                'subscription_id' => $sub->getKey(),
                'subscription_type' => $sub->getMorphClass(),
                'cycle_id' => $payload['cycle_id'] ?? null,
                'action' => $action,
                'source' => $source,
                'actor_user_id' => $payload['actor_user_id'] ?? null,
                'before_state' => $before,
                'after_state' => $after,
                'view_state_before' => $payload['view_state_before'] ?? null,
                'view_state_after' => $payload['view_state_after'] ?? null,
                'invariant_violations' => $violations,
                'has_violations' => $hasViolations,
                'latency_ms' => isset($payload['latency_ms']) ? (int) $payload['latency_ms'] : null,
                'created_at' => now(),
            ]);
        } catch (Throwable $e) {
            // Audit-log writes must never break user-facing flows. Log to
            // the `subscriptions` channel (configured in config/logging.php)
            // and return a fresh, unsaved instance so callers can keep going.
            Log::channel('subscriptions')->error('subscription_audit_log_failed', [
                'error' => $e->getMessage(),
                'exception' => $e::class,
                'action' => $payload['action'] ?? null,
                'source' => $payload['source'] ?? null,
                'subscription_id' => isset($payload['subscription']) && $payload['subscription'] instanceof BaseSubscription
                    ? $payload['subscription']->getKey()
                    : null,
                'subscription_type' => isset($payload['subscription']) && $payload['subscription'] instanceof BaseSubscription
                    ? $payload['subscription']->getMorphClass()
                    : null,
            ]);

            return new static;
        }
    }
}
