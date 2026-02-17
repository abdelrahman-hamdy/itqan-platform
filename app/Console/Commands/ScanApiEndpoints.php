<?php

namespace App\Console\Commands;

use Illuminate\Http\Request;
use App\Models\Academy;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Route;
use Throwable;

class ScanApiEndpoints extends Command
{
    protected $signature = 'app:scan-api
                            {--role=all : Role to test (student, quran_teacher, academic_teacher, parent, supervisor, admin, super_admin, all)}
                            {--subdomain=e2e-test : Academy subdomain to use}
                            {--show-all : Show successful routes too}
                            {--with-params : Try to test routes with parameters using ID=1}';

    protected $description = 'Scan all API endpoints for 500 errors by making real HTTP requests';

    protected array $results = [];

    protected array $roleEmails = [
        'student' => 'e2e-student@itqan.com',
        'quran_teacher' => 'e2e-teacher@itqan.com',
        'academic_teacher' => 'e2e-academic@itqan.com',
        'parent' => 'e2e-parent@itqan.com',
        'supervisor' => 'e2e-supervisor@itqan.com',
        'admin' => 'e2e-admin@itqan.com',
        'super_admin' => 'admin@itqan.com',
    ];

    public function handle(): int
    {
        $subdomain = $this->option('subdomain');
        $role = $this->option('role');

        $this->newLine();
        $this->info('API ENDPOINT SCANNER');
        $this->info('====================');
        $this->newLine();

        // Resolve academy
        $academy = Academy::where('subdomain', $subdomain)->first();
        if (! $academy) {
            $this->error("Academy with subdomain '{$subdomain}' not found.");

            return 1;
        }
        $this->info("Academy: {$academy->name} (subdomain: {$subdomain})");

        // Determine roles to test
        $rolesToTest = $role === 'all'
            ? array_keys($this->roleEmails)
            : [$role];

        // Get API routes
        $routes = $this->getApiRoutes();
        $this->info("Found {$routes->count()} testable API GET endpoints");
        $this->newLine();

        foreach ($rolesToTest as $testRole) {
            $this->scanAsRole($testRole, $academy, $routes);
        }

        // Display summary
        $this->displaySummary();

        // Save report
        $this->saveReport($subdomain);

        $totalErrors = collect($this->results)->where('status', '>=', 500)->count()
            + collect($this->results)->where('status', 'EXCEPTION')->count();

        return $totalErrors > 0 ? 1 : 0;
    }

    protected function getApiRoutes()
    {
        return collect(Route::getRoutes())->filter(function ($route) {
            // Only GET routes
            if (! in_array('GET', $route->methods())) {
                return false;
            }

            $uri = $route->uri();

            // Only API routes
            if (! str_starts_with($uri, 'api/')) {
                return false;
            }

            // Skip routes with required parameters unless --with-params
            if (! $this->option('with-params') && preg_match('/\{[^?}]+\}/', $uri)) {
                return false;
            }

            // Skip system/internal routes
            $skipPrefixes = ['api/sanctum', 'api/broadcasting'];
            foreach ($skipPrefixes as $prefix) {
                if (str_starts_with($uri, $prefix)) {
                    return false;
                }
            }

            return true;
        })->values();
    }

    protected function scanAsRole(string $role, Academy $academy, $routes): void
    {
        $email = $this->roleEmails[$role] ?? null;
        if (! $email) {
            $this->warn("Unknown role: {$role}");

            return;
        }

        $user = User::where('email', $email)->first();
        if (! $user) {
            $this->warn("User not found: {$email} - skipping role {$role}");

            return;
        }

        // Create Sanctum token
        $token = $user->createToken('api-scanner')->plainTextToken;

        $this->info("Testing as: {$role} ({$user->email})");

        $bar = $this->output->createProgressBar($routes->count());
        $bar->setFormat(' %current%/%max% [%bar%] %percent:3s%% -- %message%');
        $bar->setMessage('Starting...');
        $bar->start();

        foreach ($routes as $route) {
            $uri = $this->buildUri($route);
            $bar->setMessage($uri);

            $this->testEndpoint($uri, $token, $academy->subdomain, $role, $route);
            $bar->advance();
        }

        $bar->setMessage('Done');
        $bar->finish();
        $this->newLine(2);

        // Cleanup token
        $user->tokens()->where('name', 'api-scanner')->delete();
    }

