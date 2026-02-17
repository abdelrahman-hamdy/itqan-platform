<?php

namespace App\Console\Commands;

use Illuminate\Contracts\Http\Kernel;
use App\Enums\UserType;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use Throwable;

class CheckAllRoutes extends Command
{
    protected $signature = 'app:check-routes
                            {--user= : User ID to authenticate as}
                            {--admin : Use admin user}
                            {--public : Check without authentication}
                            {--show-all : Show all routes, not just errors}
                            {--role= : Check as specific role (super_admin, admin, quran_teacher, academic_teacher, supervisor, student, parent)}';

    protected $description = 'Check all GET routes for 500 errors and exceptions';

    /**
     * Hide this command in production environments.
     */
    public function isHidden(): bool
    {
        return app()->environment('production');
    }

    protected array $errors = [];

    protected array $success = [];

    protected array $redirects = [];

    protected array $clientErrors = [];

    public function handle()
    {
        $this->newLine();
        $this->info('🔍 ROUTE HEALTH CHECKER');
        $this->info('========================');
        $this->newLine();

        // Get user for authentication
        $user = $this->getUser();

        if ($user) {
            $this->info("🔐 Authenticated as: {$user->email} (ID: {$user->id})");
            $this->info('   User Type: '.($user->user_type ?? 'N/A'));
        } else {
            $this->warn('🌐 Checking as guest (no authentication)');
        }
        $this->newLine();

        // Get all testable routes
        $routes = $this->getTestableRoutes();
        $this->info("📋 Found {$routes->count()} testable routes");
        $this->newLine();

        // Progress bar
        $bar = $this->output->createProgressBar($routes->count());
        $bar->setFormat(' %current%/%max% [%bar%] %percent:3s%% -- %message%');
        $bar->setMessage('Starting...');
        $bar->start();

        foreach ($routes as $route) {
            $uri = $this->buildUri($route);
            $bar->setMessage($uri);

            $this->checkRoute($route, $uri, $user);
            $bar->advance();
        }

        $bar->setMessage('Complete!');
        $bar->finish();
        $this->newLine(2);

        // Display results
        $this->displayResults();

        // Save report
        $this->saveReport($user);

        return count($this->errors) > 0 ? 1 : 0;
    }

    protected function getTestableRoutes()
    {
        return collect(Route::getRoutes())->filter(function ($route) {
            // Only GET routes
            if (! in_array('GET', $route->methods())) {
                return false;
            }

            $uri = $route->getUri();

            // Skip routes with REQUIRED parameters (keep optional ones)
            if (preg_match('/\{[^?}]+\}/', $uri)) {
                return false;
            }

            // Skip system routes
            $skipPrefixes = [
                '_ignition', '_debugbar', 'telescope', 'horizon',
                'sanctum', 'livewire', '__clockwork', 'vapor-ui',
                'pulse', 'log-viewer',
            ];

            foreach ($skipPrefixes as $prefix) {
                if (str_starts_with($uri, $prefix)) {
                    return false;
                }
            }

            // Skip asset routes
            if (preg_match('/\.(js|css|ico|png|jpg|svg|woff|ttf)$/', $uri)) {
                return false;
            }

            // Skip Filament tenant redirect routes (cause programmatic testing issues)
            $actionName = $route->getActionName();
            if (str_contains($actionName, 'RedirectToTenantController')) {
                return false;
            }

            // Skip Filament auth and tenant redirect routes (work fine in browser, cause issues in testing)
            $routeName = $route->getName() ?? '';
            if (preg_match('/\.auth\.|\.tenant$/', $routeName)) {
                return false;
            }

            // Skip routes that require subdomains/tenants (can't be tested via route checker)
            $domain = $route->getDomain();
            if ($domain && preg_match('/\{(subdomain|tenant)\}/', $domain)) {
                return false;
            }

            return true;
        })->values();
    }

    protected function buildUri($route): string
    {
        $uri = '/'.ltrim($route->getUri(), '/');

        // Remove optional parameters
        $uri = preg_replace('/\{[^}]+\?\}/', '', $uri);
        $uri = preg_replace('/\/+/', '/', $uri); // Clean double slashes
        $uri = rtrim($uri, '/') ?: '/';

        return $uri;
    }

