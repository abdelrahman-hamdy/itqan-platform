@extends('help.layouts.article', ['role' => 'developer', 'slug' => 'platform-architecture'])

@section('content')

<h2 id="overview">What Is the Itqan Platform?</h2>

<p>
    Itqan is a <strong>multi-tenant SaaS LMS</strong> (Learning Management System) for Quran memorization and academic tutoring.
    Each academy gets its own subdomain, isolated data, and branded experience — all running on a single shared codebase and database.
</p>

<div class="help-mermaid">
<pre class="mermaid">
graph TB
    subgraph "Browser / Mobile App"
        B[Browser - Blade + Livewire + Alpine.js]
        M[Flutter Mobile App]
    end
    subgraph "Application Layer"
        V[Vite Build - TailwindCSS 4.2 + JS bundles]
        F[Filament 5 Admin Panels]
        L[Livewire 4 Components]
        A[Alpine.js Interactivity]
    end
    subgraph "Business Logic"
        S[100+ Service Classes]
        J[Queue Jobs - Horizon]
        C[Console Commands - 60+]
    end
    subgraph "Data Layer"
        DB[(MySQL 8 - Single DB Multi-Tenant)]
        R[(Redis 7 - Cache / Queue / Session)]
    end
    subgraph "External"
        LK[LiveKit Video Server]
        RV[Laravel Reverb WebSocket]
        PG[Payment Gateways]
    end
    B --> V
    M --> API[REST API /api/v1/]
    F --> S
    L --> S
    S --> DB
    S --> R
    S --> LK
    S --> PG
    J --> R
    RV --> R
</pre>
</div>

<h2 id="stack">Technology Stack</h2>

<div style="overflow-x: auto; direction: ltr;">
<table class="help-table">
    <thead>
        <tr>
            <th>Layer</th>
            <th>Technology</th>
            <th>Version</th>
            <th>Purpose</th>
        </tr>
    </thead>
    <tbody>
        <tr><td>Backend Framework</td><td>Laravel</td><td>12.x</td><td>HTTP routing, ORM, queues, events</td></tr>
        <tr><td>Language</td><td>PHP</td><td>8.4.x (prod)</td><td>Server-side language</td></tr>
        <tr><td>Admin UI</td><td>Filament</td><td>5.2.x</td><td>4 role-based admin panels</td></tr>
        <tr><td>Reactive UI</td><td>Livewire</td><td>4.1.x</td><td>Server-driven components</td></tr>
        <tr><td>JS Framework</td><td>Alpine.js</td><td>3.15.x</td><td>Lightweight interactivity</td></tr>
        <tr><td>CSS Framework</td><td>TailwindCSS</td><td>4.2.x</td><td>Utility-first CSS with RTL support</td></tr>
        <tr><td>Build Tool</td><td>Vite</td><td>7.0.x</td><td>Asset bundling (dev + prod)</td></tr>
        <tr><td>Database</td><td>MySQL</td><td>8.x</td><td>Primary data store</td></tr>
        <tr><td>Cache / Queue / Session</td><td>Redis</td><td>7.x</td><td>4 separate Redis DBs</td></tr>
        <tr><td>WebSocket</td><td>Laravel Reverb</td><td>—</td><td>Real-time events (port 8085)</td></tr>
        <tr><td>Video Conferencing</td><td>LiveKit</td><td>2.15.x (SDK)</td><td>Session video meetings</td></tr>
        <tr><td>Chat System</td><td>WireChat</td><td>0.5.0</td><td>Supervised teacher-student chat</td></tr>
        <tr><td>Multi-Tenancy</td><td>Spatie Multitenancy</td><td>—</td><td>Single-DB tenant isolation</td></tr>
        <tr><td>Admin Media</td><td>Spatie Media Library</td><td>—</td><td>File uploads and storage</td></tr>
        <tr><td>Error Tracking</td><td>Sentry</td><td>—</td><td>Production error monitoring</td></tr>
        <tr><td>Queue Dashboard</td><td>Laravel Horizon</td><td>—</td><td>Redis queue management</td></tr>
        <tr><td>E2E Testing</td><td>Playwright</td><td>1.58.x</td><td>Browser-based test automation</td></tr>
        <tr><td>Mobile App</td><td>Flutter</td><td>—</td><td>iOS + Android student/teacher app</td></tr>
    </tbody>
</table>
</div>

<h2 id="multitenancy">Multi-Tenancy Architecture</h2>

<p>
    Itqan uses <strong>single-database multi-tenancy</strong>. Every tenant-scoped model carries an
    <code>academy_id</code> column, and a global Eloquent scope automatically filters all queries
    so each academy only sees its own data.
</p>

<div class="help-mermaid">
<pre class="mermaid">
graph LR
    subgraph "DNS / Routing"
        REQ[HTTP Request<br/>alnoor.itqanway.com]
        MW[SubdomainMiddleware<br/>Resolves Academy model]
    end
    subgraph "Academy Model — the Tenant"
        AC[Academy<br/>id, subdomain, currency, timezone]
    end
    subgraph "Global Scopes"
        GS1[ScopedToAcademy trait<br/>WHERE academy_id = ?]
        GS2[ScopedToAcademyForWeb<br/>Web-specific scope]
    end
    subgraph "Isolated Data"
        D1[Sessions]
        D2[Users]
        D3[Subscriptions]
        D4[Storage: tenants/{id}/]
    end
    REQ --> MW --> AC --> GS1
    GS1 --> D1 & D2 & D3 & D4
