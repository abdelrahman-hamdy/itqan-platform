<?php

namespace App\Console\Commands;

use App\Enums\NotificationType;
use App\Enums\UserType;
use App\Models\User;
use App\Services\NotificationService;
use Illuminate\Console\Command;

class TestNotifications extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'notifications:test {user_id?} {--type=all}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Test the notification system by sending sample notifications';

    /**
     * Hide this command in production environments.
     */
    public function isHidden(): bool
    {
        return app()->environment('production');
    }

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $userId = $this->argument('user_id');
        $type = $this->option('type');

        // Get user
        if ($userId) {
            $user = User::find($userId);
            if (! $user) {
                $this->error("User with ID {$userId} not found.");

                return 1;
            }
        } else {
            // Get the first student user
            $user = User::where('user_type', UserType::STUDENT->value)->first();
            if (! $user) {
                $this->error('No student user found in the system.');

                return 1;
            }
        }

        $this->info("Testing notifications for user: {$user->full_name} (ID: {$user->id})");

        $notificationService = app(NotificationService::class);

        // Test different notification types
        $notifications = [];

        if ($type === 'all' || $type === 'session') {
            $notifications[] = [
                'type' => NotificationType::SESSION_SCHEDULED,
                'data' => [
                    'session_title' => 'جلسة تجريبية',
                    'teacher_name' => 'الأستاذ أحمد',
                    'start_time' => now()->addDays(1)->format('Y-m-d H:i'),
                    'session_type' => 'QuranSession',
                ],
                'url' => '/student/sessions',
            ];

            $notifications[] = [
                'type' => NotificationType::SESSION_REMINDER,
                'data' => [
                    'session_title' => 'جلسة قرآنية',
                    'minutes' => 30,
                    'start_time' => now()->addMinutes(30)->format('H:i'),
                ],
                'url' => '/student/sessions',
            ];
        }

        if ($type === 'all' || $type === 'homework') {
            $notifications[] = [
                'type' => NotificationType::HOMEWORK_ASSIGNED,
                'data' => [
                    'session_title' => 'حفظ سورة البقرة',
                    'teacher_name' => 'الأستاذ محمد',
                    'due_date' => now()->addDays(3)->format('Y-m-d'),
                ],
                'url' => '/student/homework',
            ];
        }

        if ($type === 'all' || $type === 'payment') {
            $notifications[] = [
                'type' => NotificationType::PAYMENT_SUCCESS,
                'data' => [
                    'amount' => '500',
                    'currency' => 'SAR',
                    'description' => 'اشتراك شهري',
                ],
                'url' => '/student/payments',
            ];
        }

        if ($type === 'all' || $type === 'progress') {
            $notifications[] = [
                'type' => NotificationType::PROGRESS_REPORT_AVAILABLE,
                'data' => [
                    'period' => 'الشهر الماضي',
                ],
                'url' => '/student/progress',
            ];
        }

        // Send all notifications
        foreach ($notifications as $notification) {
            $this->info("Sending {$notification['type']->value} notification...");

            $notificationService->send(
                $user,
                $notification['type'],
                $notification['data'],
                $notification['url'],
                ['test' => true],
                $type === 'important'
            );

            $this->line('✓ Sent successfully');
        }

        $this->newLine();
        $this->info('Test completed! Sent '.count($notifications).' notifications.');
        $this->line("Check the user's notification panel to see the notifications.");

        return 0;
    }
}
