<?php

namespace App\Http\Resources\Api\V1\Subscription;

use App\Http\Resources\Api\V1\Student\StudentListResource;
use App\Models\BaseSubscription;
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
        return [
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
            'in_grace_period' => method_exists($this->resource, 'isInGracePeriod') ? $this->isInGracePeriod() : false,
            'needs_renewal' => method_exists($this->resource, 'needsRenewal') ? $this->needsRenewal() : false,
            'grace_period_ends_at' => method_exists($this->resource, 'getGracePeriodEndsAt')
                ? $this->getGracePeriodEndsAt()?->toISOString()
                : null,
            'paid_until' => $this->ends_at?->toISOString(),

            // Notes
            'notes' => $this->notes,

            // Timestamps
            'created_at' => $this->created_at->toISOString(),
            'updated_at' => $this->updated_at->toISOString(),
        ];
    }
}
