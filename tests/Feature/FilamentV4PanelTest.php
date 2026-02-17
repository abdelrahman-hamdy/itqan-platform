<?php

use App\Models\User;
use App\Models\Academy;

// SuperAdmin Panel Tests
$superAdminRoutes = [
    'admin',
    'admin/academy-management',
    'admin/users',
    'admin/admins',
    'admin/student-profiles',
    'admin/parent-profiles',
    'admin/supervisor-profiles',
    'admin/academic-teacher-profiles',
    'admin/quran-teacher-profiles',
    'admin/quran-packages',
    'admin/quran-circles',
    'admin/quran-individual-circles',
    'admin/quran-subscriptions',
    'admin/quran-sessions',
    'admin/quran-trial-requests',
    'admin/academic-grade-levels',
    'admin/academic-subjects',
    'admin/academic-packages',
    'admin/academic-individual-lessons',
    'admin/interactive-courses',
    'admin/academic-subscriptions',
    'admin/academic-sessions',
    'admin/interactive-course-sessions',
    'admin/recorded-courses',
    'admin/payments',
    'admin/saved-payment-methods',
    'admin/teacher-reviews',
    'admin/teacher-earnings',
    'admin/student-session-reports',
    'admin/academic-session-reports',
    'admin/interactive-session-reports',
    'admin/meeting-attendances',
    'admin/homework-submissions',
    'admin/recorded-course-progress',
    'admin/certificates',
    'admin/quizzes',
    'admin/quiz-assignments',
    'admin/business-service-categories',
    'admin/business-service-requests',
    'admin/portfolio-items',
    'admin/platform-settings-page',
    'admin/academy-design-settings',
    'admin/log-viewer',
    'admin/academy-general-settings',
    'admin/payment-settings',
];

test('SuperAdmin panel pages do not return 500 errors', function () use ($superAdminRoutes) {
    $user = createSuperAdmin();
    enableGlobalView();

    $errors = [];
    $successes = [];

    foreach ($superAdminRoutes as $route) {
        try {
            $response = $this->actingAs($user)->get("/{$route}");
            $status = $response->getStatusCode();

            if ($status >= 500) {
                $content = $response->getContent();
                $errorMsg = '';
                if (preg_match('/class="exception-message"[^>]*>(.*?)<\//s', $content, $matches)) {
                    $errorMsg = trim(strip_tags($matches[1]));
                } elseif (preg_match('/<title>(.*?)<\/title>/s', $content, $matches)) {
                    $errorMsg = trim(strip_tags($matches[1]));
                }
                // Also check exception in response content
                if (preg_match('/exception.*?message.*?"(.*?)"/s', $content, $matches)) {
                    $errorMsg .= ' | ' . substr($matches[1], 0, 200);
                }
                $errors[] = "/{$route} => {$status} | {$errorMsg}";
            } else {
                $successes[] = "/{$route} => {$status}";
            }
        } catch (\Throwable $e) {
            $errors[] = "/{$route} => EXCEPTION: " . get_class($e) . ': ' . substr($e->getMessage(), 0, 300);
        }
    }

    // Always output results
    if (!empty($errors)) {
        $errorReport = "=== SUPERADMIN PANEL 500 ERRORS (" . count($errors) . ") ===\n" . implode("\n", $errors);
        $successReport = "\n\n=== SUCCESSES (" . count($successes) . ") ===\n" . implode("\n", $successes);
        $this->fail($errorReport . $successReport);
    }

    expect($successes)->not->toBeEmpty();
});

// Academy Panel Tests
$academyRoutes = [
    'panel',
    'panel/quran-teacher-profiles',
    'panel/academic-teacher-profiles',
    'panel/student-profiles',
    'panel/parent-profiles',
    'panel/supervisor-profiles',
    'panel/quran-packages',
    'panel/quran-sessions',
    'panel/quran-subscriptions',
    'panel/quran-individual-circles',
    'panel/academic-sessions',
    'panel/academic-subscriptions',
    'panel/academic-packages',
    'panel/academic-individual-lessons',
    'panel/interactive-course-sessions',
    'panel/recorded-courses',
    'panel/payments',
    'panel/saved-payment-methods',
    'panel/student-session-reports',
    'panel/academic-session-reports',
    'panel/interactive-session-reports',
    'panel/certificates',
    'panel/session-recordings',
    'panel/recorded-course-progress',
    'panel/course-reviews',
];

