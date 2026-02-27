@extends('help.layouts.article', ['role' => 'developer', 'slug' => 'subscription-system'])

@section('title', 'Subscription System')

@section('content')

<div class="prose prose-slate max-w-none">

    {{-- =========================================================
         1. Subscription Architecture
         ========================================================= --}}
    <h2 id="architecture">Subscription Architecture</h2>
    <p>
        The platform has <strong>three subscription types</strong>, all extending a shared <code>BaseSubscription</code>
        abstract model that carries common billing, pricing, and lifecycle fields:
    </p>
    <ul>
        <li>
            <strong>QuranSubscription</strong> — Links a student to a Quran teacher (individual) or circle (group).
            Supports monthly / quarterly / yearly billing with auto-renewal.
        </li>
        <li>
            <strong>AcademicSubscription</strong> — Links a student to an academic teacher for private lessons.
            Supports monthly / quarterly / yearly billing with auto-renewal.
            Carries a <code>weekly_schedule</code> JSON field (recurring lesson times).
        </li>
        <li>
            <strong>CourseSubscription</strong> — One-time purchase granting access to a pre-recorded or interactive course.
            Billing cycle is always <code>LIFETIME</code>. No auto-renewal, no session quota counting.
        </li>
    </ul>
    <p>
        Quran and Academic subscriptions use the <strong><code>CountsTowardsSubscription</code></strong> trait on their
        related session models to decrement/increment session quotas, and the
        <strong><code>HandlesSubscriptionRenewal</code></strong> trait on the subscription models themselves for
        automated billing.
    </p>

    {{-- =========================================================
         2. BaseSubscription Common Fields
         ========================================================= --}}
    <h2 id="base-fields">BaseSubscription Common Fields</h2>

    <div class="help-table-wrapper">
        <table class="help-table">
            <thead>
                <tr>
                    <th>Field Group</th>
                    <th>Fields</th>
                    <th>Description</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td><strong>Status</strong></td>
                    <td>
                        <code>status</code>,
                        <code>payment_status</code>
                    </td>
                    <td>
                        <code>status</code> → <code>SessionSubscriptionStatus</code> enum
                        (<code>pending</code>, <code>active</code>, <code>completed</code>, <code>cancelled</code>, <code>expired</code>, <code>suspended</code>).
                        <code>payment_status</code> → <code>SubscriptionPaymentStatus</code> enum
                        (<code>unpaid</code>, <code>paid</code>, <code>refunded</code>, <code>failed</code>).
                    </td>
                </tr>
                <tr>
                    <td><strong>Pricing</strong></td>
                    <td>
                        <code>monthly_price</code>,
                        <code>quarterly_price</code>,
                        <code>yearly_price</code>,
                        <code>discount_amount</code>,
                        <code>final_price</code>,
                        <code>currency</code>
                    </td>
                    <td>
                        All three tier prices stored for reference even if only one billing cycle is chosen.
                        <code>currency</code> stores ISO code (e.g. <code>SAR</code>, <code>EGP</code>).
                        <code>final_price</code> is the actual charged amount after discount.
                    </td>
                </tr>
                <tr>
                    <td><strong>Billing</strong></td>
                    <td>
                        <code>billing_cycle</code>,
                        <code>starts_at</code>,
                        <code>ends_at</code>,
                        <code>next_billing_date</code>,
                        <code>auto_renew</code>
                    </td>
                    <td>
                        <code>billing_cycle</code> → <code>BillingCycle</code> enum
                        (<code>monthly</code>, <code>quarterly</code>, <code>yearly</code>, <code>lifetime</code>).
                        <code>auto_renew</code> boolean — when false, subscription expires at <code>ends_at</code>
                        without attempting renewal.
                    </td>
                </tr>
                <tr>
                    <td><strong>Package snapshot</strong></td>
                    <td>
                        <code>package_name_ar</code>,
                        <code>package_name_en</code>,
                        <code>package_features</code>,
                        <code>package_description_ar</code>,
                        <code>package_description_en</code>
                    </td>
                    <td>
                        Snapshot of the package at time of purchase. Stored so historical records are unaffected
                        if the package is later edited. <code>package_features</code> is a JSON array of feature strings.
                    </td>
                </tr>
                <tr>
                    <td><strong>Progress</strong></td>
                    <td>
                        <code>progress_percentage</code>,
                        <code>certificate_issued</code>
                    </td>
                    <td>
                        Calculated progress (0–100) for course subscriptions.
                        <code>certificate_issued</code> boolean set once <code>CertificateService</code> generates the PDF.
                    </td>
                </tr>
                <tr>
                    <td><strong>Renewal tracking</strong></td>
                    <td>
                        <code>renewal_reminder_sent_at</code>,
                        <code>cancelled_at</code>,
                        <code>cancellation_reason</code>
                    </td>
                    <td>
                        Timestamps and reason for administrative tracking of renewal reminders and cancellations.
                    </td>
                </tr>
            </tbody>
        </table>
    </div>

    {{-- =========================================================
         3. Subscription Types Comparison
         ========================================================= --}}
    <h2 id="types-comparison">Subscription Types Comparison</h2>

    <div class="help-table-wrapper overflow-x-auto">
        <table class="help-table">
            <thead>
                <tr>
                    <th>Feature</th>
                    <th>QuranSubscription</th>
                    <th>AcademicSubscription</th>
                    <th>CourseSubscription</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td><strong>DB Table</strong></td>
                    <td><code>quran_subscriptions</code></td>
                    <td><code>academic_subscriptions</code></td>
                    <td><code>course_subscriptions</code></td>
                </tr>
                <tr>
                    <td><strong>Session counting</strong></td>
                    <td>YES (<code>CountsTowardsSubscription</code>)</td>
                    <td>YES (<code>CountsTowardsSubscription</code>)</td>
                    <td>NO</td>
                </tr>
                <tr>
                    <td><strong>Auto-renewal</strong></td>
                    <td>YES (<code>HandlesSubscriptionRenewal</code>)</td>
                    <td>YES (<code>HandlesSubscriptionRenewal</code>)</td>
                    <td>NO</td>
                </tr>
                <tr>
                    <td><strong>Billing cycle</strong></td>
                    <td><code>monthly</code> / <code>quarterly</code> / <code>yearly</code></td>
                    <td><code>monthly</code> / <code>quarterly</code> / <code>yearly</code></td>
                    <td><code>LIFETIME</code> only (one-time purchase)</td>
                </tr>
                <tr>
                    <td><strong>Weekly schedule</strong></td>
                    <td>NO</td>
                    <td>YES — JSON: <code>[{day, time}]</code></td>
                    <td>NO</td>
                </tr>
                <tr>
                    <td><strong>Teacher FK</strong></td>
                    <td><code>quran_teacher_id → users.id</code></td>
                    <td><code>academic_teacher_id → users.id</code></td>
                    <td>N/A</td>
                </tr>
                <tr>
                    <td><strong>Circle / group type</strong></td>
                    <td><code>individual</code> or <code>circle</code></td>
                    <td><code>individual</code> only</td>
                    <td>N/A</td>
                </tr>
                <tr>
                    <td><strong>Circle FK</strong></td>
                    <td><code>quran_circle_id</code> (null for individual)</td>
                    <td>—</td>
                    <td>—</td>
                </tr>
                <tr>
                    <td><strong>Duplicate guard trait</strong></td>
                    <td><code>PreventsDuplicatePendingSubscriptions</code></td>
                    <td><code>PreventsDuplicatePendingSubscriptions</code></td>
                    <td>NOT applied</td>
                </tr>
            </tbody>
        </table>
    </div>

    {{-- =========================================================
         4. Subscription Lifecycle
         ========================================================= --}}
    <h2 id="lifecycle">Subscription Lifecycle</h2>

    <div class="mermaid-wrapper my-6">
        <div class="mermaid">
