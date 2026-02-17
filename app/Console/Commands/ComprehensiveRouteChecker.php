<?php

namespace App\Console\Commands;

use Illuminate\Http\Request;
use Illuminate\Contracts\Http\Kernel;
use App\Enums\UserType;
use App\Models\AcademicHomework;
use App\Models\AcademicHomeworkSubmission;
use App\Models\AcademicIndividualLesson;
use App\Models\AcademicSession;
use App\Models\AcademicSubscription;
use App\Models\AcademicTeacherProfile;
use App\Models\Academy;
use App\Models\Certificate;
use App\Models\InteractiveCourse;
use App\Models\Lesson;
use App\Models\Payment;
use App\Models\Quiz;
use App\Models\QuranCircle;
use App\Models\QuranIndividualCircle;
use App\Models\QuranSession;
use App\Models\QuranSubscription;
use App\Models\RecordedCourse;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use Throwable;

class ComprehensiveRouteChecker extends Command
{
    protected $signature = 'app:comprehensive-route-check
                            {--role=all : Check specific role or "all" (super_admin, admin, quran_teacher, academic_teacher, supervisor, student, parent)}
                            {--include-parameterized : Include routes with parameters (slower, requires test data)}
                            {--output=json : Output format (json, markdown, console)}';

    protected $description = 'Comprehensive route checker that tests ALL routes including parameterized ones';

    /**
     * Hide this command in production environments.
     */
    public function isHidden(): bool
    {
        return app()->environment('production');
    }

    protected array $allResults = [];

    protected ?Academy $academy = null;

    protected array $testData = [];

    public function handle(): int
    {
        $this->info('🔍 COMPREHENSIVE ROUTE CHECKER');
        $this->info('==============================');
        $this->newLine();

        $roles = $this->option('role') === 'all'
            ? ['super_admin', 'admin', 'supervisor', 'quran_teacher', 'academic_teacher', 'student', 'parent']
            : [$this->option('role')];

        // Setup test data if needed
        if ($this->option('include-parameterized')) {
            $this->setupTestData();
        }

        foreach ($roles as $role) {
            $this->info("📌 Testing role: {$role}");
            $this->newLine();
            $this->testRole($role);
        }

        // Save results
        $this->saveResults();

        // Summary
        $this->displaySummary();

        return $this->hasErrors() ? 1 : 0;
    }

    protected function setupTestData(): void
    {
        $this->info('Setting up test data for parameterized routes...');

        // Get or create academy
        $this->academy = Academy::where('subdomain', 'itqan-academy')->first()
            ?? Academy::first();

        if (! $this->academy) {
            $this->warn('No academy found, parameterized route testing may fail.');

            return;
        }

        // Collect test data
        $this->testData = [
            'academy' => $this->academy->id,
            'tenant' => $this->academy->subdomain,
            'subdomain' => $this->academy->subdomain,
            'session' => QuranSession::first()?->id ?? AcademicSession::first()?->id ?? 1,
            'sessionId' => QuranSession::first()?->id ?? AcademicSession::first()?->id ?? 1,
            'quranSession' => QuranSession::first()?->id ?? 1,
            'academicSession' => AcademicSession::first()?->id ?? 1,
            'subscription' => QuranSubscription::first()?->id ?? AcademicSubscription::first()?->id ?? 1,
            'subscriptionId' => QuranSubscription::first()?->id ?? AcademicSubscription::first()?->id ?? 1,
            'circle' => QuranIndividualCircle::first()?->id ?? QuranCircle::first()?->id ?? 1,
            'circleId' => QuranIndividualCircle::first()?->id ?? QuranCircle::first()?->id ?? 1,
            'individualCircle' => QuranIndividualCircle::first()?->id ?? 1,
            'teacher' => User::where('user_type', UserType::QURAN_TEACHER->value)->first()?->id
                ?? User::where('user_type', UserType::ACADEMIC_TEACHER->value)->first()?->id ?? 1,
            'teacherId' => User::where('user_type', UserType::QURAN_TEACHER->value)->first()?->id
                ?? User::where('user_type', UserType::ACADEMIC_TEACHER->value)->first()?->id ?? 1,
            'student' => User::where('user_type', UserType::STUDENT->value)->first()?->id ?? 1,
            'studentId' => User::where('user_type', UserType::STUDENT->value)->first()?->id ?? 1,
            'user' => User::first()?->id ?? 1,
            'userId' => User::first()?->id ?? 1,
            'course' => InteractiveCourse::first()?->id ?? RecordedCourse::first()?->id ?? 1,
            'courseId' => InteractiveCourse::first()?->id ?? RecordedCourse::first()?->id ?? 1,
            'interactiveCourse' => InteractiveCourse::first()?->id ?? 1,
            'recordedCourse' => RecordedCourse::first()?->id ?? 1,
            'lesson' => Lesson::first()?->id ?? 1,
            'lessonId' => Lesson::first()?->id ?? 1,
            'academicLesson' => AcademicIndividualLesson::first()?->id ?? 1,
            'homework' => AcademicHomework::first()?->id ?? 1,
            'homeworkId' => AcademicHomework::first()?->id ?? 1,
            'submission' => AcademicHomeworkSubmission::first()?->id ?? 1,
            'submissionId' => AcademicHomeworkSubmission::first()?->id ?? 1,
            'quiz' => Quiz::first()?->id ?? 1,
            'quizId' => Quiz::first()?->id ?? 1,
            'certificate' => Certificate::first()?->id ?? 1,
            'certificateId' => Certificate::first()?->id ?? 1,
            'payment' => Payment::first()?->id ?? 1,
            'paymentId' => Payment::first()?->id ?? 1,
            'package' => AcademicTeacherProfile::first()?->id ?? 1,
            'packageId' => AcademicTeacherProfile::first()?->id ?? 1,
            'record' => 1,
        ];

        $this->info('Test data prepared: '.count($this->testData).' bindings');
        $this->newLine();
    }

