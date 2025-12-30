<?php

namespace App\Console\Commands;

use App\Models\AcademicSession;
use App\Models\AcademicSubscription;
use App\Models\Certificate;
use App\Models\InteractiveCourse;
use App\Models\InteractiveCourseSession;
use App\Models\Payment;
use App\Models\QuranCircle;
use App\Models\QuranIndividualCircle;
use App\Models\QuranSession;
use App\Models\QuranSubscription;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;

class TestUserPagesCommand extends Command
{
    protected $signature = 'app:test-user-pages
                            {--role=all : Role to test (student, parent, quran_teacher, academic_teacher, admin, all)}
                            {--subdomain=itqan-academy : Subdomain to use}
                            {--output=table : Output format (table, json)}
                            {--fix : Show suggested fixes for errors}';

    protected $description = 'Test all viewable pages for each user role using real database data';

    private array $errors = [];
    private array $successes = [];
    private string $subdomain;

    public function handle(): int
    {
        $this->subdomain = $this->option('subdomain');
        $role = $this->option('role');

        $this->info('🔍 COMPREHENSIVE PAGE TESTER WITH REAL DATA');
        $this->info('============================================');
        $this->newLine();

        $roles = $role === 'all'
            ? ['student', 'parent', 'quran_teacher', 'academic_teacher', 'admin', 'super_admin']
            : [$role];

        foreach ($roles as $testRole) {
            $this->testRole($testRole);
        }

        $this->displaySummary();

        return count($this->errors) > 0 ? 1 : 0;
    }

    private function testRole(string $role): void
    {
        $this->info("📌 Testing role: {$role}");
        $this->newLine();

        // Find a user with this role
        $user = $this->findUserForRole($role);

        if (!$user) {
            $this->warn("   ⚠️  No user found for role: {$role}");
            $this->newLine();
            return;
        }

        $this->info("   User: {$user->email} (ID: {$user->id})");

        // Login as this user
        Auth::login($user);

        // Get pages to test for this role
        $pages = $this->getPagesForRole($role, $user);

        $this->info("   Found " . count($pages) . " pages to test");
        $this->newLine();

        $bar = $this->output->createProgressBar(count($pages));
        $bar->start();

        foreach ($pages as $page) {
            $this->testPage($page, $user, $role);
            $bar->advance();
        }

        $bar->finish();
        $this->newLine(2);

        Auth::logout();
    }

    private function findUserForRole(string $role): ?User
    {
        $query = User::query();

        switch ($role) {
            case 'student':
                $query->where('user_type', 'student');
                break;
            case 'parent':
                $query->where('user_type', 'parent');
                break;
            case 'quran_teacher':
                $query->where('user_type', 'quran_teacher');
                break;
            case 'academic_teacher':
                $query->where('user_type', 'academic_teacher');
                break;
            case 'admin':
                $query->where('user_type', 'admin');
                break;
            case 'super_admin':
                $query->where('user_type', 'super_admin');
                break;
            case 'supervisor':
                $query->where('user_type', 'supervisor');
                break;
        }

        return $query->first();
    }

    private function getPagesForRole(string $role, User $user): array
    {
        $pages = [];

        switch ($role) {
            case 'student':
                $pages = $this->getStudentPages($user);
                break;
            case 'parent':
                $pages = $this->getParentPages($user);
                break;
            case 'quran_teacher':
                $pages = $this->getQuranTeacherPages($user);
                break;
            case 'academic_teacher':
                $pages = $this->getAcademicTeacherPages($user);
                break;
            case 'admin':
            case 'super_admin':
                $pages = $this->getAdminPages($user);
                break;
        }

        return $pages;
    }