test('Academy panel pages do not return 500 errors', function () use ($academyRoutes) {
    $academy = createAcademy();
    $user = createAdmin($academy);
    setTenantContext($academy);

    $errors = [];
    $successes = [];

    foreach ($academyRoutes as $route) {
        try {
            $response = $this->actingAs($user)->get("/{$route}");
            $status = $response->getStatusCode();

            if ($status >= 500) {
                $content = $response->getContent();
                $errorMsg = '';
                if (preg_match('/class="exception-message"[^>]*>(.*?)<\//s', $content, $matches)) {
                    $errorMsg = trim(strip_tags($matches[1]));
                } elseif (preg_match('/<title>(.*?)<\/title>/s', $content, $matches)) {
                    $errorMsg = trim(strip_tags($matches[1]));
                }
                if (preg_match('/exception.*?message.*?"(.*?)"/s', $content, $matches)) {
                    $errorMsg .= ' | ' . substr($matches[1], 0, 200);
                }
                $errors[] = "/{$route} => {$status} | {$errorMsg}";
            } else {
                $successes[] = "/{$route} => {$status}";
            }
        } catch (\Throwable $e) {
            $errors[] = "/{$route} => EXCEPTION: " . get_class($e) . ': ' . substr($e->getMessage(), 0, 300);
        }
    }

    if (!empty($errors)) {
        $errorReport = "=== ACADEMY PANEL 500 ERRORS (" . count($errors) . ") ===\n" . implode("\n", $errors);
        $successReport = "\n\n=== SUCCESSES (" . count($successes) . ") ===\n" . implode("\n", $successes);
        $this->fail($errorReport . $successReport);
    }

    expect($successes)->not->toBeEmpty();
});

// Teacher Panel Tests
$teacherRoutes = [
    'teacher-panel',
    'teacher-panel/quran-circles',
    'teacher-panel/quran-individual-circles',
    'teacher-panel/quran-sessions',
    'teacher-panel/quran-trial-requests',
    'teacher-panel/student-session-reports',
    'teacher-panel/teacher-earnings',
    'teacher-panel/certificates',
];

test('Teacher panel pages do not return 500 errors', function () use ($teacherRoutes) {
    $academy = createAcademy();
    $user = createQuranTeacher($academy);
    setTenantContext($academy);

    $errors = [];
    $successes = [];

    foreach ($teacherRoutes as $route) {
        try {
            $response = $this->actingAs($user)->get("/{$route}");
            $status = $response->getStatusCode();

            if ($status >= 500) {
                $content = $response->getContent();
                $errorMsg = '';
                if (preg_match('/class="exception-message"[^>]*>(.*?)<\//s', $content, $matches)) {
                    $errorMsg = trim(strip_tags($matches[1]));
                } elseif (preg_match('/<title>(.*?)<\/title>/s', $content, $matches)) {
                    $errorMsg = trim(strip_tags($matches[1]));
                }
                if (preg_match('/exception.*?message.*?"(.*?)"/s', $content, $matches)) {
                    $errorMsg .= ' | ' . substr($matches[1], 0, 200);
                }
                $errors[] = "/{$route} => {$status} | {$errorMsg}";
            } else {
                $successes[] = "/{$route} => {$status}";
            }
        } catch (\Throwable $e) {
            $errors[] = "/{$route} => EXCEPTION: " . get_class($e) . ': ' . substr($e->getMessage(), 0, 300);
        }
    }

    if (!empty($errors)) {
        $errorReport = "=== TEACHER PANEL 500 ERRORS (" . count($errors) . ") ===\n" . implode("\n", $errors);
        $successReport = "\n\n=== SUCCESSES (" . count($successes) . ") ===\n" . implode("\n", $successes);
        $this->fail($errorReport . $successReport);
    }

    expect($successes)->not->toBeEmpty();
});

// Academic Teacher Panel Tests
$academicTeacherRoutes = [
    'academic-teacher-panel',
    'academic-teacher-panel/academic-individual-lessons',
    'academic-teacher-panel/academic-sessions',
    'academic-teacher-panel/interactive-courses',
    'academic-teacher-panel/interactive-course-sessions',
    'academic-teacher-panel/session-recordings',
    'academic-teacher-panel/academic-session-reports',
    'academic-teacher-panel/interactive-session-reports',
    'academic-teacher-panel/teacher-earnings',
    'academic-teacher-panel/certificates',
];