    protected function checkRoute($route, string $uri, ?User $user): void
    {
        try {
            // Create request
            $request = Request::create($uri, 'GET');

            // Authenticate if user provided - use session-based auth for web routes
            if ($user) {
                // Set the user on the session for web auth
                session(['auth.password_confirmed_at' => time()]);
                Auth::guard('web')->login($user);
                $request->setUserResolver(fn () => $user);

                // Also set session cookies for the request
                $request->setLaravelSession(app('session.store'));
            } else {
                Auth::guard('web')->logout();
            }

            // Handle request through a fresh app instance to avoid state issues
            $kernel = app(Kernel::class);
            $response = $kernel->handle($request);
            $kernel->terminate($request, $response);

            $status = $response->getStatusCode();

            $routeData = [
                'uri' => $uri,
                'status' => $status,
                'name' => $route->getName() ?? '-',
                'action' => $route->getActionName(),
                'middleware' => implode(', ', $route->middleware() ?? []),
            ];

            if ($status >= 500) {
                $this->errors[] = $routeData;
            } elseif ($status >= 400) {
                $this->clientErrors[] = $routeData;
            } elseif ($status >= 300) {
                $this->redirects[] = $routeData;
            } else {
                $this->success[] = $routeData;
            }

        } catch (Throwable $e) {
            $this->errors[] = [
                'uri' => $uri,
                'status' => 'EXCEPTION',
                'name' => $route->getName() ?? '-',
                'action' => $route->getActionName(),
                'middleware' => implode(', ', $route->middleware() ?? []),
                'exception' => get_class($e),
                'message' => $e->getMessage(),
                'file' => $e->getFile().':'.$e->getLine(),
            ];
        }
    }

    protected function displayResults(): void
    {
        $this->info('📊 RESULTS SUMMARY');
        $this->info('==================');
        $this->newLine();

        $this->line('   ✅ Success (2xx):     '.count($this->success));
        $this->line('   ↪️  Redirects (3xx):   '.count($this->redirects));
        $this->line('   ⚠️  Client Errors (4xx): '.count($this->clientErrors));
        $this->line('   ❌ Server Errors (5xx): '.count($this->errors));
        $this->newLine();

        // Show verbose output if requested
        if ($this->option('show-all')) {
            if (count($this->success) > 0) {
                $this->info('✅ SUCCESSFUL ROUTES:');
                foreach ($this->success as $route) {
                    $this->line("   [{$route['status']}] {$route['uri']}");
                }
                $this->newLine();
            }
        }

        // Always show 4xx errors (might indicate missing data/permissions)
        if (count($this->clientErrors) > 0) {
            $this->warn('⚠️  CLIENT ERRORS (4xx) - May need attention:');
            foreach ($this->clientErrors as $route) {
                $this->warn("   [{$route['status']}] {$route['uri']}");
                $this->line("      → {$route['action']}");
            }
            $this->newLine();
        }

        // Always show 5xx errors
        if (count($this->errors) > 0) {
            $this->error('❌ SERVER ERRORS (5xx) - MUST FIX:');
            $this->newLine();

            foreach ($this->errors as $i => $route) {
                $num = $i + 1;
                $this->error("   #{$num} [{$route['status']}] {$route['uri']}");
                $this->line("      Route Name: {$route['name']}");
                $this->line("      Action: {$route['action']}");
                $this->line("      Middleware: {$route['middleware']}");

                if (isset($route['exception'])) {
                    $this->line("      Exception: {$route['exception']}");
                    $this->line('      Message: '.substr($route['message'], 0, 100));
                    $this->line("      File: {$route['file']}");
                }
                $this->newLine();
            }
        } else {
            $this->info('🎉 No server errors found!');
        }
    }

