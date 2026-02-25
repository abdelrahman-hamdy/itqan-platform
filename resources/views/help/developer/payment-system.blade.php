@extends('help.layouts.article', ['role' => 'developer', 'slug' => 'payment-system'])

@section('content')

<h2 id="gateways">Payment Gateways</h2>

<div style="overflow-x: auto; direction: ltr;">
<table class="help-table">
    <thead>
        <tr>
            <th>Gateway</th>
            <th>Region</th>
            <th>Currencies</th>
            <th>Methods</th>
            <th>Mode</th>
        </tr>
    </thead>
    <tbody>
        <tr>
            <td><strong>Paymob</strong></td>
            <td>Egypt</td>
            <td>EGP</td>
            <td>Card, Wallet, Installments, Apple Pay</td>
            <td><span class="help-badge help-badge-amber">SANDBOX=true (test)</span></td>
        </tr>
        <tr>
            <td><strong>EasyKash</strong></td>
            <td>Egypt</td>
            <td>EGP (SAR converted)</td>
            <td>Cash/E-wallet</td>
            <td><span class="help-badge help-badge-green">LIVE</span></td>
        </tr>
        <tr>
            <td><strong>Tapay</strong></td>
            <td>GCC</td>
            <td>SAR, AED, KWD</td>
            <td>Card</td>
            <td><span class="help-badge help-badge-slate">Configured</span></td>
        </tr>
        <tr>
            <td><strong>Moyasar</strong></td>
            <td>Saudi Arabia</td>
            <td>SAR</td>
            <td>Card, Apple Pay</td>
            <td><span class="help-badge help-badge-slate">Configured</span></td>
        </tr>
        <tr>
            <td><strong>STC Pay</strong></td>
            <td>Saudi Arabia</td>
            <td>SAR</td>
            <td>STC wallet</td>
            <td><span class="help-badge help-badge-slate">Configured</span></td>
        </tr>
    </tbody>
</table>
</div>

<h2 id="architecture">Payment Architecture</h2>

<div class="help-mermaid">
<pre class="mermaid">
graph TB
    Student[Student clicks Pay]
    PS[PaymentService<br/>app/Services/PaymentService.php]
    GM[PaymentGatewayManager<br/>Laravel Manager pattern]
    AF[AcademyPaymentGatewayFactory<br/>Academy-specific selection]
    GW[Gateway Implementation<br/>Paymob / EasyKash / Tapay]
    CB[Callback URL<br/>metadata only, NOT authoritative]
    WH[Webhook URL<br/>AUTHORITATIVE for activation]
    SUB[SubscriptionService<br/>activate subscription]
    NOT[NotificationService<br/>send receipts]

    Student --> PS
    PS --> GM --> AF --> GW
    GW --> CB
    GW --> WH
    WH --> PS --> SUB --> NOT
    style WH fill:#dcfce7,stroke:#16a34a
    style CB fill:#fef9c3,stroke:#ca8a04
</pre>
</div>

<div class="help-danger">
    <i class="ri-error-warning-line help-callout-icon"></i>
    <div>
        <strong>Critical: Webhook is the ONLY authoritative source for subscription activation.</strong>
        The payment callback URL (<code>/payments/callback</code>) only stores metadata —
        it must NEVER activate a subscription. Only the webhook handler
        (<code>/webhooks/paymob</code>, <code>/webhooks/easykash</code>) activates subscriptions,
        after validating the gateway signature.
    </div>
</div>

<h2 id="state-machine">Payment State Machine</h2>

<div class="help-mermaid">
<pre class="mermaid">
stateDiagram-v2
    [*] --> PENDING: Payment initiated
    PENDING --> PROCESSING: Gateway redirects student
    PROCESSING --> COMPLETED: Webhook confirms success
    PROCESSING --> FAILED: Webhook reports failure
    PENDING --> EXPIRED: 24 hours elapsed (daily cleanup job)
    COMPLETED --> REFUNDED: Manual refund issued
    FAILED --> PENDING: Student retries (max 3 attempts)
    CANCELLED --> [*]
    COMPLETED --> [*]
    REFUNDED --> [*]
</pre>
</div>

<h2 id="webhook-security">Webhook Security</h2>