test('Academic Teacher panel pages do not return 500 errors', function () use ($academicTeacherRoutes) {
    $academy = createAcademy();
    $user = createAcademicTeacher($academy);
    setTenantContext($academy);

    $errors = [];
    $successes = [];

    foreach ($academicTeacherRoutes as $route) {
        try {
            $response = $this->actingAs($user)->get("/{$route}");
            $status = $response->getStatusCode();

            if ($status >= 500) {
                $content = $response->getContent();
                $errorMsg = '';
                if (preg_match('/class="exception-message"[^>]*>(.*?)<\//s', $content, $matches)) {
                    $errorMsg = trim(strip_tags($matches[1]));
                } elseif (preg_match('/<title>(.*?)<\/title>/s', $content, $matches)) {
                    $errorMsg = trim(strip_tags($matches[1]));
                }
                if (preg_match('/exception.*?message.*?"(.*?)"/s', $content, $matches)) {
                    $errorMsg .= ' | ' . substr($matches[1], 0, 200);
                }
                $errors[] = "/{$route} => {$status} | {$errorMsg}";
            } else {
                $successes[] = "/{$route} => {$status}";
            }
        } catch (\Throwable $e) {
            $errors[] = "/{$route} => EXCEPTION: " . get_class($e) . ': ' . substr($e->getMessage(), 0, 300);
        }
    }

    if (!empty($errors)) {
        $errorReport = "=== ACADEMIC TEACHER PANEL 500 ERRORS (" . count($errors) . ") ===\n" . implode("\n", $errors);
        $successReport = "\n\n=== SUCCESSES (" . count($successes) . ") ===\n" . implode("\n", $successes);
        $this->fail($errorReport . $successReport);
    }

    expect($successes)->not->toBeEmpty();
});

// Supervisor Panel Tests
$supervisorRoutes = [
    'supervisor-panel',
    'supervisor-panel/managed-teachers',
    'supervisor-panel/managed-teacher-reviews',
    'supervisor-panel/managed-teacher-earnings',
    'supervisor-panel/monitored-all-sessions',
    'supervisor-panel/monitored-individual-circles',
    'supervisor-panel/monitored-group-circles',
    'supervisor-panel/monitored-trial-requests',
    'supervisor-panel/monitored-academic-lessons',
    'supervisor-panel/monitored-interactive-courses',
    'supervisor-panel/monitored-session-reports',
    'supervisor-panel/monitored-certificates',
    'supervisor-panel/monitored-quiz-assignments',
];

test('Supervisor panel pages do not return 500 errors', function () use ($supervisorRoutes) {
    $academy = createAcademy();
    $user = createSupervisor($academy);
    setTenantContext($academy);

    $errors = [];
    $successes = [];

    foreach ($supervisorRoutes as $route) {
        try {
            $response = $this->actingAs($user)->get("/{$route}");
            $status = $response->getStatusCode();

            if ($status >= 500) {
                $content = $response->getContent();
                $errorMsg = '';
                if (preg_match('/class="exception-message"[^>]*>(.*?)<\//s', $content, $matches)) {
                    $errorMsg = trim(strip_tags($matches[1]));
                } elseif (preg_match('/<title>(.*?)<\/title>/s', $content, $matches)) {
                    $errorMsg = trim(strip_tags($matches[1]));
                }
                if (preg_match('/exception.*?message.*?"(.*?)"/s', $content, $matches)) {
                    $errorMsg .= ' | ' . substr($matches[1], 0, 200);
                }
                $errors[] = "/{$route} => {$status} | {$errorMsg}";
            } else {
                $successes[] = "/{$route} => {$status}";
            }
        } catch (\Throwable $e) {
            $errors[] = "/{$route} => EXCEPTION: " . get_class($e) . ': ' . substr($e->getMessage(), 0, 300);
        }
    }

    if (!empty($errors)) {
        $errorReport = "=== SUPERVISOR PANEL 500 ERRORS (" . count($errors) . ") ===\n" . implode("\n", $errors);
        $successReport = "\n\n=== SUCCESSES (" . count($successes) . ") ===\n" . implode("\n", $successes);
        $this->fail($errorReport . $successReport);
    }

    expect($successes)->not->toBeEmpty();
});
