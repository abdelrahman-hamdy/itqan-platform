@extends('help.layouts.article', ['role' => 'developer', 'slug' => 'deployment-guide'])

@section('content')

<h2 id="production">Production Environment</h2>

<div style="overflow-x: auto; direction: ltr;">
<table class="help-table">
    <thead><tr><th>Item</th><th>Value</th></tr></thead>
    <tbody>
        <tr><td>Domain</td><td><code>itqanway.com</code></td></tr>
        <tr><td>Server user</td><td><code>deploy</code> (ask team lead for access)</td></tr>
        <tr><td>App directory</td><td><code>/var/www/itqan-platform</code></td></tr>
        <tr><td>PHP version</td><td>8.4.16</td></tr>
        <tr><td>App environment</td><td>APP_DEBUG=false, APP_ENV=production</td></tr>
        <tr><td>Queue driver</td><td>Redis</td></tr>
        <tr><td>Session driver</td><td>Redis</td></tr>
        <tr><td>Cache driver</td><td>Redis</td></tr>
    </tbody>
</table>
</div>

<p>SSH access — use your own credentials (ask the team lead for the server password):</p>
<pre><code class="language-bash">ssh deploy@itqanway.com
</code></pre>

<h2 id="deployment-flow">Deployment Flow</h2>

<div class="help-mermaid">
<pre class="mermaid">
graph TD
    A[git push to GitHub] --> B[SSH into server]
    B --> C[git pull origin main]
    C --> D[composer install --no-dev --optimize-autoloader]
    D --> E[php artisan migrate --force]
    E --> F[php artisan config:cache]
    F --> F2[php artisan route:cache]
    F2 --> F3[php artisan event:cache]
    F3 --> F4[php artisan view:cache]
    F4 --> F5[php artisan icons:cache]
    F5 --> G[php artisan filament:cache-components]
    G --> G2[php artisan filament:assets]
    G2 --> H[npm run build]
    H --> I[php artisan horizon:terminate]
    I --> J[supervisorctl restart all]
    J --> K[✅ Deployment Complete]

    style H fill:#fef9c3,stroke:#ca8a04
    style G fill:#dcfce7,stroke:#16a34a
</pre>
</div>

<h2 id="checklist">Full Deployment Checklist</h2>

<p>Run these commands IN ORDER on the production server:</p>

<pre><code class="language-bash"># 1. Pull latest code
cd /var/www/itqan-platform
git pull origin main

# 2. Install PHP dependencies (no dev packages, optimized autoloader)
composer install --no-dev --optimize-autoloader

# 3. Run database migrations
php artisan migrate --force

# 4. Cache all framework components
php artisan config:cache
php artisan route:cache
php artisan event:cache
php artisan view:cache
php artisan icons:cache

# 5. Cache Filament components (CRITICAL — prevents Livewire 500s)
php artisan filament:cache-components
php artisan filament:assets

# 6. Build frontend assets (MUST run ON server, not locally!)
npm run build

# 7. Restart queue workers gracefully
php artisan horizon:terminate
# Horizon will auto-restart via supervisor

# 8. Optionally clear OPcache
php artisan opcache:clear  # if package installed
</code></pre>

<div class="help-danger">
    <i class="ri-error-warning-line help-callout-icon"></i>
    <div>
        <strong>NEVER do these on production:</strong>
        <ul style="margin-top: 0.5rem; margin-bottom: 0;">
            <li><code>git add -A</code> — always stage specific files</li>
            <li><code>git checkout -- .</code> — wipes build artifacts (npm run build output)</li>
            <li>Build Vite assets locally and push — always build ON the server</li>
            <li><code>php artisan migrate:fresh</code> — destroys all data</li>
        </ul>
    </div>
</div>

<h2 id="supervisor">Supervisor Process Configuration</h2>

<p>
    Three supervisor programs manage background processes.
    Config files are in <code>deployment/supervisor/</code>.