    private function getStudentPages(User $user): array
    {
        $pages = [
            // Static pages
            ['name' => 'Profile', 'url' => "/{$this->subdomain}/profile", 'route' => 'student.profile'],
            ['name' => 'Payments', 'url' => "/{$this->subdomain}/payments", 'route' => 'student.payments'],
            ['name' => 'Subscriptions', 'url' => "/{$this->subdomain}/subscriptions", 'route' => 'student.subscriptions'],
            ['name' => 'Certificates', 'url' => "/{$this->subdomain}/certificates", 'route' => 'student.certificates'],
            ['name' => 'Homework', 'url' => "/{$this->subdomain}/homework", 'route' => 'student.homework.index'],
            ['name' => 'Quizzes', 'url' => "/{$this->subdomain}/quizzes", 'route' => 'student.quizzes'],
            ['name' => 'Calendar', 'url' => "/{$this->subdomain}/student/calendar", 'route' => 'student.calendar'],
            ['name' => 'Search', 'url' => "/{$this->subdomain}/search", 'route' => 'student.search'],
        ];

        // Add dynamic pages with real data
        // Quran Sessions (student_id references User.id directly)
        $quranSessions = QuranSession::where('student_id', $user->id)->limit(3)->pluck('id');
        foreach ($quranSessions as $sessionId) {
            $pages[] = ['name' => "Quran Session #{$sessionId}", 'url' => "/{$this->subdomain}/sessions/{$sessionId}", 'route' => 'student.sessions.show'];
        }

        // Academic Subscriptions
        $academicSubs = AcademicSubscription::where('student_id', $user->id)->limit(3)->pluck('id');
        foreach ($academicSubs as $subId) {
            $pages[] = ['name' => "Academic Subscription #{$subId}", 'url' => "/{$this->subdomain}/academic-subscriptions/{$subId}", 'route' => 'student.academic-subscriptions.show'];
        }

        // Academic Sessions
        $academicSessions = AcademicSession::whereHas('academicSubscription', fn($q) => $q->where('student_id', $user->id))
            ->limit(3)->pluck('id');
        foreach ($academicSessions as $sessionId) {
            $pages[] = ['name' => "Academic Session #{$sessionId}", 'url' => "/{$this->subdomain}/academic-sessions/{$sessionId}", 'route' => 'student.academic-sessions.show'];
        }

        // Interactive Course Sessions
        $interactiveSessions = InteractiveCourseSession::whereHas('course.enrollments', fn($q) => $q->where('student_id', $user->id))
            ->limit(3)->pluck('id');
        foreach ($interactiveSessions as $sessionId) {
            $pages[] = ['name' => "Interactive Session #{$sessionId}", 'url' => "/{$this->subdomain}/student/interactive-sessions/{$sessionId}", 'route' => 'student.interactive-sessions.show'];
        }

        // Certificates
        $certificates = Certificate::where('student_id', $user->id)->limit(3)->pluck('id');
        foreach ($certificates as $certId) {
            $pages[] = ['name' => "Certificate #{$certId}", 'url' => "/{$this->subdomain}/certificates/{$certId}/view", 'route' => 'student.certificate.view'];
        }

        // Individual Circles (student_id references User.id directly)
        $individualCircles = QuranIndividualCircle::where('student_id', $user->id)->limit(3)->pluck('id');
        foreach ($individualCircles as $circleId) {
            $pages[] = ['name' => "Individual Circle #{$circleId}", 'url' => "/{$this->subdomain}/individual-circles/{$circleId}", 'route' => 'individual-circles.show'];
            $pages[] = ['name' => "Individual Circle Report #{$circleId}", 'url' => "/{$this->subdomain}/individual-circles/{$circleId}/report", 'route' => 'student.individual-circles.report'];
        }

        // Group Circles - get circles where user is enrolled as student
        $groupCircles = QuranCircle::whereHas('students', fn($q) => $q->where('users.id', $user->id))
            ->limit(3)->pluck('id');
        foreach ($groupCircles as $circleId) {
            $pages[] = ['name' => "Group Circle #{$circleId}", 'url' => "/{$this->subdomain}/quran-circles/{$circleId}", 'route' => 'quran-circles.show'];
            $pages[] = ['name' => "Group Circle Report #{$circleId}", 'url' => "/{$this->subdomain}/group-circles/{$circleId}/report", 'route' => 'student.group-circles.report'];
        }

        return $pages;
    }

