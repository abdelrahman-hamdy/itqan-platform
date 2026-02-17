<?php

namespace App\Console\Commands\Archived;

use Exception;
use App\Models\QuranIndividualCircle;
use Illuminate\Console\Command;

class FixIndividualCircleTeacherIds extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'quran:fix-individual-circle-teacher-ids';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Fix individual circles that have teacher profile IDs instead of user IDs';

    /**
     * Hide this command in production - one-time fix only.
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
        $this->info('Starting to fix individual circle teacher IDs...');

        $circles = QuranIndividualCircle::with('subscription.quranTeacher', 'sessions')->get();
        $circlesFixed = 0;
        $sessionsFixed = 0;
        $errors = 0;

        foreach ($circles as $circle) {
            try {
                $subscription = $circle->subscription;
                if (! $subscription || ! $subscription->quranTeacher) {
                    $this->warn("Circle {$circle->id}: No subscription or teacher profile found");
                    $errors++;

                    continue;
                }

                $teacherProfile = $subscription->quranTeacher;
                $correctUserId = $teacherProfile->user_id;

                if (! $correctUserId) {
                    $this->warn("Circle {$circle->id}: Teacher profile {$teacherProfile->id} is not linked to a user");
                    $errors++;

                    continue;
                }

                // Fix circle teacher ID
                if ($circle->quran_teacher_id !== $correctUserId) {
                    $this->line("Fixing circle {$circle->id}: {$circle->quran_teacher_id} -> {$correctUserId}");
                    $circle->update(['quran_teacher_id' => $correctUserId]);
                    $circlesFixed++;
                }

                // Fix associated sessions
                $sessionsToFix = $circle->sessions()->where('quran_teacher_id', '!=', $correctUserId)->get();
                if ($sessionsToFix->count() > 0) {
                    $this->line("Fixing {$sessionsToFix->count()} sessions for circle {$circle->id}");
                    $circle->sessions()->where('quran_teacher_id', '!=', $correctUserId)
                        ->update(['quran_teacher_id' => $correctUserId]);
                    $sessionsFixed += $sessionsToFix->count();
                }

            } catch (Exception $e) {
                $this->error("Circle {$circle->id}: Error - {$e->getMessage()}");
                $errors++;
            }
        }

        $this->info("Completed! Circles fixed: {$circlesFixed}, Sessions fixed: {$sessionsFixed}, Errors: {$errors}, Total circles: {$circles->count()}");

        return 0;
    }
}