<div style="overflow-x: auto; direction: ltr;">
<table class="help-table">
    <thead><tr><th>Gateway</th><th>Validation Method</th><th>IP Whitelist</th></tr></thead>
    <tbody>
        <tr>
            <td>Paymob</td>
            <td>HMAC-SHA512 signature (<code>PaymobSignatureService</code>)</td>
            <td><code>PAYMOB_WEBHOOK_IPS</code> env var</td>
        </tr>
        <tr>
            <td>EasyKash</td>
            <td>Custom signature (<code>EasyKashSignatureService</code>)</td>
            <td><code>EASYKASH_WEBHOOK_IPS</code> env var</td>
        </tr>
        <tr>
            <td>LiveKit</td>
            <td>JWT signature verification</td>
            <td>N/A</td>
        </tr>
    </tbody>
</table>
</div>

<h2 id="fees-tax">Fees & Tax Configuration</h2>

<div style="overflow-x: auto; direction: ltr;">
<table class="help-table">
    <thead><tr><th>Item</th><th>Rate</th><th>Config Key</th></tr></thead>
    <tbody>
        <tr><td>Card payment fee</td><td>2.5%</td><td><code>payments.fees.card</code></td></tr>
        <tr><td>Wallet payment fee</td><td>2.0%</td><td><code>payments.fees.wallet</code></td></tr>
        <tr><td>Installment fee</td><td>3.0%</td><td><code>payments.fees.installments</code></td></tr>
        <tr><td>VAT — Saudi (SAR)</td><td>15%</td><td><code>payments.tax.SAR</code></td></tr>
        <tr><td>VAT — Egypt (EGP)</td><td>14%</td><td><code>payments.tax.EGP</code></td></tr>
        <tr><td>VAT — UAE (AED)</td><td>5%</td><td><code>payments.tax.AED</code></td></tr>
        <tr><td>Intent expiry</td><td>60 minutes</td><td><code>payments.security.intent_expiry_minutes</code></td></tr>
        <tr><td>3D Secure</td><td>Enabled</td><td><code>payments.security.3ds_enabled</code></td></tr>
    </tbody>
</table>
</div>

<h2 id="renewal">Auto-Renewal Flow</h2>

<div class="help-mermaid">
<pre class="mermaid">
sequenceDiagram
    participant CRON as ProcessSubscriptionRenewalsCommand<br/>(daily 06:00 UTC)
    participant SRS as SubscriptionRenewalService
    participant PRS as PaymentRenewalService
    participant GW as Payment Gateway
    participant NS as NotificationService

    CRON->>SRS: Find subscriptions due for renewal
    SRS->>PRS: chargeStoredMethod(subscription)
    PRS->>GW: Charge stored card/wallet token

    alt Success
        GW-->>PRS: Payment confirmed
        PRS->>SRS: extend dates, reset sessions_remaining
        SRS->>NS: Send renewal success notification
    else Failure (attempt 1 or 2)
        GW-->>PRS: Payment failed
        PRS->>SRS: Schedule retry (exponential backoff)
        SRS->>NS: Send renewal failed notification
    else Failure (attempt 3)
        GW-->>PRS: Payment failed
        PRS->>SRS: Start 3-day grace period
        SRS->>NS: Send grace period warning
    end

    Note over SRS: After grace period expires (hourly job)
    SRS->>SRS: Suspend subscription
    SRS->>NS: Send suspension notification
</pre>
</div>

<h2 id="paymob-notes">Paymob-Specific Notes</h2>

<div class="help-note">
    <i class="ri-information-line help-callout-icon"></i>
    <div>
        <p><strong>Paymob uses two API modes:</strong></p>
        <ul>
            <li><strong>Unified Intention API</strong> — Modern API for card/wallet/Apple Pay</li>
            <li><strong>Classic API</strong> — Legacy API for installments integration</li>
        </ul>
        <p>Keys use format <code>egy_sk_live_*</code> for live, <code>egy_sk_test_*</code> for sandbox.
        Default is production mode (<code>false</code>). Set <code>PAYMOB_SANDBOX=true</code> in
        <code>.env</code> for local sandbox testing.</p>
    </div>
</div>

@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/mermaid@11/dist/mermaid.min.js"></script>
<script>
mermaid.initialize({
    startOnLoad: true,
    theme: 'base',
    themeVariables: {
        fontFamily: 'monospace, Consolas',
        fontSize: '13px',
        primaryColor: '#dbeafe',
        primaryBorderColor: '#3b82f6',
        primaryTextColor: '#1e3a8a',
        lineColor: '#6b7280',
        secondaryColor: '#f0fdf4',
    }
});
</script>
@endpush