    private function getParentPages(User $user): array
    {
        $pages = [
            ['name' => 'Dashboard', 'url' => "/{$this->subdomain}/parent", 'route' => 'parent.dashboard'],
            ['name' => 'Profile', 'url' => "/{$this->subdomain}/parent/profile", 'route' => 'parent.profile'],
            ['name' => 'Profile Edit', 'url' => "/{$this->subdomain}/parent/profile/edit", 'route' => 'parent.profile.edit'],
            ['name' => 'Children', 'url' => "/{$this->subdomain}/parent/children", 'route' => 'parent.children.index'],
            ['name' => 'Upcoming Sessions', 'url' => "/{$this->subdomain}/parent/sessions/upcoming", 'route' => 'parent.sessions.upcoming'],
            ['name' => 'Session History', 'url' => "/{$this->subdomain}/parent/sessions/history", 'route' => 'parent.sessions.history'],
            ['name' => 'Calendar', 'url' => "/{$this->subdomain}/parent/calendar", 'route' => 'parent.calendar.index'],
            ['name' => 'Subscriptions', 'url' => "/{$this->subdomain}/parent/subscriptions", 'route' => 'parent.subscriptions.index'],
            ['name' => 'Payments', 'url' => "/{$this->subdomain}/parent/payments", 'route' => 'parent.payments.index'],
            ['name' => 'Certificates', 'url' => "/{$this->subdomain}/parent/certificates", 'route' => 'parent.certificates.index'],
            ['name' => 'Progress Report', 'url' => "/{$this->subdomain}/parent/reports/progress", 'route' => 'parent.reports.progress'],
            ['name' => 'Homework', 'url' => "/{$this->subdomain}/parent/homework", 'route' => 'parent.homework.index'],
            ['name' => 'Quizzes', 'url' => "/{$this->subdomain}/parent/quizzes", 'route' => 'parent.quizzes.index'],
        ];

        // Get child user IDs for dynamic pages
        $childUserIds = [];
        if ($user->parentProfile) {
            $childUserIds = $user->parentProfile->students()->with('user')->get()->pluck('user.id')->filter()->toArray();
        }

        // Add dynamic pages based on children's data
        if (!empty($childUserIds)) {
            // Children's quran sessions
            $quranSessions = QuranSession::whereIn('student_id', $childUserIds)->limit(2)->get();
            foreach ($quranSessions as $session) {
                $pages[] = ['name' => "Child Quran Session #{$session->id}", 'url' => "/{$this->subdomain}/parent/sessions/quran/{$session->id}", 'route' => 'parent.sessions.show'];
            }

            // Children's academic sessions
            $academicSessions = AcademicSession::whereHas('academicSubscription', fn($q) => $q->whereIn('student_id', $childUserIds))
                ->limit(2)->get();
            foreach ($academicSessions as $session) {
                $pages[] = ['name' => "Child Academic Session #{$session->id}", 'url' => "/{$this->subdomain}/parent/sessions/academic/{$session->id}", 'route' => 'parent.sessions.show'];
            }
        }

        return $pages;
    }

