@extends('help.layouts.article', ['role' => 'developer', 'slug' => 'code-conventions'])

@section('content')

<h2 id="php">PHP Conventions</h2>

<div style="overflow-x: auto; direction: ltr;">
<table class="help-table">
    <thead><tr><th>Rule</th><th>Example</th></tr></thead>
    <tbody>
        <tr>
            <td>Constructor property promotion</td>
            <td><code>public function __construct(private readonly Service $service) {}</code></td>
        </tr>
        <tr>
            <td>Explicit return types</td>
            <td><code>public function create(array $data): Session</code></td>
        </tr>
        <tr>
            <td>PHPDoc over inline comments</td>
            <td>Use <code>/** */</code> blocks for complex logic, not <code>// comment</code></td>
        </tr>
        <tr>
            <td>Enum keys in TitleCase</td>
            <td><code>SessionStatus::SCHEDULED</code> (not <code>SessionStatus::scheduled</code>)</td>
        </tr>
        <tr>
            <td>Curly braces always</td>
            <td>Always use <code>{}</code> even for single-line if/foreach</td>
        </tr>
    </tbody>
</table>
</div>

<h2 id="laravel">Laravel Conventions</h2>

<div style="overflow-x: auto; direction: ltr;">
<table class="help-table">
    <thead><tr><th>Rule</th><th>Correct</th><th>Wrong</th></tr></thead>
    <tbody>
        <tr>
            <td>ORM queries</td>
            <td><code>Model::query()->where(...)</code></td>
            <td><code>DB::table(...)</code> (unless raw SQL needed)</td>
        </tr>
        <tr>
            <td>Config access</td>
            <td><code>config('livekit.api_key')</code></td>
            <td><code>env('LIVEKIT_API_KEY')</code> outside config files</td>
        </tr>
        <tr>
            <td>Input validation</td>
            <td>Form Request classes (<code>app/Http/Requests/</code>)</td>
            <td><code>$request->validate()</code> in controllers</td>
        </tr>
        <tr>
            <td>Named routes</td>
            <td><code>route('sessions.show', $session)</code></td>
            <td><code>'/sessions/' . $session->id</code></td>
        </tr>
        <tr>
            <td>Eager loading</td>
            <td><code>Session::with(['teacher', 'attendances'])->get()</code></td>
            <td>Accessing relationships in a loop (N+1)</td>
        </tr>
        <tr>
            <td>Service layer</td>
            <td>Controller delegates to service, service uses model</td>
            <td>Business logic in controllers or models</td>
        </tr>
    </tbody>
</table>
</div>

<h2 id="localization">Localization — Critical Rule</h2>