    protected function buildUri($route): string
    {
        $uri = '/'.ltrim($route->uri(), '/');

        if ($this->option('with-params')) {
            // Replace required parameters with test value 1
            $uri = preg_replace('/\{[^?}]+\}/', '1', $uri);
        }

        // Remove optional parameters
        $uri = preg_replace('/\{[^}]+\?\}/', '', $uri);
        $uri = preg_replace('/\/+/', '/', $uri);
        $uri = rtrim($uri, '/') ?: '/';

        return $uri;
    }

    protected function testEndpoint(string $uri, string $token, string $subdomain, string $role, $route): void
    {
        try {
            $response = app()->handle(
                Request::create($uri, 'GET', [], [], [], [
                    'HTTP_AUTHORIZATION' => 'Bearer '.$token,
                    'HTTP_ACCEPT' => 'application/json',
                    'HTTP_X_ACADEMY_SUBDOMAIN' => $subdomain,
                    'HTTP_X_TENANT_SUBDOMAIN' => $subdomain,
                ])
            );

            $status = $response->getStatusCode();
            $contentType = $response->headers->get('Content-Type', '');
            $isJson = str_contains($contentType, 'json');

            $result = [
                'uri' => $uri,
                'status' => $status,
                'role' => $role,
                'name' => $route->getName() ?? '-',
                'action' => $route->getActionName(),
                'is_json' => $isJson,
            ];

            // Capture error details for 500s
            if ($status >= 500) {
                $body = $response->getContent();
                $decoded = json_decode($body, true);
                $result['error_message'] = $decoded['message'] ?? substr($body, 0, 200);
            }

            // Flag HTML responses on API routes as suspicious
            if (! $isJson && $status < 300 && str_contains($contentType, 'html')) {
                $result['warning'] = 'API route returned HTML instead of JSON';
            }

            $this->results[] = $result;

        } catch (Throwable $e) {
            $this->results[] = [
                'uri' => $uri,
                'status' => 'EXCEPTION',
                'role' => $role,
                'name' => $route->getName() ?? '-',
                'action' => $route->getActionName(),
                'exception' => get_class($e),
                'error_message' => $e->getMessage(),
                'file' => basename($e->getFile()).':'.$e->getLine(),
            ];
        }
    }