    private function getQuranTeacherPages(User $user): array
    {
        $pages = [
            ['name' => 'Profile', 'url' => "/{$this->subdomain}/teacher/profile", 'route' => 'teacher.profile'],
            ['name' => 'Profile Edit', 'url' => "/{$this->subdomain}/teacher/profile/edit", 'route' => 'teacher.profile.edit'],
            ['name' => 'Earnings', 'url' => "/{$this->subdomain}/teacher/earnings", 'route' => 'teacher.earnings'],
            ['name' => 'Schedule', 'url' => "/{$this->subdomain}/teacher/schedule", 'route' => 'teacher.schedule'],
            ['name' => 'Students', 'url' => "/{$this->subdomain}/teacher/students", 'route' => 'teacher.students'],
            ['name' => 'Homework Index', 'url' => "/{$this->subdomain}/teacher/homework", 'route' => 'teacher.homework.index'],
            ['name' => 'Homework Statistics', 'url' => "/{$this->subdomain}/teacher/homework/statistics", 'route' => 'teacher.homework.statistics'],
            ['name' => 'Individual Circles', 'url' => "/{$this->subdomain}/teacher/individual-circles", 'route' => 'teacher.individual-circles.index'],
            ['name' => 'Group Circles', 'url' => "/{$this->subdomain}/teacher/group-circles", 'route' => 'teacher.group-circles.index'],
        ];

        // Get teacher profile - quran_teacher_id stores the user_id directly
        $teacherProfile = $user->quranTeacherProfile;
        if ($teacherProfile) {
            // Individual circles (quran_teacher_id = quran_teacher_profiles.id)
            $individualCircles = QuranIndividualCircle::where('quran_teacher_id', $teacherProfile->id)->limit(3)->pluck('id');
            foreach ($individualCircles as $circleId) {
                $pages[] = ['name' => "Individual Circle #{$circleId}", 'url' => "/{$this->subdomain}/individual-circles/{$circleId}", 'route' => 'individual-circles.show'];
                $pages[] = ['name' => "Individual Circle Progress #{$circleId}", 'url' => "/{$this->subdomain}/teacher/individual-circles/{$circleId}/progress", 'route' => 'teacher.individual-circles.progress'];
            }

            // Group circles (quran_teacher_id stores user_id)
            $groupCircles = QuranCircle::where('quran_teacher_id', $user->id)->limit(3)->pluck('id');
            foreach ($groupCircles as $circleId) {
                $pages[] = ['name' => "Group Circle #{$circleId}", 'url' => "/{$this->subdomain}/teacher/group-circles/{$circleId}", 'route' => 'teacher.group-circles.show'];
                $pages[] = ['name' => "Group Circle Progress #{$circleId}", 'url' => "/{$this->subdomain}/teacher/group-circles/{$circleId}/progress", 'route' => 'teacher.group-circles.progress'];
            }

            // Sessions (quran_teacher_id = quran_teacher_profiles.id)
            $sessions = QuranSession::where('quran_teacher_id', $teacherProfile->id)->limit(3)->pluck('id');
            foreach ($sessions as $sessionId) {
                $pages[] = ['name' => "Quran Session #{$sessionId}", 'url' => "/{$this->subdomain}/teacher/sessions/{$sessionId}", 'route' => 'teacher.sessions.show'];
            }
        }

        return $pages;
    }