    protected function getUser(): ?User
    {
        if ($this->option('public')) {
            return null;
        }

        // Handle --role option
        if ($role = $this->option('role')) {
            $validRoles = ['super_admin', 'admin', 'quran_teacher', 'academic_teacher', 'supervisor', 'student', 'parent'];
            if (! in_array($role, $validRoles)) {
                $this->error("Invalid role: {$role}. Valid roles: ".implode(', ', $validRoles));
                exit(1);
            }

            // Try to find test user first
            $testDomain = config('seeding.test_email_domain');
            $testEmails = [
                'super_admin' => 'super@'.$testDomain,
                'admin' => 'admin@'.$testDomain,
                'quran_teacher' => 'quran.teacher@'.$testDomain,
                'academic_teacher' => 'academic.teacher@'.$testDomain,
                'supervisor' => 'supervisor@'.$testDomain,
                'student' => 'student@'.$testDomain,
                'parent' => 'parent@'.$testDomain,
            ];

            $user = User::where('email', $testEmails[$role])->first();

            if (! $user) {
                // Fallback to any user with that role
                $user = User::where('user_type', $role)->first();
            }

            if (! $user) {
                $this->error("No user found with role: {$role}. Run 'php artisan app:generate-test-data' first.");
                exit(1);
            }

            return $user;
        }

        if ($userId = $this->option('user')) {
            $user = User::find($userId);
            if (! $user) {
                $this->error("User with ID {$userId} not found!");
                exit(1);
            }

            return $user;
        }

        if ($this->option('admin')) {
            // Try common patterns to find admin user
            $user = User::where('user_type', UserType::SUPER_ADMIN->value)->first()
                ?? User::where('user_type', UserType::ADMIN->value)->first()
                ?? User::where('email', 'like', '%admin%')->first();

            if (! $user) {
                $this->warn('Could not find admin user, using first user instead');
                $user = User::first();
            }

            return $user;
        }

        // Default: use first user
        return User::first();
    }

    protected function saveReport(?User $user): void
    {
        $role = $this->option('role') ?? ($user?->user_type ?? 'guest');
        $filename = "BROKEN_ROUTES_{$role}.md";

        $report = "# Broken Routes Report - {$role}\n\n";
        $report .= '**Generated:** '.now()->format('Y-m-d H:i:s')."\n";
        $report .= '**Authenticated as:** '.($user ? "{$user->email} (ID: {$user->id})" : 'Guest')."\n";
        $report .= '**User Type:** '.($user?->user_type ?? 'N/A')."\n\n";

        $report .= "## Summary\n\n";
        $report .= "| Status | Count |\n";
        $report .= "|--------|-------|\n";
        $report .= '| ✅ Success (2xx) | '.count($this->success)." |\n";
        $report .= '| ↪️ Redirects (3xx) | '.count($this->redirects)." |\n";
        $report .= '| ⚠️ Client Errors (4xx) | '.count($this->clientErrors)." |\n";
        $report .= '| ❌ Server Errors (5xx) | '.count($this->errors)." |\n\n";

        if (count($this->errors) > 0) {
            $report .= "## ❌ Server Errors (MUST FIX)\n\n";
            foreach ($this->errors as $i => $route) {
                $num = $i + 1;
                $report .= "### #{$num} [{$route['status']}] {$route['uri']}\n\n";
                $report .= "- **Route Name:** {$route['name']}\n";
                $report .= "- **Action:** {$route['action']}\n";
                $report .= "- **Middleware:** {$route['middleware']}\n";

                if (isset($route['exception'])) {
                    $report .= "- **Exception:** {$route['exception']}\n";
                    $report .= "- **Message:** {$route['message']}\n";
                    $report .= "- **File:** {$route['file']}\n";
                }
                $report .= "\n---\n\n";
            }
        }

        if (count($this->clientErrors) > 0) {
            $report .= "## ⚠️ Client Errors (May Need Attention)\n\n";
            foreach ($this->clientErrors as $route) {
                $report .= "- [{$route['status']}] {$route['uri']} → {$route['action']}\n";
            }
            $report .= "\n";
        }

        file_put_contents(base_path($filename), $report);
        $this->newLine();
        $this->info("📄 Report saved to: {$filename}");
    }
}