    protected function testRole(string $role): void
    {
        $user = $this->getUserForRole($role);

        if (! $user) {
            $this->error("No user found for role: {$role}");
            $this->allResults[$role] = ['errors' => [['message' => 'No user found for role']], 'success' => [], 'client_errors' => []];

            return;
        }

        $this->info("Authenticated as: {$user->email}");

        $routes = $this->getTestableRoutes($role);
        $this->info("Found {$routes->count()} testable routes for this role");

        $results = [
            'user' => $user->email,
            'errors' => [],
            'client_errors' => [],
            'success' => [],
            'redirects' => [],
        ];

        $bar = $this->output->createProgressBar($routes->count());
        $bar->setFormat(' %current%/%max% [%bar%] %percent:3s%% -- %message%');
        $bar->setMessage('Starting...');
        $bar->start();

        foreach ($routes as $route) {
            $uri = $this->buildUri($route);
            $bar->setMessage(substr($uri, 0, 50));

            $result = $this->checkRoute($route, $uri, $user);

            if ($result['status'] >= 500 || $result['status'] === 'EXCEPTION') {
                $results['errors'][] = $result;
            } elseif ($result['status'] >= 400) {
                $results['client_errors'][] = $result;
            } elseif ($result['status'] >= 300) {
                $results['redirects'][] = $result;
            } else {
                $results['success'][] = $result;
            }

            $bar->advance();
        }

        $bar->setMessage('Complete!');
        $bar->finish();
        $this->newLine(2);

        $this->allResults[$role] = $results;

        // Display quick stats
        $this->line('   ✅ Success: '.count($results['success']));
        $this->line('   ↪️  Redirects: '.count($results['redirects']));
        $this->line('   ⚠️  Client Errors (4xx): '.count($results['client_errors']));
        $this->line('   ❌ Server Errors (5xx): '.count($results['errors']));
        $this->newLine();
    }

    protected function getUserForRole(string $role): ?User
    {
        $domain = config('seeding.test_email_domain');
        $testEmails = [
            'super_admin' => 'super@'.$domain,
            'admin' => 'admin@'.$domain,
            'quran_teacher' => 'quran.teacher@'.$domain,
            'academic_teacher' => 'academic.teacher@'.$domain,
            'supervisor' => 'supervisor@'.$domain,
            'student' => 'student@'.$domain,
            'parent' => 'parent@'.$domain,
        ];

        // Try test email first
        $user = User::where('email', $testEmails[$role] ?? '')->first();

        if (! $user) {
            // Fallback to any user with that role
            $user = User::where('user_type', $role)->first();
        }

        return $user;
    }

