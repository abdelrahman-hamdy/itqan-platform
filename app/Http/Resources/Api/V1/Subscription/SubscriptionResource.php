<?php

namespace App\Http\Resources\Api\V1\Subscription;

use App\Http\Resources\Api\V1\Student\StudentListResource;
use App\Models\BaseSubscription;
use App\Services\Subscription\SubscriptionPresentation;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Base Subscription Resource
 *
 * Provides polymorphic support for all subscription types:
 * - QuranSubscription
 * - AcademicSubscription
 * - CourseSubscription
 *
 * Phase A.7 / INV-J2: surfaces `view_state`, `primary_action`, and
 * `helper_line` from {@see SubscriptionPresentation::formatForApi()} so the
 * mobile app consumes a canonical state object instead of re-deriving state
 * from a tangle of `status` + `payment_status` flags. The legacy boolean
 * derivations (in_grace_period, needs_renewal, expired_with_leftover_sessions)
 * remain during the migration window — older mobile builds still read them —
 * and are scheduled for removal once the new client ships (Phase E).
 *
 * @mixin BaseSubscription
 */
class SubscriptionResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $presentation = resolve(SubscriptionPresentation::class)->formatForApi($this->resource);

        return [
            // Phase A.7 canonical view-state surface (INV-J2). The mobile-app
            // subscription card binds directly to `view_state` +
            // `primary_action` + `helper_line`; do not re-derive in the
            // client. Mirrored `sessions_remaining`, `ends_at`, and
            // `grace_period_ends_at` are present below for legacy fields.
            'view_state' => $presentation['view_state'],
            'primary_action' => $presentation['primary_action'],
            'helper_line' => $presentation['helper_line'],
            'id' => $this->id,
            'type' => $this->getMorphClass(),
            'subscription_code' => $this->subscription_code,

            // Package information
            'package' => [
                'name_ar' => $this->package_name_ar,
                'name_en' => $this->package_name_en,
                'description_ar' => $this->package_description_ar,
                'description_en' => $this->package_description_en,
                'features' => $this->package_features,
            ],

            // Status
            'status' => [
                'value' => $this->status->value,
                'label' => $this->status->label(),
                'color' => $this->status->color(),
                'icon' => $this->status->icon(),
            ],

            // Pricing
            'pricing' => [
                'monthly_price' => $this->monthly_price ? (float) $this->monthly_price : null,
                'quarterly_price' => $this->quarterly_price ? (float) $this->quarterly_price : null,
                'yearly_price' => $this->yearly_price ? (float) $this->yearly_price : null,
                'discount_amount' => $this->discount_amount ? (float) $this->discount_amount : null,
                'final_price' => (float) $this->final_price,
                'currency' => $this->currency,
            ],

            // Billing
            'billing_cycle' => [
                'value' => $this->billing_cycle->value,
                'label' => $this->billing_cycle->label(),
            ],
            'payment_status' => [
                'value' => $this->payment_status->value,
                'label' => $this->payment_status->label(),
            ],

            // Dates
            'starts_at' => $this->starts_at?->toISOString(),
            'ends_at' => $this->ends_at?->toISOString(),
            'next_billing_date' => $this->next_billing_date?->toISOString(),
            'last_payment_date' => $this->last_payment_date?->toISOString(),

            // Renewal
            'auto_renew' => $this->auto_renew,
            'renewal_reminder_sent_at' => $this->renewal_reminder_sent_at?->toISOString(),

            // Cancellation
            'cancelled_at' => $this->cancelled_at?->toISOString(),
            'cancellation_reason' => $this->when($this->cancelled_at, $this->cancellation_reason),

            // Progress
            'progress_percentage' => (float) $this->progress_percentage,

            // Certificate
            'certificate' => [
                'issued' => $this->certificate_issued,
                'issued_at' => $this->certificate_issued_at?->toISOString(),
            ],

            // Student
            'student' => $this->whenLoaded('student', fn () => new StudentListResource($this->student)),

            // Academy
            'academy' => $this->whenLoaded('academy', [
                'id' => $this->academy?->id,
                'name' => $this->academy?->name,
                'subdomain' => $this->academy?->subdomain,
            ]),

            // Grace period
            'in_grace_period' => $this->isInGracePeriod(),
            'needs_renewal' => $this->needsRenewal(),
            'grace_period_ends_at' => $this->getGracePeriodEndsAt()?->toISOString(),
            'paid_until' => $this->ends_at?->toISOString(),

            // Expired-with-leftover banner — true when the subscription has
            // elapsed (ends_at past, status paused/expired) with paid sessions
            // still unconsumed. Leftover sessions roll into the next cycle on
            // renewal (Rule 3) so the count is preserved.
            'expired_with_leftover_sessions' => $this->hasExpiredWithLeftoverSessions(),

            // Cycle tracking
            'current_cycle_id' => $this->current_cycle_id,
            'cycle_count' => (int) ($this->cycle_count ?? 1),
            'current_cycle' => $this->whenLoaded('currentCycle', function () {
                $cycle = $this->currentCycle;

                return [
                    'id' => $cycle->id,
                    'cycle_number' => $cycle->cycle_number,
                    'state' => $cycle->cycle_state,
                    'billing_cycle' => $cycle->billing_cycle,
                    'starts_at' => $cycle->starts_at?->toDateString(),
                    'ends_at' => $cycle->ends_at?->toDateString(),
                    'total_sessions' => (int) $cycle->total_sessions,
                    'sessions_used' => (int) $cycle->sessions_used,
                    'sessions_remaining' => max(0, (int) $cycle->total_sessions - (int) $cycle->sessions_used),
                    'total_price' => (float) ($cycle->total_price ?? 0),
                    'final_price' => (float) ($cycle->final_price ?? 0),
                    'currency' => $cycle->currency,
                    'payment_status' => $cycle->payment_status,
                ];
            }),

            // Notes
            'notes' => $this->notes,

            // Timestamps
            'created_at' => $this->created_at->toISOString(),
            'updated_at' => $this->updated_at->toISOString(),
        ];
    }
}