</pre>
</div>

<div class="help-note">
    <i class="ri-information-line help-callout-icon"></i>
    <div>
        <strong>Default tenant:</strong> The root domain <code>itqanway.com</code> resolves to the
        <code>itqan-academy</code> subdomain as the default tenant. Raw queries and complex joins
        must manually apply <code>WHERE academy_id = ?</code> — the global scope only activates
        on Eloquent model queries.
    </div>
</div>

<h2 id="panels">Filament Admin Panels</h2>

<p>
    There are <strong>4 Filament panels</strong>, each serving a distinct user role with its own
    navigation, resources, and access control enforced via <code>canAccessPanel()</code> on the User model.
</p>

<div style="overflow-x: auto; direction: ltr;">
<table class="help-table">
    <thead>
        <tr>
            <th>Panel</th>
            <th>Directory</th>
            <th>URL Prefix</th>
            <th>Roles</th>
            <th>Resources</th>
        </tr>
    </thead>
    <tbody>
        <tr>
            <td><strong>Academy</strong></td>
            <td><code>app/Filament/Academy/</code></td>
            <td><code>/panel/{subdomain}</code></td>
            <td>admin, supervisor</td>
            <td>70+</td>
        </tr>
        <tr>
            <td><strong>Teacher</strong> (Quran)</td>
            <td><code>app/Filament/Teacher/</code></td>
            <td><code>/teacher-panel</code></td>
            <td>quran_teacher</td>
            <td>10+</td>
        </tr>
        <tr>
            <td><strong>AcademicTeacher</strong></td>
            <td><code>app/Filament/AcademicTeacher/</code></td>
            <td><code>/academic-teacher-panel</code></td>
            <td>academic_teacher</td>
            <td>10+</td>
        </tr>
        <tr>
            <td><strong>Supervisor</strong></td>
            <td><code>app/Filament/Supervisor/</code></td>
            <td><code>/supervisor-panel</code></td>
            <td>supervisor</td>
            <td>—</td>
        </tr>
        <tr>
            <td><strong>Super Admin</strong></td>
            <td><code>app/Filament/ (root)</code></td>
            <td><code>/admin</code></td>
            <td>super_admin</td>
            <td>Platform-wide</td>
        </tr>
    </tbody>
</table>
</div>

<h2 id="request-lifecycle">Request Lifecycle</h2>

<div class="help-mermaid">
<pre class="mermaid">
sequenceDiagram
    participant Browser
    participant Nginx
    participant Laravel
    participant Middleware
    participant Controller
    participant Service
    participant Model
    participant MySQL

    Browser->>Nginx: GET alnoor.itqanway.com/sessions
    Nginx->>Laravel: Forward to public/index.php
    Laravel->>Middleware: Run middleware stack
    Middleware->>Middleware: SubdomainTenant → set Academy
    Middleware->>Middleware: Auth → verify user
    Middleware->>Controller: Route dispatch
    Controller->>Service: Delegate business logic
    Service->>Model: Query with global scope
    Model->>MySQL: SELECT ... WHERE academy_id = 5
    MySQL-->>Model: Results
    Model-->>Service: Eloquent collection
    Service-->>Controller: Processed data
    Controller-->>Browser: Blade view / JSON
</pre>
</div>

<h2 id="directory">Key Directory Structure</h2>

<pre><code>itqan-platform/
├── app/
│   ├── Console/Commands/       # 60+ Artisan commands
│   ├── Enums/                  # 55+ type-safe enums
│   ├── Filament/               # 4 admin panels
│   │   ├── Academy/            # Main admin panel
│   │   ├── Teacher/            # Quran teacher panel
│   │   ├── AcademicTeacher/    # Academic teacher panel
│   │   ├── Supervisor/         # Supervisor panel
│   │   └── Shared/             # Cross-panel components
│   ├── Http/
│   │   ├── Controllers/Api/    # REST API controllers (v1)
│   │   └── Requests/           # Form Request validation
│   ├── Jobs/                   # Queue-dispatched jobs
│   ├── Livewire/               # Livewire page components
│   ├── Models/
│   │   └── Traits/             # 22 model traits
│   ├── Observers/              # Eloquent model observers
│   ├── Policies/               # 20 authorization policies
│   ├── Providers/              # Service providers
│   └── Services/               # 100+ business logic services
├── config/                     # 40 config files
├── database/
│   ├── migrations/             # 111 migration files (~90 tables)
│   └── seeders/
├── resources/
│   ├── css/app.css             # TailwindCSS entry
│   ├── js/app.js               # Livewire + Alpine entry
│   └── views/                  # 981 Blade templates
├── routes/
│   ├── web.php                 # 77KB — includes sub-files
│   ├── web/                    # Per-feature route files
│   ├── api.php                 # REST API routes
│   ├── channels.php            # Broadcasting channels
│   └── console.php             # 26 scheduled tasks
└── deployment/
    └── supervisor/             # Supervisor process configs
</code></pre>

@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/mermaid@11/dist/mermaid.min.js"></script>
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