</p>

<div style="overflow-x: auto; direction: ltr;">
<table class="help-table">
    <thead>
        <tr>
            <th>Program</th>
            <th>Command</th>
            <th>Processes</th>
            <th>Restarts</th>
        </tr>
    </thead>
    <tbody>
        <tr>
            <td><code>itqan-worker</code></td>
            <td><code>php artisan queue:work redis --sleep=3 --tries=3 --max-time=3600</code></td>
            <td>2</td>
            <td>Always</td>
        </tr>
        <tr>
            <td><code>itqan-reverb</code></td>
            <td><code>php artisan reverb:start --host=0.0.0.0 --port=8085</code></td>
            <td>1</td>
            <td>Always</td>
        </tr>
        <tr>
            <td><code>itqan-scheduler</code></td>
            <td><code>php artisan schedule:work</code></td>
            <td>1</td>
            <td>Always</td>
        </tr>
    </tbody>
</table>
</div>

<pre><code class="language-bash"># Supervisor management commands
supervisorctl reread          # Reload config files
supervisorctl update          # Apply config changes
supervisorctl restart all     # Restart all programs
supervisorctl status          # Check running status
supervisorctl tail itqan-reverb stdout   # View Reverb logs
</code></pre>

<h2 id="horizon">Laravel Horizon — Queue Dashboard</h2>

<p>
    Horizon manages Redis queue workers with auto-scaling. Access the dashboard at
    <code>/horizon</code> (super_admin only).
</p>

<div style="overflow-x: auto; direction: ltr;">
<table class="help-table">
    <thead>
        <tr>
            <th>Supervisor</th>
            <th>Queues</th>
            <th>Max Processes (prod)</th>
            <th>Memory</th>
        </tr>
    </thead>
    <tbody>
        <tr>
            <td><code>supervisor-default</code></td>
            <td><code>default</code>, <code>messages</code></td>
            <td>10</td>
            <td>128MB</td>
        </tr>
        <tr>
            <td><code>supervisor-notifications</code></td>
            <td><code>notifications</code></td>
            <td>5</td>
            <td>64MB</td>
        </tr>
        <tr>
            <td><code>supervisor-meetings</code></td>
            <td><code>meetings</code>, <code>attendance</code></td>
            <td>5</td>
            <td>128MB</td>
        </tr>
    </tbody>
</table>
</div>

<div class="help-warning">
    <i class="ri-alert-line help-callout-icon"></i>
    <div>
        <strong><code>messages</code> queue must be in supervisor-default!</strong>
        WireChat's <code>MessageCreated</code> event dispatches to the <code>messages</code> queue.
        If this queue isn't configured in Horizon, chat messages won't be delivered in real-time.
    </div>
</div>

<h2 id="redis">Redis Database Separation</h2>

<div style="overflow-x: auto; direction: ltr;">
<table class="help-table">
    <thead><tr><th>Redis DB</th><th>Purpose</th><th>Config Key</th></tr></thead>
    <tbody>
        <tr><td><span class="help-badge help-badge-blue">DB 0</span></td><td>Default / Horizon data</td><td><code>REDIS_DB=0</code></td></tr>
        <tr><td><span class="help-badge help-badge-green">DB 1</span></td><td>Application cache</td><td><code>REDIS_CACHE_DB=1</code></td></tr>
        <tr><td><span class="help-badge help-badge-amber">DB 2</span></td><td>Queue jobs</td><td><code>REDIS_QUEUE_DB=2</code></td></tr>
        <tr><td><span class="help-badge help-badge-purple">DB 3</span></td><td>PHP sessions</td><td><code>REDIS_SESSION_DB=3</code></td></tr>
    </tbody>
</table>
</div>

<h2 id="scheduled-tasks">Scheduled Tasks (26 commands)</h2>

<p>All scheduled via <code>routes/console.php</code>. The scheduler runs via <code>itqan-scheduler</code> supervisor.</p>