    protected function displaySummary(): void
    {
        $grouped = collect($this->results)->groupBy('role');

        $this->newLine();
        $this->info('RESULTS SUMMARY');
        $this->info('================');
        $this->newLine();

        foreach ($grouped as $role => $results) {
            $success = $results->filter(fn ($r) => $r['status'] >= 200 && $r['status'] < 300)->count();
            $redirects = $results->filter(fn ($r) => is_int($r['status']) && $r['status'] >= 300 && $r['status'] < 400)->count();
            $clientErrors = $results->filter(fn ($r) => is_int($r['status']) && $r['status'] >= 400 && $r['status'] < 500)->count();
            $serverErrors = $results->filter(fn ($r) => (is_int($r['status']) && $r['status'] >= 500) || $r['status'] === 'EXCEPTION')->count();
            $htmlWarnings = $results->filter(fn ($r) => isset($r['warning']))->count();

            $this->info("  [{$role}]");
            $this->line("    Success: {$success} | Redirects: {$redirects} | Client Errors: {$clientErrors} | Server Errors: {$serverErrors}");
            if ($htmlWarnings > 0) {
                $this->warn("    HTML responses: {$htmlWarnings}");
            }
        }

        $this->newLine();

        // Show all 500 errors
        $serverErrors = collect($this->results)->filter(fn ($r) => (is_int($r['status']) && $r['status'] >= 500) || $r['status'] === 'EXCEPTION');

        if ($serverErrors->isNotEmpty()) {
            $this->error('SERVER ERRORS (5xx):');
            $this->newLine();
            foreach ($serverErrors as $i => $r) {
                $this->error("  [{$r['role']}] [{$r['status']}] {$r['uri']}");
                $this->line("    Action: {$r['action']}");
                if (isset($r['error_message'])) {
                    $this->line('    Error: '.substr($r['error_message'], 0, 150));
                }
                if (isset($r['file'])) {
                    $this->line("    File: {$r['file']}");
                }
                $this->newLine();
            }
        } else {
            $this->info('No server errors found!');
        }

        // Show HTML warnings
        $htmlWarnings = collect($this->results)->filter(fn ($r) => isset($r['warning']));
        if ($htmlWarnings->isNotEmpty()) {
            $this->warn('HTML RESPONSES ON API ROUTES:');
            foreach ($htmlWarnings as $r) {
                $this->warn("  [{$r['role']}] {$r['uri']}");
            }
            $this->newLine();
        }

        if ($this->option('show-all')) {
            $successes = collect($this->results)->filter(fn ($r) => is_int($r['status']) && $r['status'] >= 200 && $r['status'] < 300);
            if ($successes->isNotEmpty()) {
                $this->info('SUCCESSFUL ENDPOINTS:');
                foreach ($successes as $r) {
                    $this->line("  [{$r['role']}] [{$r['status']}] {$r['uri']}");
                }
            }
        }
    }

    protected function saveReport(string $subdomain): void
    {
        $filename = "qa/reports/api-scan-".date('Y-m-d-His').".md";
        $dir = dirname(base_path($filename));
        if (! is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        $report = "# API Endpoint Scan Report\n\n";
        $report .= '**Generated:** '.now()->format('Y-m-d H:i:s')."\n";
        $report .= "**Academy:** {$subdomain}\n\n";

        // Summary table
        $grouped = collect($this->results)->groupBy('role');
        $report .= "## Summary\n\n";
        $report .= "| Role | 2xx | 3xx | 4xx | 5xx | Total |\n";
        $report .= "|------|-----|-----|-----|-----|-------|\n";

        foreach ($grouped as $role => $results) {
            $s2 = $results->filter(fn ($r) => is_int($r['status']) && $r['status'] >= 200 && $r['status'] < 300)->count();
            $s3 = $results->filter(fn ($r) => is_int($r['status']) && $r['status'] >= 300 && $r['status'] < 400)->count();
            $s4 = $results->filter(fn ($r) => is_int($r['status']) && $r['status'] >= 400 && $r['status'] < 500)->count();
            $s5 = $results->filter(fn ($r) => (is_int($r['status']) && $r['status'] >= 500) || $r['status'] === 'EXCEPTION')->count();
            $total = $results->count();
            $report .= "| {$role} | {$s2} | {$s3} | {$s4} | {$s5} | {$total} |\n";
        }

        // Server errors detail
        $serverErrors = collect($this->results)->filter(fn ($r) => (is_int($r['status']) && $r['status'] >= 500) || $r['status'] === 'EXCEPTION');
        if ($serverErrors->isNotEmpty()) {
            $report .= "\n## Server Errors\n\n";
            foreach ($serverErrors as $r) {
                $report .= "### [{$r['role']}] [{$r['status']}] `{$r['uri']}`\n\n";
                $report .= "- **Action:** {$r['action']}\n";
                if (isset($r['error_message'])) {
                    $report .= "- **Error:** {$r['error_message']}\n";
                }
                if (isset($r['file'])) {
                    $report .= "- **File:** {$r['file']}\n";
                }
                $report .= "\n";
            }
        }

        file_put_contents(base_path($filename), $report);
        $this->newLine();
        $this->info("Report saved: {$filename}");
    }
}
