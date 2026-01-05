<?php

namespace App\Console\Commands;

use App\Enums\NotificationCategory;
use App\Enums\NotificationType;
use App\Models\User;
use App\Services\NotificationService;
use Illuminate\Console\Command;
use Illuminate\Support\Str;

/**
 * Comprehensive notification testing command.
 *
 * Tests all notification types for all roles, validates translations,
 * and checks for unprocessed placeholders.
 */
class TestAllNotifications extends Command
{
    protected $signature = 'notifications:test-all
                          {--role= : Test for specific role (student, parent, quran_teacher, academic_teacher)}
                          {--type= : Test specific notification type}
                          {--send : Actually send notifications to test users}
                          {--dry-run : Show what would be sent without sending}
                          {--details : Show detailed output including English translations}';

    protected $description = 'Test all notification types for all roles with proper placeholder substitution';

    private NotificationService $notificationService;

    private array $testData = [];

    private array $results = [
        'passed' => [],
        'failed' => [],
        'warnings' => [],
    ];

    public function __construct(NotificationService $notificationService)
    {
        parent::__construct();
        $this->notificationService = $notificationService;
    }

    public function handle(): int
    {
        $this->info('');
        $this->info('╔══════════════════════════════════════════════════════════════╗');
        $this->info('║           Notification System Comprehensive Test             ║');
        $this->info('╚══════════════════════════════════════════════════════════════╝');
        $this->info('');

        $this->initializeTestData();

        $roleFilter = $this->option('role');
        $typeFilter = $this->option('type');
        $shouldSend = $this->option('send');
        $isDryRun = $this->option('dry-run') || !$shouldSend;

        if ($isDryRun) {
            $this->warn('Running in DRY-RUN mode - notifications will NOT be sent');
            $this->info('Use --send to actually send test notifications');
            $this->info('');
        }

        // Get all notification types
        $notificationTypes = NotificationType::cases();

        if ($typeFilter) {
            $notificationTypes = array_filter($notificationTypes, function ($type) use ($typeFilter) {
                return Str::contains($type->value, $typeFilter, true);
            });
        }

        $this->info('Testing ' . count($notificationTypes) . ' notification types...');
        $this->info('');

        // Test each notification type
        foreach ($notificationTypes as $type) {
            $this->testNotificationType($type, $roleFilter, $shouldSend);
        }

        // Display results summary
        $this->displayResults();

        return count($this->results['failed']) > 0 ? self::FAILURE : self::SUCCESS;
    }

    private function initializeTestData(): void
    {
        $this->testData = [
            // Session placeholders
            'session_title' => 'جلسة حفظ القرآن',
            'session_number' => 5,
            'start_time' => now()->addHours(1)->format('H:i'),
            'new_time' => now()->addDays(1)->format('Y-m-d H:i'),
            'minutes' => 15,

            // User placeholders
            'teacher_name' => 'الأستاذ أحمد محمد',
            'student_name' => 'محمد علي',
            'participant_name' => 'أحمد خالد',
            'child_name' => 'سارة أحمد',

            // Date/Time placeholders
            'date' => now()->format('Y-m-d'),
            'scheduled_date' => now()->addDays(1)->format('Y-m-d'),
            'scheduled_time' => now()->addHours(2)->format('H:i'),
            'due_date' => now()->addDays(3)->format('Y-m-d'),
            'expiry_date' => now()->addMonths(1)->format('Y-m-d'),
            'maintenance_time' => now()->addDays(2)->format('Y-m-d H:i'),

            // Academic placeholders
            'grade' => '95%',
            'period' => 'الشهر الماضي',
            'hours' => 24,
            'student_level' => 'متوسط',

            // Payment placeholders
            'amount' => '500',
            'currency' => 'SAR',
            'description' => 'اشتراك شهري - حلقة القرآن',
            'subscription_name' => 'حلقة حفظ القرآن',
            'month' => 'ديسمبر 2025',
            'reason' => 'معلومات الحساب غير مكتملة',
            'reference' => 'TXN-123456789',

            // Meeting placeholders
            'issue_description' => 'انقطاع في الاتصال',

            // Achievement placeholders
            'achievement_name' => 'حافظ 5 أجزاء',
            'course_name' => 'تجويد القرآن الكريم',

            // Quiz placeholders
            'quiz_title' => 'اختبار التجويد',
            'score' => '85',
            'passing_score' => '70',

            // Review placeholders
            'rating' => '5',

            // Trial placeholders
            'request_code' => 'TR-123456',
        ];
    }