stateDiagram-v2
    [*] --> PENDING : Student purchases\nsubscription

    PENDING --> ACTIVE : Payment success\nwebhook received

    ACTIVE --> COMPLETED : All sessions used\nOR end date passed\n(no renewal)
    ACTIVE --> CANCELLED : Manual cancellation\n(admin / student)
    ACTIVE --> EXPIRED : end date passed\nauto_renew = false

    ACTIVE --> GRACE_PERIOD : Renewal payment fails\n(attempt 1 of 3)
    GRACE_PERIOD --> ACTIVE : Retry succeeds\nwithin 3-day window
    GRACE_PERIOD --> SUSPENDED : 3 retry attempts failed\nOR grace period expired

    SUSPENDED --> ACTIVE : Manual reactivation\n+ new payment

    COMPLETED --> [*]
    CANCELLED --> [*]
    EXPIRED --> [*]
        </div>
    </div>

    {{-- =========================================================
         5. Auto-Renewal Flow
         ========================================================= --}}
    <h2 id="auto-renewal">Auto-Renewal Flow</h2>

    <div class="mermaid-wrapper my-6">
        <div class="mermaid">
sequenceDiagram
    participant CRON as Scheduler (daily 06:00 UTC)
    participant CMD  as ProcessSubscriptionRenewalsCommand
    participant TRAIT as HandlesSubscriptionRenewal trait
    participant PAY  as PaymentRenewalService
    participant GW   as Payment Gateway (Paymob / EasyKash)
    participant NOTIF as NotificationService

    CRON->>CMD: Trigger daily renewal check
    CMD->>TRAIT: Find subscriptions due for renewal\n(next_billing_date <= today AND auto_renew = true)

    loop For each eligible subscription
        TRAIT->>PAY: Charge stored card / wallet
        PAY->>GW: API payment request

        alt Payment success
            GW-->>PAY: Success response
            PAY->>TRAIT: Extend dates, reset sessions_remaining
            TRAIT->>NOTIF: Send RenewalSuccessNotification
        else Attempt 1 or 2 fails
            GW-->>PAY: Failure response
            PAY->>TRAIT: Schedule retry (exponential backoff: 24h, 48h)
            TRAIT->>NOTIF: Send RenewalFailedWarningNotification
        else Attempt 3 fails
            GW-->>PAY: Failure response
            PAY->>TRAIT: Start 3-day grace period
            TRAIT->>NOTIF: Send GracePeriodStartedNotification
        end

        alt Grace period expired (3 days)
            TRAIT->>TRAIT: Set status = SUSPENDED
            TRAIT->>NOTIF: Send SubscriptionSuspendedNotification
        end
    end
        </div>
    </div>

    {{-- =========================================================
         6. Key Field Notes (Warning boxes)
         ========================================================= --}}
    <h2 id="key-field-notes">Key Field Notes</h2>

    <div class="help-warning">
        <strong>CourseSubscription.billing_cycle is always <code>BillingCycle::LIFETIME</code>.</strong>
        A course purchase is a one-time transaction. Never set a date-based billing cycle on a
        CourseSubscription or attempt to run renewal logic against it.
    </div>

    <div class="help-warning">
        <strong>QuranSubscription.quran_circle_id</strong> is only set for circle (group) subscriptions.
        It is <code>null</code> for individual 1-on-1 subscriptions.
        Always check <code>$subscription->type</code> (<code>'individual'</code> or <code>'circle'</code>)
        before accessing the circle relationship.
    </div>

    <div class="help-warning">
        <strong>AcademicSubscription.weekly_schedule</strong> is a JSON array of day/time objects:<br>
        <code>[{"day": "monday", "time": "15:00"}, {"day": "thursday", "time": "16:30"}]</code><br>
        This drives the <code>AutoMeetingCreationService</code> when generating recurring academic sessions.
        The field is cast to an array automatically by Eloquent.
    </div>

    <div class="help-note">
        <strong>subscription_counted flag on session models</strong> — once set to <code>true</code>,
        the <code>CountsTowardsSubscription</code> trait will skip any further processing for that session,
        even if the background job re-runs. This is the primary guard against double-counting.
        Never manually set this flag to <code>false</code> without also decrementing the
        subscription's <code>sessions_used</code> counter.
    </div>

    {{-- =========================================================
         7. PreventsDuplicatePendingSubscriptions Trait
         ========================================================= --}}
    <h2 id="duplicate-guard">PreventsDuplicatePendingSubscriptions Trait</h2>

    <div class="help-note">
        <strong>Location:</strong> <code>app/Models/Traits/PreventsDuplicatePendingSubscriptions.php</code>
    </div>

    <p>
        Applied to <strong>QuranSubscription</strong> and <strong>AcademicSubscription</strong> (not CourseSubscription).
        Hooks into the model's <code>creating</code> event and throws a domain exception if the student already has
        a <code>PENDING</code> subscription of the same type with the same teacher.
    </p>

    <p><strong>Why it exists:</strong> Without this guard, a student could click "subscribe" multiple times
    during slow payment gateway redirects, creating duplicate pending subscriptions for the same teacher slot.
    </p>

    <p><strong>What it checks:</strong></p>
    <ul>
        <li>Same <code>student_id</code></li>
        <li>Same <code>quran_teacher_id</code> / <code>academic_teacher_id</code></li>
        <li>Status is <code>PENDING</code> (active subscriptions are allowed to co-exist for upgrade flows)</li>
    </ul>

    <p><strong>CourseSubscription is excluded</strong> because a student may legitimately purchase the same course
    twice (as a gift, or after a refund) — duplicate handling for course purchases is done
    at the payment/enrollment layer instead.
    </p>

</div>

@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/mermaid@10/dist/mermaid.min.js"></script>
<script>
mermaid.initialize({
    startOnLoad: true,
    theme: 'base',
    themeVariables: {
        darkMode: true,
        fontFamily: 'monospace, Consolas',
        fontSize: '13px',
        background: '#0f172a',
        mainBkg: '#1e293b',
        nodeBorder: '#3b82f6',
        clusterBkg: '#0f172a',
        titleColor: '#e2e8f0',
        edgeLabelBackground: '#1e293b',
        primaryColor: '#1d3461',
        primaryBorderColor: '#3b82f6',
        primaryTextColor: '#e2e8f0',
        secondaryColor: '#1e293b',
        secondaryBorderColor: '#475569',
        secondaryTextColor: '#cbd5e1',
        tertiaryColor: '#334155',
        tertiaryBorderColor: '#475569',
        tertiaryTextColor: '#cbd5e1',
        lineColor: '#64748b',
        textColor: '#e2e8f0',
        nodeTextColor: '#e2e8f0',
        attributeBackgroundColorEven: '#1e293b',
        attributeBackgroundColorOdd: '#0f172a',
    }
});
</script>
@endpush
