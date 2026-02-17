<?php

namespace App\Console\Commands;

use Throwable;
use Illuminate\Contracts\Http\Kernel;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

class TestRoutesCommand extends Command
{
    protected $signature = 'routes:test
                            {--user-type= : Test with specific user type (student, parent, quran_teacher, academic_teacher, admin, super_admin)}
                            {--route-prefix= : Only test routes with this prefix}
                            {--limit=100 : Maximum routes to test}
                            {--verbose-errors : Show full error traces}';

    protected $description = 'Test all GET routes for 500 errors with actual logged-in users';

    /**
     * Hide this command in production environments.
     */
    public function isHidden(): bool
    {
        return app()->environment('production');
    }

    protected array $errors = [];

    protected array $successes = [];

    protected array $skipped = [];

    public function handle(): int
    {
        $this->info('🔍 Starting Route Testing...');
        $this->newLine();

        // Get test users by type
        $userTypes = $this->option('user-type')
            ? [$this->option('user-type')]
            : ['student', 'parent', 'quran_teacher', 'academic_teacher', 'admin'];

        $routePrefix = $this->option('route-prefix');
        $limit = (int) $this->option('limit');

        // Get all GET routes
        $routes = collect(Route::getRoutes())->filter(function ($route) use ($routePrefix) {
            // Only GET routes
            if (! in_array('GET', $route->methods())) {
                return false;
            }

            // Skip API routes (they need different handling)
            if (str_starts_with($route->getUri(), 'api/')) {
                return false;
            }

            // Skip Livewire, Filament internal routes
            if (str_starts_with($route->getUri(), 'livewire/') ||
                str_contains($route->getUri(), '/actions/') ||
                str_starts_with($route->getUri(), '_') ||
                str_starts_with($route->getUri(), 'sanctum/')) {
                return false;
            }

            // Apply prefix filter
            if ($routePrefix && ! str_starts_with($route->getUri(), $routePrefix)) {
                return false;
            }

            return true;
        })->take($limit);

        $this->info("Found {$routes->count()} GET routes to test");
        $this->newLine();

        foreach ($userTypes as $userType) {
            $this->testRoutesWithUserType($routes, $userType);
        }

        // Summary
        $this->newLine();
        $this->info('='.str_repeat('=', 70));
        $this->info('📊 SUMMARY');
        $this->info('='.str_repeat('=', 70));

        $this->info('✅ Successful routes: '.count($this->successes));
        $this->warn('⏭️ Skipped routes: '.count($this->skipped));
        $this->error('❌ Failed routes: '.count($this->errors));

        if (! empty($this->errors)) {
            $this->newLine();
            $this->error('FAILED ROUTES:');
            $this->table(
                ['User Type', 'Route', 'Error'],
                array_map(fn ($e) => [$e['user_type'], $e['uri'], substr($e['error'], 0, 80)], $this->errors)
            );

            // Write detailed errors to file
            $errorFile = storage_path('logs/route-test-errors.json');
            file_put_contents($errorFile, json_encode($this->errors, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
            $this->info("Detailed errors written to: {$errorFile}");
        }

        return count($this->errors) > 0 ? 1 : 0;
    }

    protected function testRoutesWithUserType($routes, string $userType): void
    {
        $user = User::where('user_type', $userType)->first();

        if (! $user) {
            $this->warn("No user found with type: {$userType}");

            return;
        }

        $this->info("Testing with {$userType}: {$user->email}");
        $this->newLine();

        $bar = $this->output->createProgressBar($routes->count());
        $bar->start();

        foreach ($routes as $route) {
            $bar->advance();

            $uri = $route->getUri();

            // Skip routes that require parameters we can't easily fill
            if (preg_match('/\{[^}]+\}/', $uri)) {
                // Try to replace common parameters
                $uri = $this->replaceRouteParameters($uri, $user);
                if (preg_match('/\{[^}]+\}/', $uri)) {
                    $this->skipped[] = [
                        'uri' => $route->getUri(),
                        'reason' => 'Has unfillable parameters',
                    ];

                    continue;
                }
            }

            // Replace subdomain placeholder
            $domain = str_replace('{subdomain}', 'itqan-academy', $route->getDomain() ?? '');
            $domain = str_replace('{tenant}', 'itqan-academy', $domain);

            try {
                $result = $this->testRoute($uri, $user, $route);

                if ($result['status'] >= 500) {
                    $this->errors[] = [
                        'user_type' => $userType,
                        'uri' => $uri,
                        'original_uri' => $route->getUri(),
                        'status' => $result['status'],
                        'error' => $result['error'],
                        'trace' => $result['trace'] ?? null,
                    ];
                } else {
                    $this->successes[] = [
                        'uri' => $uri,
                        'status' => $result['status'],
                    ];
                }
            } catch (Throwable $e) {
                $this->errors[] = [
                    'user_type' => $userType,
                    'uri' => $uri,
                    'original_uri' => $route->getUri(),
                    'status' => 500,
                    'error' => $e->getMessage(),
                    'trace' => $this->option('verbose-errors') ? $e->getTraceAsString() : null,
                    'file' => $e->getFile(),
                    'line' => $e->getLine(),
                ];
            }
        }

        $bar->finish();
        $this->newLine(2);
    }

    protected function testRoute(string $uri, User $user, $route): array
    {
        // Create a request
        $request = Request::create('/'.ltrim($uri, '/'), 'GET');
        $request->setLaravelSession(app('session.store'));

        // Set the user
        Auth::login($user);

        // Set academy context if needed
        if ($user->academy_id) {
            session(['current_academy_id' => $user->academy_id]);
        }

        try {
            // Handle the request through the kernel
            $kernel = app(Kernel::class);
            $response = $kernel->handle($request);

            $status = $response->getStatusCode();
            $error = '';

            if ($status >= 500) {
                $content = $response->getContent();
                // Try to extract error message from HTML
                if (preg_match('/<h1[^>]*>(.*?)<\/h1>/s', $content, $matches)) {
                    $error = strip_tags($matches[1]);
                } elseif (preg_match('/"message":"([^"]+)"/', $content, $matches)) {
                    $error = $matches[1];
                } else {
                    $error = substr(strip_tags($content), 0, 200);
                }
            }

            // Terminate the request
            $kernel->terminate($request, $response);

            return [
                'status' => $status,
                'error' => $error,
            ];
        } catch (Throwable $e) {
            return [
                'status' => 500,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ];
        } finally {
            Auth::logout();
        }
    }

    protected function replaceRouteParameters(string $uri, User $user): string
    {
        // Common parameter replacements
        $replacements = [
            '{session}' => '1',
            '{subscriptionId}' => '1',
            '{subscription}' => '1',
            '{student}' => '1',
            '{teacher}' => '1',
            '{circle}' => '1',
            '{course}' => '1',
            '{record}' => '1',
            '{quiz}' => '1',
            '{homework}' => '1',
            '{lesson}' => '1',
            '{packageId}' => '1',
            '{id}' => '1',
            '{child}' => '1',
            '{subdomain}' => 'itqan-academy',
            '{tenant}' => 'itqan-academy',
        ];

        foreach ($replacements as $param => $value) {
            $uri = str_replace($param, $value, $uri);
        }

        return $uri;
    }
}