    private function testNotificationType(NotificationType $type, ?string $roleFilter, bool $shouldSend): void
    {
        $typeName = $type->value;
        $category = $type->getCategory();
        $categoryName = $category->value;
        // Use type-specific icon/color which may override category defaults
        $icon = $type->getIcon();
        $color = $type->getTailwindColor();

        // Determine which role this notification is for
        $targetRole = $this->determineTargetRole($type);

        if ($roleFilter && $targetRole !== $roleFilter) {
            return;
        }

        $this->line("┌─────────────────────────────────────────────────────────────");
        $this->line("│ Type: <fg=cyan>{$typeName}</>");
        $this->line("│ Category: <fg=yellow>{$categoryName}</> | Icon: {$icon} | Color: {$color}");
        $this->line("│ Target Role: <fg=green>{$targetRole}</>");

        // Get translations
        $arTitle = __("notifications.types.{$typeName}.title", [], 'ar');
        $arMessage = __("notifications.types.{$typeName}.message", [], 'ar');
        $enTitle = __("notifications.types.{$typeName}.title", [], 'en');
        $enMessage = __("notifications.types.{$typeName}.message", [], 'en');

        // Check if translation exists
        $arTitleExists = $arTitle !== "notifications.types.{$typeName}.title";
        $arMessageExists = $arMessage !== "notifications.types.{$typeName}.message";
        $enTitleExists = $enTitle !== "notifications.types.{$typeName}.title";
        $enMessageExists = $enMessage !== "notifications.types.{$typeName}.message";

        if (!$arTitleExists || !$arMessageExists) {
            $this->results['failed'][] = [
                'type' => $typeName,
                'issue' => 'Missing Arabic translation',
            ];
            $this->line("│ <fg=red>✗ Missing Arabic translation</>");
            $this->line("└─────────────────────────────────────────────────────────────");
            $this->line('');

            return;
        }

        if (!$enTitleExists || !$enMessageExists) {
            $this->results['warnings'][] = [
                'type' => $typeName,
                'issue' => 'Missing English translation',
            ];
            $this->line("│ <fg=yellow>⚠ Missing English translation</>");
        }

        // Substitute placeholders
        $arTitleRendered = $this->substitutePlaceholders($arTitle);
        $arMessageRendered = $this->substitutePlaceholders($arMessage);
        $enTitleRendered = $this->substitutePlaceholders($enTitle);
        $enMessageRendered = $this->substitutePlaceholders($enMessage);

        // Check for unprocessed placeholders
        $arHasUnprocessed = $this->hasUnprocessedPlaceholders($arTitleRendered . $arMessageRendered);
        $enHasUnprocessed = $this->hasUnprocessedPlaceholders($enTitleRendered . $enMessageRendered);

        if ($arHasUnprocessed) {
            $this->results['failed'][] = [
                'type' => $typeName,
                'issue' => 'Unprocessed Arabic placeholders: ' . $arMessageRendered,
            ];
            $this->line("│ <fg=red>✗ Unprocessed Arabic placeholders</>");
        }

        if ($enHasUnprocessed) {
            $this->results['warnings'][] = [
                'type' => $typeName,
                'issue' => 'Unprocessed English placeholders: ' . $enMessageRendered,
            ];
            $this->line("│ <fg=yellow>⚠ Unprocessed English placeholders</>");
        }

        // Display rendered messages
        $this->line("│");
        $this->line("│ <fg=white;options=bold>Arabic:</>");
        $this->line("│   Title: {$arTitleRendered}");
        $this->line("│   Message: {$arMessageRendered}");

        if ($this->option('details')) {
            $this->line("│");
            $this->line("│ <fg=white;options=bold>English:</>");
            $this->line("│   Title: {$enTitleRendered}");
            $this->line("│   Message: {$enMessageRendered}");
        }

        // Send test notification if requested
        if ($shouldSend) {
            $user = $this->getTestUser($targetRole);
            if ($user) {
                try {
                    $this->notificationService->send(
                        $user,
                        $type,
                        $this->testData,
                        '/test/notifications',
                        ['test' => true, 'notification_type' => $typeName]
                    );
                    $this->line("│ <fg=green>✓ Sent to {$user->name} ({$user->email})</>");
                } catch (\Exception $e) {
                    $this->results['failed'][] = [
                        'type' => $typeName,
                        'issue' => 'Send failed: ' . $e->getMessage(),
                    ];
                    $this->line("│ <fg=red>✗ Send failed: {$e->getMessage()}</>");
                }
            } else {
                $this->line("│ <fg=yellow>⚠ No test user found for role: {$targetRole}</>");
            }
        }

        if (!$arHasUnprocessed && !$enHasUnprocessed) {
            $this->results['passed'][] = $typeName;
            $this->line("│ <fg=green>✓ All checks passed</>");
        }

        $this->line("└─────────────────────────────────────────────────────────────");
        $this->line('');
    }