<div style="overflow-x: auto; direction: ltr;">
<table class="help-table">
    <thead>
        <tr>
            <th>Command</th>
            <th>Schedule</th>
            <th>Purpose</th>
        </tr>
    </thead>
    <tbody>
        <tr><td><code>update-session-statuses</code></td><td>Every minute</td><td>scheduled → live → completed transitions</td></tr>
        <tr><td><code>create-scheduled-meetings</code></td><td>Every minute</td><td>Create LiveKit rooms for going-live sessions</td></tr>
        <tr><td><code>stop-expired-recordings</code></td><td>Every minute</td><td>Stop recordings at session end time</td></tr>
        <tr><td><code>manage-session-meetings</code></td><td>Every 3-5 min</td><td>Comprehensive Quran session meeting ops</td></tr>
        <tr><td><code>manage-academic-session-meetings</code></td><td>Every 3-5 min</td><td>Comprehensive academic session meeting ops</td></tr>
        <tr><td><code>CalculateSessionAttendance</code> (job)</td><td>Every 5 min (prod)</td><td>Final attendance calculation from events</td></tr>
        <tr><td><code>ReconcileOrphanedAttendanceEvents</code> (job)</td><td>Hourly</td><td>Close missed webhook attendance events</td></tr>
        <tr><td><code>expire-grace-period-subscriptions</code> (job)</td><td>Hourly</td><td>Suspend after grace period expires</td></tr>
        <tr><td><code>cleanup-expired-meetings</code></td><td>Every 10 min (prod)</td><td>End expired meetings, cleanup rooms</td></tr>
        <tr><td><code>session-meeting-maintenance</code></td><td>Hourly (00-06 UTC)</td><td>Off-hours maintenance</td></tr>
        <tr><td><code>send-trial-reminders</code></td><td>Hourly</td><td>1-hour-before trial session reminders</td></tr>
        <tr><td><code>send-quiz-deadline-reminders</code></td><td>Every 30 min</td><td>24h/1h before quiz deadline</td></tr>
        <tr><td><code>send-missed-notifications</code></td><td>Every 15 min</td><td>Catch webhook-missed payment notifications</td></tr>
        <tr><td><code>process-subscription-renewals</code></td><td>Daily 06:00 UTC</td><td>Auto-renew eligible subscriptions</td></tr>
        <tr><td><code>expire-pending-payments</code></td><td>Daily 01:00 UTC</td><td>Expire unpaid payments older than 24h</td></tr>
        <tr><td><code>send-renewal-reminders</code></td><td>Daily 09:00</td><td>7/3-day renewal reminder notifications</td></tr>
        <tr><td><code>check-expiring-subscriptions</code></td><td>Daily 09:00</td><td>7/3/1-day expiry warning notifications</td></tr>
        <tr><td><code>notify-grace-expiring</code></td><td>Daily 09:00</td><td>Grace period expiry warnings</td></tr>
        <tr><td><code>refresh-exchange-rates</code></td><td>Daily 06:30</td><td>Fetch latest SAR→EGP exchange rates</td></tr>
        <tr><td><code>validate-data-integrity</code></td><td>Daily 03:00</td><td>Report data inconsistencies (no auto-fix)</td></tr>
        <tr><td><code>cleanup-soft-deleted-data</code></td><td>Weekly Sunday 02:00</td><td>Permanently delete old soft-deleted records</td></tr>
        <tr><td><code>calculate-missed-earnings</code></td><td>Weekly Monday 04:00</td><td>Backup teacher earnings calculation</td></tr>
        <tr><td><code>RunHealthChecksCommand</code></td><td>Every 5 min</td><td>Spatie Health checks (DB, Redis, queue, etc.)</td></tr>
        <tr><td><code>ScheduleCheckHeartbeatCommand</code></td><td>Every minute</td><td>Scheduler heartbeat monitoring</td></tr>
    </tbody>
</table>
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