<div class="help-danger">
    <i class="ri-error-warning-line help-callout-icon"></i>
    <div>
        <strong>NEVER hardcode user-facing strings.</strong> All text displayed to users must use
        the <code>__('key')</code> helper. Always add keys to BOTH language files (<code>lang/ar/*.php</code>
        and <code>lang/en/*.php</code>) when adding new UI text.
        <pre style="margin-top: 0.5rem; background: #1e293b; color: #e2e8f0; padding: 0.75rem; border-radius: 0.25rem; font-size: 0.8rem; direction: ltr; text-align: left;">
// ❌ Wrong — hardcoded Arabic string
echo 'جلسة مجدولة';

// ✅ Correct — localized
echo __('sessions.status.scheduled');
        </pre>
    </div>
</div>

<h2 id="type-checking">Type Checking — Critical Rule</h2>

<div class="help-danger">
    <i class="ri-error-warning-line help-callout-icon"></i>
    <div>
        <strong>NEVER use <code>str_contains()</code> or string comparison for model type checks.</strong>
        Always use <code>instanceof</code> or class constants.
        <pre style="margin-top: 0.5rem; background: #1e293b; color: #e2e8f0; padding: 0.75rem; border-radius: 0.25rem; font-size: 0.8rem; direction: ltr; text-align: left;">
// ❌ BAD — string matching
if (str_contains(get_class($payable), 'QuranSubscription')) { ... }

// ✅ GOOD — instanceof
if ($payable instanceof QuranSubscription) { ... }

// ✅ GOOD — class constant
if ($payable::class === QuranSubscription::class) { ... }
        </pre>
    </div>
</div>

<h2 id="timezone">Timezone Handling</h2>

<p>
    <strong>All timestamps are stored in UTC.</strong> Convert to the academy's local timezone only for display.
    The academy timezone is configured per-academy (Asia/Riyadh, Africa/Cairo, etc.).
</p>

<div style="overflow-x: auto; direction: ltr;">
<table class="help-table">
    <thead><tr><th>Use Case</th><th>Correct Code</th></tr></thead>
    <tbody>
        <tr>
            <td>Get current time for display</td>
            <td><code>AcademyContextService::nowInAcademyTimezone()</code> or helper <code>nowInAcademyTimezone()</code></td>
        </tr>
        <tr>
            <td>Convert stored UTC to display</td>
            <td><code>AcademyContextService::toAcademyTimezone($session->scheduled_at)</code> or <code>toAcademyTimezone($dt)</code></td>
        </tr>
        <tr>
            <td>Get timezone string</td>
            <td><code>AcademyContextService::getTimezone()</code> or <code>getAcademyTimezone()</code></td>
        </tr>
        <tr>
            <td>Filament DateTimePicker</td>
            <td><code>->timezone(AcademyContextService::getTimezone())</code> — ALWAYS required</td>
        </tr>
        <tr>
            <td>DB queries (compare with stored UTC)</td>
            <td><code>where('scheduled_at', '>', now())</code> — use <code>now()</code> for DB comparisons</td>
        </tr>
        <tr>
            <td>User-facing time comparisons</td>
            <td><code>$session->scheduled_at->lte(nowInAcademyTimezone())</code></td>
        </tr>
    </tbody>
</table>
</div>

<div class="help-warning">
    <i class="ri-alert-line help-callout-icon"></i>
    <div>
        <strong>Don't mix UTC and academy timezone in the same comparison.</strong>
        For DB queries comparing against stored timestamps, always use <code>now()</code> (UTC).
        For user-facing logic (can they join now?), use <code>nowInAcademyTimezone()</code>.
    </div>
</div>

<h2 id="multitenancy">Multi-Tenancy Rules</h2>

<ul>
    <li>The <code>ScopedToAcademy</code> global scope auto-filters all Eloquent queries — you rarely need to add <code>where('academy_id', ...)</code> manually.</li>
    <li>For raw queries (<code>DB::statement()</code>, complex JOINs) you MUST manually add <code>AND academy_id = ?</code>.</li>
    <li>Never disable the global scope without an explicit authorization check.</li>
    <li>Storage isolation: <code>storage/app/tenants/{academy_id}/</code> — use <code>ProvisionTenantStorage</code> command for new tenants.</li>
</ul>

<h2 id="rtl">Arabic / RTL Support</h2>

<div style="overflow-x: auto; direction: ltr;">
<table class="help-table">
    <thead><tr><th>Rule</th><th>Implementation</th></tr></thead>
    <tbody>
        <tr><td>Primary locale</td><td><code>APP_LOCALE=ar</code> — app is Arabic-first</td></tr>
        <tr><td>Tailwind RTL utilities</td><td>Use <code>rtl:</code> and <code>ltr:</code> prefixes for direction-specific styles</td></tr>
        <tr><td>Mixed content</td><td>Use <code>dir="auto"</code> for text that may be Arabic or English</td></tr>
        <tr><td>Code blocks</td><td>Always set <code>dir="ltr"</code> and <code>text-align: left</code> for code</td></tr>
        <tr><td>Font stack</td><td>Tajawal (UI), Cairo (headings), Amiri (Quran text)</td></tr>
        <tr><td>Spacing</td><td>Use <code>space-x-reverse</code> and RTL-safe gap utilities</td></tr>
    </tbody>
</table>
</div>

<h2 id="observer-pattern">Observer Pattern — Auto-Generated Codes</h2>

<p>
    Several models auto-generate unique codes using Eloquent Observers with row-level locking to prevent race conditions:
</p>

<div style="overflow-x: auto; direction: ltr;">
<table class="help-table">
    <thead><tr><th>Model</th><th>Code Format</th><th>Observer</th></tr></thead>
    <tbody>
        <tr><td><code>User</code> (admin role)</td><td><code>ADM-{academyId:02d}-{sequence:06d}</code></td><td><code>UserObserver</code></td></tr>
        <tr><td><code>StudentProfile</code></td><td><code>ST-{academyId:02d}-{sequence:06d}</code></td><td><code>StudentProfileObserver</code></td></tr>
        <tr><td><code>AcademicSession</code></td><td><code>AS-{academyId:02d}-{sequence:06d}</code></td><td>In model <code>boot()</code></td></tr>
        <tr><td><code>QuranSession</code></td><td><code>QS-{academyId:02d}-{sequence:06d}</code></td><td>In model <code>boot()</code></td></tr>
    </tbody>
</table>
</div>

<pre><code class="language-php">// Pattern used in observers — lockForUpdate prevents duplicate codes
protected static function booted(): void
{
    static::creating(function (self $model) {
        if (empty($model->session_code)) {
            DB::transaction(function () use ($model) {
                $last = static::where('academy_id', $model->academy_id)
                    ->lockForUpdate()
                    ->max('session_code_sequence');
                $model->session_code = 'AS-' . str_pad($model->academy_id, 2, '0', STR_PAD_LEFT)
                    . '-' . str_pad(($last ?? 0) + 1, 6, '0', STR_PAD_LEFT);
            });
        }
    });
}
</code></pre>

<h2 id="meetings">Meeting Integration Rule</h2>

<div class="help-danger">
    <i class="ri-error-warning-line help-callout-icon"></i>
    <div>
        <strong>NEVER create separate routes or pages for video meetings.</strong>
        Meetings are always embedded within session detail pages.
        The <code>meeting-feature-requirements.mdc</code> rules document explicitly forbids standalone meeting pages.
        Use the LiveKit JavaScript SDK directly in session Blade views.
    </div>
</div>

@endsection
