@extends('help.layouts.article', ['role' => 'developer', 'slug' => 'filament-panels'])

@section('content')

<h2 id="frontend-vs-filament">Frontend Dashboard vs. Filament</h2>

<p>
    As of the current architecture, <strong>Filament is no longer the primary interface for daily operations</strong>.
    All roles now have complete frontend dashboards; Filament panels are optional/supplementary.
</p>

<div style="overflow-x: auto; direction: ltr;">
<table class="help-table">
    <thead>
        <tr>
            <th>Interface</th>
            <th>URL</th>
            <th>Roles</th>
            <th>Purpose</th>
        </tr>
    </thead>
    <tbody>
        <tr>
            <td><strong>Management Dashboard</strong></td>
            <td><code>/manage/dashboard</code></td>
            <td>admin, supervisor, super_admin</td>
            <td>All daily educational operations</td>
        </tr>
        <tr>
            <td><strong>Teacher Frontend</strong></td>
            <td><code>/login</code> → <code>/teacher/*</code></td>
            <td>quran_teacher, academic_teacher</td>
            <td>Teacher sessions, circles, calendar</td>
        </tr>
        <tr>
            <td><strong>Student/Parent Frontend</strong></td>
            <td><code>/login</code> → <code>/profile</code></td>
            <td>student, parent</td>
            <td>Subscriptions, sessions, homework</td>
        </tr>
        <tr>
            <td><strong>Filament Panels</strong></td>
            <td><code>/admin</code>, <code>/panel</code>, etc.</td>
            <td>super_admin (primary), others (optional)</td>
            <td>Tenant creation, platform config, advanced bulk ops</td>
        </tr>
    </tbody>
</table>
</div>

<p>
    The <code>/manage/*</code> route set covers: teachers, students, parents, circles, subscriptions,
    sessions, attendance, homework, quizzes, payments, certificates, calendar, courses, reports,
    earnings, and trial sessions. See the <a href="#frontend-routes">Frontend Routes</a> section below.
</p>

<div class="help-note">
    <i class="ri-information-line help-callout-icon"></i>
    <div>
        <strong>Middleware:</strong> <code>/manage/*</code> routes use <code>auth, role:supervisor,super_admin,admin</code>
        middleware. The frontend dashboard is served by <code>app/Http/Controllers/Supervisor/</code> (23 controllers)
        backed by Blade views in <code>resources/views/supervisor/</code> (51 templates).
    </div>
</div>

<h2 id="panels-overview">Filament Panels Overview (Supplementary)</h2>

<p>
    The platform has <strong>5 Filament panels</strong>, now used primarily for platform-level management
    and advanced configuration rather than daily educational operations.
</p>

<div style="overflow-x: auto; direction: ltr;">
<table class="help-table">
    <thead>
        <tr>
            <th>Panel</th>
            <th>PHP Namespace</th>
            <th>URL Prefix</th>
            <th>Roles</th>
            <th>Resources (~)</th>
        </tr>
    </thead>
    <tbody>
        <tr>
            <td><strong>Super Admin</strong></td>
            <td><code>App\Filament\</code></td>
            <td><code>/admin</code></td>
            <td><code>super_admin</code></td>
            <td>Platform-wide management</td>
        </tr>
        <tr>
            <td><strong>Academy</strong></td>
            <td><code>App\Filament\Academy\</code></td>
            <td><code>/panel/{subdomain}</code></td>
            <td><code>admin</code>, <code>supervisor</code></td>
            <td>70+</td>
        </tr>
        <tr>
            <td><strong>Teacher (Quran)</strong></td>
            <td><code>App\Filament\Teacher\</code></td>
            <td><code>/teacher-panel</code></td>
            <td><code>quran_teacher</code></td>
            <td>10+</td>
        </tr>
        <tr>
            <td><strong>AcademicTeacher</strong></td>
            <td><code>App\Filament\AcademicTeacher\</code></td>
            <td><code>/academic-teacher-panel</code></td>
            <td><code>academic_teacher</code></td>
            <td>10+</td>
        </tr>
        <tr>
            <td><strong>Supervisor</strong></td>
            <td><code>App\Filament\Supervisor\</code></td>
            <td><code>/supervisor-panel</code></td>
            <td><code>supervisor</code></td>
            <td>—</td>
        </tr>
    </tbody>
</table>
</div>

<h2 id="panel-structure">Panel Directory Structure</h2>

<pre><code>app/Filament/Academy/
├── Pages/
│   ├── Dashboard.php               # Widget-based dashboard
│   ├── PlatformSettingsPage.php    # Platform settings (super admin)
│   └── LogViewer.php               # Log viewer page
├── Resources/
│   ├── UserResource.php            # User CRUD + invitations
│   ├── QuranSessionResource.php    # Session management
│   └── ...70+ resources...
├── Shared/                         # Cross-panel components
│   ├── Actions/                    # Shared table/form actions
│   └── Components/                 # Shared Blade components
└── Widgets/
    ├── StatsOverviewWidget.php     # Dashboard stats
    └── ...
</code></pre>

<h2 id="access-control">Panel Access Control</h2>

<p>
    Each panel checks access in the <code>canAccessPanel()</code> method on the <code>User</code> model.
    This is enforced at the framework level — no middleware needed.
</p>

<pre><code class="language-php">// app/Models/User.php
public function canAccessPanel(Panel $panel): bool
{
    return match ($panel->getId()) {
        'academy'          => $this->hasRole('admin') || $this->hasRole('supervisor'),
        'teacher'          => $this->hasRole('quran_teacher') && $this->isActive(),
        'academic-teacher' => $this->hasRole('academic_teacher') && $this->isActive(),
        'supervisor'       => $this->hasRole('supervisor'),
        default            => $this->hasRole('super_admin'),
    };
}
</code></pre>

<h2 id="pitfalls">Common Filament Pitfalls</h2>

<div class="help-danger">
    <i class="ri-error-warning-line help-callout-icon"></i>
    <div>
        <strong>Pitfall 1: Enum objects in closures — NOT strings.</strong><br>
        Filament 5 passes enum <em>objects</em> (not string values) to closure callbacks.
        Type-hint <code>mixed $state</code> and compare with <code>===</code> on the enum instance:
        <pre style="margin-top: 0.5rem; background: #1e293b; color: #e2e8f0; padding: 0.75rem; border-radius: 0.25rem; font-size: 0.8rem; direction: ltr; text-align: left;">
// ❌ Wrong — this never matches because $state is an enum object
->color(fn (string $state) => $state === 'completed' ? 'success' : 'warning')

// ✅ Correct
->color(fn (mixed $state) => $state === SessionStatus::COMPLETED ? 'success' : 'warning')
        </pre>
    </div>
</div>

<div class="help-danger">
    <i class="ri-error-warning-line help-callout-icon"></i>
    <div>
        <strong>Pitfall 2: <code>$tenantOwnershipRelationshipName = null</code> falls back to panel default.</strong><br>
        In resources where the model IS the tenant model, you MUST override <code>getEloquentQuery()</code>
        to prevent infinite self-referencing:
        <pre style="margin-top: 0.5rem; background: #1e293b; color: #e2e8f0; padding: 0.75rem; border-radius: 0.25rem; font-size: 0.8rem; direction: ltr; text-align: left;">
// ✅ Use this when resource model IS the tenant
public static function getEloquentQuery(): Builder
{
    return static::getModel()::query(); // bypass tenantOwnership scoping
}
        </pre>
    </div>
</div>

<div class="help-warning">
    <i class="ri-alert-line help-callout-icon"></i>
    <div>
        <strong>Pitfall 3: <code>AcademicTeacherProfile</code> has NO <code>is_approved</code> or <code>is_active</code> column.</strong><br>
        These fields don't exist on the profile — check activation via the user relationship:
        <code>whereHas('user', fn($q) => $q->where('active_status', true))</code>
    </div>
</div>

<div class="help-warning">
    <i class="ri-alert-line help-callout-icon"></i>
    <div>
        <strong>Pitfall 4: After every deploy, run <code>filament:cache-components</code>.</strong><br>
        Stale Livewire component cache causes mysterious 500 errors after deployment.
        Always include this in the deployment checklist.
    </div>
</div>

<div class="help-note">
    <i class="ri-information-line help-callout-icon"></i>
    <div>
        <strong>Pitfall 5: <code>KeyValue</code> form component does NOT support <code>->placeholder()</code>.</strong><br>
        Remove <code>->placeholder()</code> from any <code>KeyValue::make()</code> usage — it silently fails.
    </div>
</div>

<div class="help-note">
    <i class="ri-information-line help-callout-icon"></i>
    <div>
        <strong>Pitfall 6: <code>deferFilters(false)</code> must be added to <code>table()</code> methods.</strong><br>
        Every resource's <code>table()</code> method must call <code>->deferFilters(false)</code>. New resources must include it.
    </div>
</div>

<h2 id="resource-conventions">Resource Conventions</h2>

<div style="overflow-x: auto; direction: ltr;">
<table class="help-table">
    <thead><tr><th>Convention</th><th>Rule</th></tr></thead>
    <tbody>
        <tr><td>Generating resources</td><td><code>php artisan make:filament-resource ModelName</code></td></tr>
        <tr><td>Table actions</td><td>Define in <code>actions()</code> method using Filament action builders</td></tr>
        <tr><td>Table columns</td><td>Use Filament column builders — never raw HTML in table</td></tr>
        <tr><td>Forms</td><td>Use Filament form builders for all input — never custom HTML forms</td></tr>
        <tr><td>Eager loading</td><td>ALWAYS eager load in <code>getEloquentQuery()</code> using <code>with([...])</code></td></tr>
        <tr><td>N+1 prevention</td><td>Every table column accessing a relationship must be in <code>with()</code></td></tr>
        <tr><td>Date filtering</td><td>Use <code>->deferFilters(false)</code> on all <code>table()</code> calls</td></tr>
        <tr><td>Timezone in forms</td><td>DateTimePicker must use <code>->timezone(AcademyContextService::getTimezone())</code></td></tr>
        <tr><td>Arabic labels</td><td>All labels must use <code>__('key')</code> — never hardcoded Arabic text</td></tr>
        <tr><td>Infolist view pages</td><td>Return the infolist schema — not the infolist itself (Filament 5 pattern)</td></tr>
    </tbody>
</table>
</div>

<h2 id="frontend-routes">Frontend Management Dashboard Routes</h2>

<p>The <code>/manage/*</code> route prefix covers all daily management operations:</p>

<div style="overflow-x: auto; direction: ltr;">
<table class="help-table">
    <thead>
        <tr>
            <th>Route</th>
            <th>Purpose</th>
        </tr>
    </thead>
    <tbody>
        <tr><td><code>/manage/dashboard</code></td><td>Analytics, stats, today's sessions overview</td></tr>
        <tr><td><code>/manage/teachers</code></td><td>Teacher management (CRUD, toggle status, reset password)</td></tr>
        <tr><td><code>/manage/students</code></td><td>Student management (CRUD, toggle)</td></tr>
        <tr><td><code>/manage/parents</code></td><td>Parent management (CRUD)</td></tr>
        <tr><td><code>/manage/supervisors</code></td><td>Supervisor management (admin-only)</td></tr>
        <tr><td><code>/manage/individual-circles</code></td><td>Quran individual circle management</td></tr>
        <tr><td><code>/manage/group-circles</code></td><td>Quran group circle management</td></tr>
        <tr><td><code>/manage/academic-lessons</code></td><td>Academic lesson/subscription monitoring</td></tr>
        <tr><td><code>/manage/interactive-courses</code></td><td>Interactive course management</td></tr>
        <tr><td><code>/manage/sessions</code></td><td>Unified session management (Quran + Academic + Interactive)</td></tr>
        <tr><td><code>/manage/subscriptions</code></td><td>Subscription lifecycle management</td></tr>
        <tr><td><code>/manage/payments</code></td><td>Payment management (admin-only)</td></tr>
        <tr><td><code>/manage/homework</code></td><td>Homework oversight and grading</td></tr>
        <tr><td><code>/manage/attendance</code></td><td>Attendance records</td></tr>
        <tr><td><code>/manage/quizzes</code></td><td>Quiz management</td></tr>
        <tr><td><code>/manage/certificates</code></td><td>Certificate issuance and management</td></tr>
        <tr><td><code>/manage/calendar</code></td><td>Calendar, scheduling, homework assignment</td></tr>
        <tr><td><code>/manage/teacher-earnings</code></td><td>Earnings tracking and disputes</td></tr>
        <tr><td><code>/manage/session-reports</code></td><td>Session report viewing</td></tr>
        <tr><td><code>/manage/trial-sessions</code></td><td>Trial session request management</td></tr>
    </tbody>
</table>
</div>

<h2 id="artisan">Useful Filament Artisan Commands</h2>

<pre><code class="language-bash"># Create a new resource
php artisan make:filament-resource ModelName

# Create a custom page
php artisan make:filament-page PageName

# Cache all Filament components (run after every deploy!)
php artisan filament:cache-components

# Publish Filament assets
php artisan filament:assets

# Upgrade Filament
php artisan filament:upgrade
</code></pre>

@endsection