    private function getAcademicTeacherPages(User $user): array
    {
        $pages = [
            ['name' => 'Profile', 'url' => "/{$this->subdomain}/teacher/profile", 'route' => 'teacher.profile'],
            ['name' => 'Profile Edit', 'url' => "/{$this->subdomain}/teacher/profile/edit", 'route' => 'teacher.profile.edit'],
            ['name' => 'Earnings', 'url' => "/{$this->subdomain}/teacher/earnings", 'route' => 'teacher.earnings'],
            ['name' => 'Schedule', 'url' => "/{$this->subdomain}/teacher/schedule", 'route' => 'teacher.schedule'],
            ['name' => 'Students', 'url' => "/{$this->subdomain}/teacher/students", 'route' => 'teacher.students'],
            ['name' => 'Academic Sessions', 'url' => "/{$this->subdomain}/teacher/academic-sessions", 'route' => 'teacher.academic-sessions.index'],
            ['name' => 'Academic Lessons', 'url' => "/{$this->subdomain}/teacher/academic/lessons", 'route' => 'teacher.academic.lessons.index'],
            ['name' => 'Interactive Courses', 'url' => "/{$this->subdomain}/teacher/interactive-courses", 'route' => 'teacher.interactive-courses.index'],
            ['name' => 'Homework Index', 'url' => "/{$this->subdomain}/teacher/homework", 'route' => 'teacher.homework.index'],
            ['name' => 'Homework Statistics', 'url' => "/{$this->subdomain}/teacher/homework/statistics", 'route' => 'teacher.homework.statistics'],
        ];

        // Get teacher profile
        $teacherProfile = $user->academicTeacherProfile;
        if ($teacherProfile) {
            // Academic sessions (academic_teacher_id = academic_teacher_profiles.id)
            $sessions = AcademicSession::where('academic_teacher_id', $teacherProfile->id)->limit(3)->pluck('id');
            foreach ($sessions as $sessionId) {
                $pages[] = ['name' => "Academic Session #{$sessionId}", 'url' => "/{$this->subdomain}/teacher/academic-sessions/{$sessionId}", 'route' => 'teacher.academic-sessions.show'];
            }

            // Interactive course sessions
            $interactiveSessions = InteractiveCourseSession::whereHas('course', fn($q) => $q->where('assigned_teacher_id', $teacherProfile->id))
                ->limit(3)->pluck('id');
            foreach ($interactiveSessions as $sessionId) {
                $pages[] = ['name' => "Interactive Session #{$sessionId}", 'url' => "/{$this->subdomain}/teacher/interactive-sessions/{$sessionId}", 'route' => 'teacher.interactive-sessions.show'];
            }

            // Interactive courses
            $courses = InteractiveCourse::where('assigned_teacher_id', $teacherProfile->id)->limit(3)->pluck('id');
            foreach ($courses as $courseId) {
                $pages[] = ['name' => "Interactive Course Report #{$courseId}", 'url' => "/{$this->subdomain}/teacher/interactive-courses/{$courseId}/report", 'route' => 'teacher.interactive-courses.report'];
            }

            // Academic subscriptions (teacher_id = academic_teacher_profiles.id)
            $subs = AcademicSubscription::where('teacher_id', $teacherProfile->id)->limit(3)->pluck('id');
            foreach ($subs as $subId) {
                $pages[] = ['name' => "Academic Subscription Report #{$subId}", 'url' => "/{$this->subdomain}/teacher/academic-subscriptions/{$subId}/report", 'route' => 'teacher.academic-subscriptions.report'];
            }
        }

        return $pages;
    }

    private function getAdminPages(User $user): array
    {
        // Admin pages are mostly Filament, which has its own authentication
        // Focus on web routes that admins can access
        return [
            ['name' => 'Academy Home', 'url' => "/{$this->subdomain}", 'route' => 'academy.home'],
            ['name' => 'Quran Teachers', 'url' => "/{$this->subdomain}/quran-teachers", 'route' => 'quran-teachers.index'],
            ['name' => 'Academic Teachers', 'url' => "/{$this->subdomain}/academic-teachers", 'route' => 'academic-teachers.index'],
            ['name' => 'Quran Circles', 'url' => "/{$this->subdomain}/quran-circles", 'route' => 'quran-circles.index'],
            ['name' => 'Interactive Courses', 'url' => "/{$this->subdomain}/interactive-courses", 'route' => 'interactive-courses.index'],
            ['name' => 'Courses', 'url' => "/{$this->subdomain}/courses", 'route' => 'courses.index'],
        ];
    }

