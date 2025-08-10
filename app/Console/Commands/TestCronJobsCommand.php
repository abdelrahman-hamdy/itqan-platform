<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Jobs\PrepareUpcomingSessions;
use App\Jobs\GenerateWeeklyScheduleSessions;
use App\Jobs\CleanupExpiredTokens;
use App\Models\QuranSession;
use App\Models\GoogleToken;
use App\Models\SessionSchedule;
use Carbon\Carbon;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Log;

class TestCronJobsCommand extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'test:cron-jobs 
                            {--job=all : Which job to test (all, prepare, generate, cleanup)}
                            {--dry-run : Run without executing actual jobs}
                            {--details : Show detailed output}';

    /**
     * The console command description.
     */
    protected $description = 'Test the Google Meet integration cron jobs and verify they work correctly';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('🔄 بدء اختبار وظائف Cron Jobs للنظام...');
        $this->newLine();

        $jobType = $this->option('job');
        $dryRun = $this->option('dry-run');
        $verbose = $this->option('details');

        $results = [];

        if ($jobType === 'all' || $jobType === 'prepare') {
            $results['prepare'] = $this->testPrepareSessionsJob($dryRun, $verbose);
        }

        if ($jobType === 'all' || $jobType === 'generate') {
            $results['generate'] = $this->testGenerateSessionsJob($dryRun, $verbose);
        }

        if ($jobType === 'all' || $jobType === 'cleanup') {
            $results['cleanup'] = $this->testCleanupTokensJob($dryRun, $verbose);
        }

        $this->displayResults($results);
        
        return 0;
    }

    /**
     * Test the PrepareUpcomingSessions job
     */
    private function testPrepareSessionsJob($dryRun, $verbose): array
    {
        $this->info('📋 اختبار وظيفة تحضير الجلسات القادمة...');
        
        $result = [
            'name' => 'Prepare Upcoming Sessions',
            'status' => 'unknown',
            'message' => '',
            'details' => [],
        ];

        try {
            // Find sessions that need preparation (next 2 hours)
            $upcomingSessions = QuranSession::with(['quranSubscription.student', 'quranCircle', 'teacher'])
                ->where('status', 'scheduled')
                ->whereBetween('scheduled_at', [
                    now(),
                    now()->addHours(2)
                ])
                ->whereNull('preparation_completed_at')
                ->get();

            $result['details']['sessions_found'] = $upcomingSessions->count();
            $result['details']['sessions'] = $upcomingSessions->map(function ($session) {
                return [
                    'id' => $session->id,
                    'type' => $session->quran_subscription_id ? 'individual' : 'group',
                    'scheduled_at' => $session->scheduled_at->format('Y-m-d H:i:s'),
                    'teacher' => $session->teacher->name ?? 'N/A',
                    'has_meeting_link' => !empty($session->meeting_link),
                ];
            });

            if ($verbose) {
                $this->table(
                    ['ID', 'Type', 'Scheduled At', 'Teacher', 'Has Meeting Link'],
                    $result['details']['sessions']->map(function ($session) {
                        return [
                            $session['id'],
                            $session['type'],
                            $session['scheduled_at'],
                            $session['teacher'],
                            $session['has_meeting_link'] ? '✅' : '❌',
                        ];
                    })->toArray()
                );
            }

            if (!$dryRun && $upcomingSessions->count() > 0) {
                // Dispatch the job
                PrepareUpcomingSessions::dispatch();
                $result['message'] = "تم إرسال وظيفة تحضير {$upcomingSessions->count()} جلسة إلى طابور المعالجة";
                $result['status'] = 'dispatched';
            } else if ($upcomingSessions->count() > 0) {
                $result['message'] = "تم العثور على {$upcomingSessions->count()} جلسة تحتاج تحضير (وضع التجربة)";
                $result['status'] = 'ready';
            } else {
                $result['message'] = "لا توجد جلسات تحتاج تحضير في الوقت الحالي";
                $result['status'] = 'no_action_needed';
            }

        } catch (\Exception $e) {
            $result['status'] = 'error';
            $result['message'] = 'خطأ: ' . $e->getMessage();
            Log::error('Test prepare sessions job failed', ['error' => $e->getMessage()]);
        }

        $this->displayJobResult($result);
        return $result;
    }

    /**
     * Test the GenerateWeeklyScheduleSessions job
     */
    private function testGenerateSessionsJob($dryRun, $verbose): array
    {
        $this->info('📅 اختبار وظيفة إنشاء الجلسات الأسبوعية...');
        
        $result = [
            'name' => 'Generate Weekly Sessions',
            'status' => 'unknown',
            'message' => '',
            'details' => [],
        ];

        try {
            // Check active session schedules
            $activeSchedules = SessionSchedule::with(['quranSubscription', 'quranCircle', 'teacher'])
                ->where('is_active', true)
                ->where('start_date', '<=', now())
                ->where(function ($query) {
                    $query->whereNull('end_date')
                          ->orWhere('end_date', '>=', now());
                })
                ->get();

            $result['details']['active_schedules'] = $activeSchedules->count();
            $result['details']['schedules'] = $activeSchedules->map(function ($schedule) {
                return [
                    'id' => $schedule->id,
                    'type' => $schedule->schedule_type,
                    'teacher' => $schedule->teacher->name ?? 'N/A',
                    'recurrence' => $schedule->recurrence_pattern,
                    'start_date' => $schedule->start_date ? $schedule->start_date->format('Y-m-d') : 'N/A',
                    'end_date' => $schedule->end_date ? $schedule->end_date->format('Y-m-d') : 'Open',
                ];
            });

            if ($verbose && $activeSchedules->count() > 0) {
                $this->table(
                    ['ID', 'Type', 'Teacher', 'Recurrence', 'Start', 'End'],
                    $result['details']['schedules']->map(function ($schedule) {
                        return [
                            $schedule['id'],
                            $schedule['type'],
                            $schedule['teacher'],
                            $schedule['recurrence'],
                            $schedule['start_date'],
                            $schedule['end_date'],
                        ];
                    })->toArray()
                );
            }

            // Count existing sessions for next 2 weeks
            $existingSessions = QuranSession::whereBetween('scheduled_at', [
                now(),
                now()->addWeeks(2)
            ])->count();

            $result['details']['existing_sessions_next_2_weeks'] = $existingSessions;

            if (!$dryRun && $activeSchedules->count() > 0) {
                // Dispatch the job
                GenerateWeeklyScheduleSessions::dispatch(2); // Generate for 2 weeks
                $result['message'] = "تم إرسال وظيفة إنشاء الجلسات لـ {$activeSchedules->count()} جدولة نشطة";
                $result['status'] = 'dispatched';
            } else if ($activeSchedules->count() > 0) {
                $result['message'] = "تم العثور على {$activeSchedules->count()} جدولة نشطة (وضع التجربة)";
                $result['status'] = 'ready';
            } else {
                $result['message'] = "لا توجد جدولات نشطة لإنشاء جلسات منها";
                $result['status'] = 'no_schedules';
            }

        } catch (\Exception $e) {
            $result['status'] = 'error';
            $result['message'] = 'خطأ: ' . $e->getMessage();
            Log::error('Test generate sessions job failed', ['error' => $e->getMessage()]);
        }

        $this->displayJobResult($result);
        return $result;
    }

    /**
     * Test the CleanupExpiredTokens job
     */
    private function testCleanupTokensJob($dryRun, $verbose): array
    {
        $this->info('🧹 اختبار وظيفة تنظيف الرموز المنتهية الصلاحية...');
        
        $result = [
            'name' => 'Cleanup Expired Tokens',
            'status' => 'unknown',
            'message' => '',
            'details' => [],
        ];

        try {
            // Check for expired tokens
            $expiredTokens = GoogleToken::with('user')
                ->where('expires_at', '<', now())
                ->get();

            $totalTokens = GoogleToken::count();

            $result['details']['total_tokens'] = $totalTokens;
            $result['details']['expired_tokens'] = $expiredTokens->count();
            $result['details']['expired'] = $expiredTokens->map(function ($token) {
                return [
                    'user_id' => $token->user_id,
                    'user_name' => $token->user->name ?? 'N/A',
                    'expired_at' => $token->expires_at->format('Y-m-d H:i:s'),
                    'expired_days_ago' => $token->expires_at->diffInDays(now()),
                ];
            });

            if ($verbose && $expiredTokens->count() > 0) {
                $this->table(
                    ['User ID', 'User Name', 'Expired At', 'Days Ago'],
                    $result['details']['expired']->map(function ($token) {
                        return [
                            $token['user_id'],
                            $token['user_name'],
                            $token['expired_at'],
                            $token['expired_days_ago'],
                        ];
                    })->toArray()
                );
            }

            if (!$dryRun && ($expiredTokens->count() > 0 || $totalTokens > 0)) {
                // Dispatch the job
                CleanupExpiredTokens::dispatch();
                $result['message'] = "تم إرسال وظيفة تنظيف الرموز ({$expiredTokens->count()} منتهي الصلاحية من {$totalTokens})";
                $result['status'] = 'dispatched';
            } else if ($expiredTokens->count() > 0) {
                $result['message'] = "تم العثور على {$expiredTokens->count()} رمز منتهي الصلاحية من أصل {$totalTokens} (وضع التجربة)";
                $result['status'] = 'ready';
            } else {
                $result['message'] = "جميع الرموز ({$totalTokens}) صالحة - لا حاجة للتنظيف";
                $result['status'] = 'no_action_needed';
            }

        } catch (\Exception $e) {
            $result['status'] = 'error';
            $result['message'] = 'خطأ: ' . $e->getMessage();
            Log::error('Test cleanup tokens job failed', ['error' => $e->getMessage()]);
        }

        $this->displayJobResult($result);
        return $result;
    }

    /**
     * Display individual job result
     */
    private function displayJobResult($result)
    {
        $statusIcon = match ($result['status']) {
            'dispatched' => '✅',
            'ready' => '⚡',
            'no_action_needed' => '✓',
            'no_schedules' => '⚠️',
            'error' => '❌',
            default => '?',
        };

        $this->line("  {$statusIcon} {$result['message']}");
        $this->newLine();
    }

    /**
     * Display final results summary
     */
    private function displayResults($results)
    {
        $this->info('📊 ملخص نتائج الاختبار:');
        $this->newLine();

        $tableData = [];
        foreach ($results as $key => $result) {
            $statusIcon = match ($result['status']) {
                'dispatched' => '✅ تم الإرسال',
                'ready' => '⚡ جاهز',
                'no_action_needed' => '✓ لا حاجة لإجراء',
                'no_schedules' => '⚠️ لا توجد جدولات',
                'error' => '❌ خطأ',
                default => '? غير معروف',
            };

            $tableData[] = [
                $result['name'],
                $statusIcon,
                $result['message'],
            ];
        }

        $this->table(['Job', 'Status', 'Message'], $tableData);

        // Show queue info
        $this->info('📡 معلومات طابور المعالجة:');
        $this->line('  • للتحقق من طابور المعالجة: php artisan queue:work');
        $this->line('  • لعرض الوظائف المتعطلة: php artisan queue:failed');
        $this->line('  • لمراقبة الطابور: php artisan queue:monitor');
        
        $this->newLine();
        $this->info('🔧 لاختبار وظيفة واحدة فقط:');
        $this->line('  • php artisan test:cron-jobs --job=prepare');
        $this->line('  • php artisan test:cron-jobs --job=generate');
        $this->line('  • php artisan test:cron-jobs --job=cleanup');
        
        $this->newLine();
        $this->info('⚙️ لتشغيل المُجدوِل يدوياً:');
        $this->line('  • php artisan schedule:run');
        $this->line('  • php artisan schedule:list');
    }

    /**
     * Test if cron scheduler is working
     */
    public function testScheduler()
    {
        $this->info('🕒 اختبار المُجدوِل (Scheduler)...');
        
        try {
            // Check if any scheduled commands are due
            $schedule = app(\Illuminate\Console\Scheduling\Schedule::class);
            $events = $schedule->events();
            
            $this->line("  • عدد الوظائف المجدولة: " . count($events));
            
            foreach ($events as $event) {
                $command = $event->command ?? $event->description ?? 'Unknown';
                $expression = $event->getExpression();
                $this->line("    - {$command} ({$expression})");
            }
            
        } catch (\Exception $e) {
            $this->error('فشل في اختبار المُجدوِل: ' . $e->getMessage());
        }
    }
}