    protected function getTestableRoutes(string $role): Collection
    {
        $includeParameterized = $this->option('include-parameterized');

        return collect(Route::getRoutes())->filter(function ($route) use ($includeParameterized, $role) {
            // Only GET routes
            if (! in_array('GET', $route->methods())) {
                return false;
            }

            $uri = $route->getUri();
            $routeName = $route->getName() ?? '';

            // Skip routes with required parameters unless include-parameterized is set
            if (! $includeParameterized && preg_match('/\{[^?}]+\}/', $uri)) {
                return false;
            }

            // Skip system routes
            $skipPrefixes = [
                '_ignition', '_debugbar', 'telescope', 'horizon',
                'sanctum', 'livewire', '__clockwork', 'vapor-ui',
                'pulse', 'log-viewer', '_boost', 'filament/exports',
                'filament/imports', 'storage/',
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

            // Skip Filament tenant redirect routes
            $actionName = $route->getActionName();
            if (str_contains($actionName, 'RedirectToTenantController')) {
                return false;
            }

            // Skip auth routes
            if (preg_match('/\.auth\.|\.tenant$/', $routeName)) {
                return false;
            }

            // Role-based panel filtering
            if ($role === 'quran_teacher') {
                // Quran teachers should test teacher-panel routes
                if (str_contains($uri, 'academic-teacher-panel') || str_contains($uri, 'panel/')) {
                    if (! str_contains($uri, 'teacher-panel')) {
                        return false;
                    }
                }
            } elseif ($role === 'academic_teacher') {
                // Academic teachers should test academic-teacher-panel routes
                if (str_contains($uri, 'teacher-panel') && ! str_contains($uri, 'academic-teacher-panel')) {
                    return false;
                }
            } elseif ($role === 'student') {
                // Students should test student routes
                if (str_contains($uri, '-panel/') || str_contains($uri, 'panel/')) {
                    return false;
                }
            } elseif ($role === 'parent') {
                // Parents should test parent routes
                if (str_contains($uri, '-panel/') || str_contains($uri, 'panel/')) {
                    return false;
                }
            }

            return true;
        })->values();
    }

    protected function buildUri($route): string
    {
        $uri = '/'.ltrim($route->getUri(), '/');

        // Remove domain part if present
        $domain = $route->getDomain();
        if ($domain) {
            // Handle subdomain routes - prepend subdomain
            if (preg_match('/\{(subdomain|tenant)\}/', $domain)) {
                $subdomain = $this->testData['tenant'] ?? 'itqan-academy';
                $uri = "/{$subdomain}".$uri;
            }
        }

        // Replace optional parameters with empty
        $uri = preg_replace('/\{[^}]+\?\}/', '', $uri);

        // Replace required parameters with test data
        if ($this->option('include-parameterized')) {
            foreach ($this->testData as $param => $value) {
                $uri = preg_replace("/\{{$param}\}/i", (string) $value, $uri);
            }
        }

        // Clean up
        $uri = preg_replace('/\/+/', '/', $uri);
        $uri = rtrim($uri, '/') ?: '/';

        return $uri;
    }

    protected function checkRoute($route, string $uri, User $user): array
    {
        $routeData = [
            'uri' => $uri,
            'name' => $route->getName() ?? '-',
            'action' => $route->getActionName(),
            'middleware' => implode(', ', $route->middleware() ?? []),
        ];

        try {
            // Use Laravel's testing request
            $request = Request::create($uri, 'GET');

            // Authenticate
            Auth::guard('web')->login($user);
            $request->setUserResolver(fn () => $user);
            $request->setLaravelSession(app('session.store'));

            // Set tenant context if academy available
            if ($this->academy) {
                app()->forgetInstance('currentTenant');
                app()->instance('currentTenant', $this->academy);
            }

            // Handle request
            $kernel = app(Kernel::class);
            $response = $kernel->handle($request);
            $status = $response->getStatusCode();
            $kernel->terminate($request, $response);

            $routeData['status'] = $status;

            // Try to capture error message for 500 errors
            if ($status >= 500) {
                $content = $response->getContent();
                if ($content && strlen($content) < 5000) {
                    // Try to extract error message from HTML
                    if (preg_match('/<title>([^<]+)<\/title>/', $content, $matches)) {
                        $routeData['error_title'] = $matches[1];
                    }
                }
            }

            return $routeData;

        } catch (Throwable $e) {
            return array_merge($routeData, [
                'status' => 'EXCEPTION',
                'exception' => get_class($e),
                'message' => substr($e->getMessage(), 0, 200),
                'file' => basename($e->getFile()).':'.$e->getLine(),
            ]);
        }
    }

    protected function saveResults(): void
    {
        $format = $this->option('output');

        if ($format === 'json') {
            $filename = 'route-test-results.json';
            file_put_contents(base_path($filename), json_encode($this->allResults, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
            $this->info("📄 Results saved to: {$filename}");
        } else {
            // Markdown format
            $filename = 'ROUTE_TEST_RESULTS.md';
            $content = $this->generateMarkdownReport();
            file_put_contents(base_path($filename), $content);
            $this->info("📄 Results saved to: {$filename}");
        }
    }

    protected function generateMarkdownReport(): string
    {
        $report = "# Comprehensive Route Test Results\n\n";
        $report .= '**Generated:** '.now()->format('Y-m-d H:i:s')."\n\n";

        foreach ($this->allResults as $role => $results) {
            $report .= "## Role: {$role}\n\n";

            if (isset($results['user'])) {
                $report .= "**User:** {$results['user']}\n\n";
            }

            $report .= "| Status | Count |\n";
            $report .= "|--------|-------|\n";
            $report .= '| ✅ Success | '.count($results['success'] ?? [])." |\n";
            $report .= '| ↪️ Redirects | '.count($results['redirects'] ?? [])." |\n";
            $report .= '| ⚠️ Client Errors (4xx) | '.count($results['client_errors'] ?? [])." |\n";
            $report .= '| ❌ Server Errors (5xx) | '.count($results['errors'] ?? [])." |\n\n";

            if (! empty($results['errors'])) {
                $report .= "### ❌ Server Errors (MUST FIX)\n\n";
                foreach ($results['errors'] as $error) {
                    $report .= "- **[{$error['status']}]** `{$error['uri']}`\n";
                    $report .= "  - Action: `{$error['action']}`\n";
                    if (isset($error['exception'])) {
                        $report .= "  - Exception: `{$error['exception']}`\n";
                        $report .= "  - Message: {$error['message']}\n";
                        $report .= "  - File: {$error['file']}\n";
                    }
                    $report .= "\n";
                }
            }

            if (! empty($results['client_errors'])) {
                $report .= "### ⚠️ Client Errors (403/404)\n\n";
                $grouped403 = [];
                $grouped404 = [];
                foreach ($results['client_errors'] as $error) {
                    if ($error['status'] == 403) {
                        $grouped403[] = $error;
                    } elseif ($error['status'] == 404) {
                        $grouped404[] = $error;
                    }
                }

                if (! empty($grouped403)) {
                    $report .= '#### 403 Forbidden ('.count($grouped403).")\n\n";
                    foreach (array_slice($grouped403, 0, 20) as $error) {
                        $report .= "- `{$error['uri']}`\n";
                    }
                    if (count($grouped403) > 20) {
                        $report .= '- ... and '.(count($grouped403) - 20)." more\n";
                    }
                    $report .= "\n";
                }

                if (! empty($grouped404)) {
                    $report .= '#### 404 Not Found ('.count($grouped404).")\n\n";
                    foreach (array_slice($grouped404, 0, 20) as $error) {
                        $report .= "- `{$error['uri']}`\n";
                    }
                    if (count($grouped404) > 20) {
                        $report .= '- ... and '.(count($grouped404) - 20)." more\n";
                    }
                    $report .= "\n";
                }
            }

            $report .= "---\n\n";
        }

        return $report;
    }

    protected function displaySummary(): void
    {
        $this->newLine();
        $this->info('='.str_repeat('=', 60));
        $this->info('📊 OVERALL SUMMARY');
        $this->info('='.str_repeat('=', 60));
        $this->newLine();

        $totalErrors = 0;
        $totalClientErrors = 0;

        foreach ($this->allResults as $role => $results) {
            $errors = count($results['errors'] ?? []);
            $clientErrors = count($results['client_errors'] ?? []);
            $totalErrors += $errors;
            $totalClientErrors += $clientErrors;

            $status = $errors > 0 ? '❌' : ($clientErrors > 0 ? '⚠️' : '✅');
            $this->line("{$status} {$role}: {$errors} server errors, {$clientErrors} client errors");
        }

        $this->newLine();

        if ($totalErrors > 0) {
            $this->error("Total Server Errors: {$totalErrors}");
        } else {
            $this->info('🎉 No server errors found across all roles!');
        }

        if ($totalClientErrors > 0) {
            $this->warn("Total Client Errors (403/404): {$totalClientErrors}");
        }
    }

    protected function hasErrors(): bool
    {
        foreach ($this->allResults as $results) {
            if (! empty($results['errors'])) {
                return true;
            }
        }

        return false;
    }
}