    private function testPage(array $page, User $user, string $role): void
    {
        try {
            // Build proper URL with subdomain
            $url = str_replace("/{$this->subdomain}", '', $page['url']);

            // Make request
            $response = $this->makeRequest($url);

            $statusCode = $response['status'];
            $isError = $statusCode >= 400;

            if ($isError) {
                $this->errors[] = [
                    'role' => $role,
                    'user_id' => $user->id,
                    'page' => $page['name'],
                    'url' => $page['url'],
                    'route' => $page['route'] ?? 'unknown',
                    'status' => $statusCode,
                    'error' => $response['error'] ?? null,
                ];
            } else {
                $this->successes[] = [
                    'role' => $role,
                    'page' => $page['name'],
                    'url' => $page['url'],
                    'status' => $statusCode,
                ];
            }
        } catch (\Exception $e) {
            $this->errors[] = [
                'role' => $role,
                'user_id' => $user->id,
                'page' => $page['name'],
                'url' => $page['url'],
                'route' => $page['route'] ?? 'unknown',
                'status' => 500,
                'error' => $e->getMessage(),
            ];
        }
    }

    private function makeRequest(string $url): array
    {
        try {
            // Use Laravel's testing capabilities
            $response = $this->laravel->handle(
                \Illuminate\Http\Request::create(
                    "http://{$this->subdomain}." . config('app.domain') . $url,
                    'GET',
                    [],
                    [],
                    [],
                    [
                        'HTTP_HOST' => "{$this->subdomain}." . config('app.domain'),
                    ]
                )
            );

            return [
                'status' => $response->getStatusCode(),
                'error' => $response->getStatusCode() >= 400 ? $this->extractError($response) : null,
            ];
        } catch (\Symfony\Component\HttpKernel\Exception\HttpException $e) {
            return [
                'status' => $e->getStatusCode(),
                'error' => $e->getMessage(),
            ];
        } catch (\Exception $e) {
            return [
                'status' => 500,
                'error' => $e->getMessage(),
            ];
        }
    }

    private function extractError($response): ?string
    {
        $content = $response->getContent();

        // Try to extract error message from HTML
        if (preg_match('/<title>([^<]+)<\/title>/i', $content, $matches)) {
            return trim($matches[1]);
        }

        // Try JSON
        $json = json_decode($content, true);
        if ($json && isset($json['message'])) {
            return $json['message'];
        }

        return substr($content, 0, 200);
    }

    private function displaySummary(): void
    {
        $this->newLine();
        $this->info('=============================================================');
        $this->info('📊 TEST RESULTS SUMMARY');
        $this->info('=============================================================');
        $this->newLine();

        // Group errors by role
        $errorsByRole = collect($this->errors)->groupBy('role');
        $successesByRole = collect($this->successes)->groupBy('role');

        foreach ($errorsByRole as $role => $errors) {
            $successCount = isset($successesByRole[$role]) ? $successesByRole[$role]->count() : 0;
            $errorCount = $errors->count();

            $this->error("❌ {$role}: {$errorCount} errors, {$successCount} successes");

            // Show error details
            $this->table(
                ['Page', 'URL', 'Status', 'Error'],
                $errors->map(fn($e) => [
                    $e['page'],
                    strlen($e['url']) > 50 ? substr($e['url'], 0, 47) . '...' : $e['url'],
                    $e['status'],
                    strlen($e['error'] ?? '') > 40 ? substr($e['error'], 0, 37) . '...' : ($e['error'] ?? 'N/A'),
                ])->toArray()
            );
            $this->newLine();
        }

        // Show roles with no errors
        foreach ($successesByRole as $role => $successes) {
            if (!isset($errorsByRole[$role])) {
                $this->info("✅ {$role}: 0 errors, {$successes->count()} successes");
            }
        }

        $this->newLine();
        $totalErrors = count($this->errors);
        $totalSuccesses = count($this->successes);

        if ($totalErrors > 0) {
            $this->error("Total: {$totalErrors} errors found across all roles");
        } else {
            $this->info("🎉 All {$totalSuccesses} pages loaded successfully!");
        }

        // Save detailed report
        $reportPath = storage_path('logs/page-test-results.json');
        file_put_contents($reportPath, json_encode([
            'timestamp' => now()->toIso8601String(),
            'errors' => $this->errors,
            'successes' => $this->successes,
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

        $this->info("📄 Detailed report saved to: {$reportPath}");
    }
}