    private function determineTargetRole(NotificationType $type): string
    {
        $value = $type->value;

        // Role-specific types
        if (Str::endsWith($value, '_parent')) {
            return 'parent';
        }
        if (Str::endsWith($value, '_teacher')) {
            return 'quran_teacher';
        }
        if (Str::endsWith($value, '_student')) {
            return 'student';
        }

        // Teacher-specific notifications
        $teacherTypes = [
            'trial_request_received',
            'payout_approved',
            'payout_paid',
            'review_received',
            'meeting_room_ready',
        ];
        if (in_array($value, $teacherTypes)) {
            return 'quran_teacher';
        }

        // Default to student
        return 'student';
    }

    private function substitutePlaceholders(string $text): string
    {
        foreach ($this->testData as $key => $value) {
            $text = str_replace(":{$key}", (string) $value, $text);
        }

        return $text;
    }

    private function hasUnprocessedPlaceholders(string $text): bool
    {
        return preg_match('/:[a-z_]+/', $text) === 1;
    }

    private function getTestUser(string $role): ?User
    {
        return match ($role) {
            'student' => User::where('user_type', 'student')->first(),
            'parent' => User::where('user_type', 'parent')->first(),
            'quran_teacher' => User::whereHas('quranTeacherProfile')->first(),
            'academic_teacher' => User::whereHas('academicTeacherProfile')->first(),
            default => User::first(),
        };
    }

    private function displayResults(): void
    {
        $this->info('');
        $this->info('╔══════════════════════════════════════════════════════════════╗');
        $this->info('║                        Test Results                          ║');
        $this->info('╚══════════════════════════════════════════════════════════════╝');
        $this->info('');

        $passed = count($this->results['passed']);
        $failed = count($this->results['failed']);
        $warnings = count($this->results['warnings']);

        $this->info("<fg=green>✓ Passed: {$passed}</>");
        $this->info("<fg=red>✗ Failed: {$failed}</>");
        $this->info("<fg=yellow>⚠ Warnings: {$warnings}</>");
        $this->info('');

        if ($failed > 0) {
            $this->error('Failed Tests:');
            foreach ($this->results['failed'] as $failure) {
                $this->line("  - <fg=red>{$failure['type']}</>: {$failure['issue']}");
            }
            $this->info('');
        }

        if ($warnings > 0 && $this->option('details')) {
            $this->warn('Warnings:');
            foreach ($this->results['warnings'] as $warning) {
                $this->line("  - <fg=yellow>{$warning['type']}</>: {$warning['issue']}");
            }
            $this->info('');
        }

        if ($failed === 0) {
            $this->info('<fg=green;options=bold>All notification tests passed!</>');
        }
    }
}
