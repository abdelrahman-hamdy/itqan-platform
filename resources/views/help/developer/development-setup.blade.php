@extends('help.layouts.article', ['role' => 'developer', 'slug' => 'development-setup'])

@section('content')

<h2 id="prerequisites">Prerequisites</h2>

<div style="overflow-x: auto; direction: ltr;">
<table class="help-table">
    <thead><tr><th>Tool</th><th>Minimum Version</th><th>Notes</th></tr></thead>
    <tbody>
        <tr><td>PHP</td><td>8.2+</td><td>Production runs 8.4.16</td></tr>
        <tr><td>MySQL</td><td>8.x</td><td>DB: <code>itqan_platform</code> on 127.0.0.1:3306</td></tr>
        <tr><td>Redis</td><td>7.x</td><td>Required for cache, queue, sessions</td></tr>
        <tr><td>Node.js</td><td>20+</td><td>Required for Vite and npm</td></tr>
        <tr><td>Composer</td><td>2.7+</td><td>PHP dependency manager</td></tr>
        <tr><td>Git</td><td>Any</td><td>Version control</td></tr>
    </tbody>
</table>
</div>

<h2 id="setup-steps">Local Setup Steps</h2>

<div class="help-step">
    <div class="help-step-number">1</div>
    <div class="help-step-content">
        <h3>Clone repository &amp; install dependencies</h3>
        <pre><code class="language-bash">git clone https://github.com/abdelrahman-hamdy/itqan-platform.git
cd itqan-platform
composer install
npm install</code></pre>
    </div>
</div>

<div class="help-step">
    <div class="help-step-number">2</div>
    <div class="help-step-content">
        <h3>Configure environment</h3>
        <pre><code class="language-bash">cp .env.example .env
php artisan key:generate</code></pre>
        <p>Edit <code>.env</code> with your local settings:</p>
        <pre><code class="language-ini">APP_URL=http://itqan-platform.test
APP_LOCALE=ar

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=itqan_platform
DB_USERNAME=root
DB_PASSWORD=

REDIS_HOST=127.0.0.1
REDIS_PORT=6379

QUEUE_CONNECTION=redis
SESSION_DRIVER=database
CACHE_STORE=redis

# LiveKit (optional for local, use test server)
LIVEKIT_SERVER_URL=wss://your-livekit-server.com
LIVEKIT_API_KEY=your-key
LIVEKIT_API_SECRET=your-secret</code></pre>
    </div>
</div>

<div class="help-step">
    <div class="help-step-number">3</div>
    <div class="help-step-content">
        <h3>Create database &amp; run migrations</h3>
        <pre><code class="language-bash"># Create the database in MySQL first:
mysql -u root -e "CREATE DATABASE itqan_platform CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"

# Run migrations with seed data
php artisan migrate --seed</code></pre>
    </div>
</div>

<div class="help-step">
    <div class="help-step-number">4</div>
    <div class="help-step-content">
        <h3>Configure local multi-tenancy (hosts file)</h3>
        <p>Add subdomains to your <code>/etc/hosts</code> file:</p>
        <pre><code class="language-bash">127.0.0.1 itqan-platform.test
127.0.0.1 itqan-academy.itqan-platform.test
127.0.0.1 e2e-test.itqan-platform.test</code></pre>
        <p>Or use Laravel Valet / Herd which handles this automatically.</p>
    </div>
</div>

<div class="help-step">
    <div class="help-step-number">5</div>
    <div class="help-step-content">
        <h3>Start the development environment</h3>
        <pre><code class="language-bash">composer dev</code></pre>
        <p>This single command starts all services concurrently:</p>
        <ul>
            <li><strong>Laravel dev server</strong> — <code>php artisan serve</code></li>
            <li><strong>Queue worker</strong> — <code>php artisan queue:listen</code></li>
            <li><strong>Real-time logs</strong> — <code>php artisan pail</code></li>
            <li><strong>Vite dev server</strong> — <code>npm run dev</code> (port 5173)</li>
        </ul>
    </div>
</div>

<h2 id="seeding">Database Seeding</h2>

<pre><code class="language-bash"># Fresh start with all seed data
php artisan migrate:fresh --seed

# Run seeders without resetting schema
php artisan db:seed

# Create E2E test data (for Playwright testing)
php artisan db:seed --class=E2ETestDataSeeder --force
</code></pre>

<p>The default seeder creates:</p>
<ul>
    <li>A super admin user</li>
    <li>A sample academy (<code>itqan-academy</code> subdomain)</li>
    <li>Sample Quran teachers, academic teachers, students</li>
    <li>Sample sessions, subscriptions, and packages</li>
</ul>

<h2 id="individual-services">Individual Services (when needed)</h2>

<pre><code class="language-bash">php artisan serve                    # Laravel dev server only
npm run dev                          # Vite dev server only (port 5173)
php artisan queue:listen --tries=1   # Queue worker only
php artisan pail                     # Real-time log tailing
php artisan reverb:start             # WebSocket server (if testing real-time)
</code></pre>

<h2 id="testing">Testing</h2>

<h3>Unit &amp; Feature Tests (Pest)</h3>
<pre><code class="language-bash">composer test                                    # Full test suite
php artisan test tests/Feature/ExampleTest.php   # Single file
php artisan test --filter=testName               # Filter by name
</code></pre>

<h3>E2E Tests (Playwright)</h3>
<pre><code class="language-bash"># Install Playwright browsers
npx playwright install

# Run E2E test suite
npx playwright test

# E2E test accounts (on e2e-test subdomain)
# URL: http://e2e-test.itqan-platform.test
# Credentials: ask the team lead — do not hardcode here
</code></pre>

<h2 id="diagnostic-commands">Diagnostic Commands</h2>

<pre><code class="language-bash"># Discover all API endpoints with methods and middleware
php artisan app:scan-api

# Audit data integrity (orphaned records, counter mismatches)
php artisan app:audit-data

# Check all registered routes
php artisan app:check-all-routes

# Test exchange rate fetching
php artisan app:test-exchange-rate

# Check cron job execution status
php artisan app:cron-job-status
</code></pre>

<h2 id="code-quality">Code Quality</h2>

<pre><code class="language-bash"># Format all changed files with Laravel Pint
vendor/bin/pint --dirty

# Format specific file
vendor/bin/pint app/Services/MyService.php

# Check formatting without modifying
vendor/bin/pint --test
</code></pre>

<h2 id="livekit-test">LiveKit Test Screen (Debug Mode)</h2>

<p>
    A debug LiveKit test screen is available in the Flutter mobile app when <code>kDebugMode == true</code>.
    Access it from the Profile screen → "اختبار LiveKit [DEBUG]" button.
    Alternatively navigate to route <code>/debug/livekit-test</code>.
</p>

<div class="help-note">
    <i class="ri-information-line help-callout-icon"></i>
    <div>
        The test screen is only registered in the router when <code>kDebugMode == true</code>.
        It connects to the LiveKit server configured in <code>environment.dart</code>:
        dev uses <code>itqan-platform.com</code>, prod uses <code>itqanway.com</code>.
    </div>
</div>

@endsection
