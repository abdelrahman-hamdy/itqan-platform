<?php

namespace App\Console\Commands;

use App\Models\AcademicTeacherProfile;
use App\Models\QuranTeacherProfile;
use Illuminate\Console\Command;

class FixTeacherActivation extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'teachers:fix-activation {--dry-run : Show what would be updated without making changes}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Fix teacher User accounts that have activated profiles but inactive User status';

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
        $isDryRun = $this->option('dry-run');

        if ($isDryRun) {
            $this->info('DRY RUN MODE - No changes will be made');
            $this->newLine();
        }

        $this->info('Checking for teachers with activation issues...');
        $this->newLine();

        // Fix Quran Teachers
        $this->fixQuranTeachers($isDryRun);

        // Fix Academic Teachers
        $this->fixAcademicTeachers($isDryRun);

        $this->newLine();
        $this->info('Teacher activation fix completed!');

        if ($isDryRun) {
            $this->warn('This was a dry run. Run without --dry-run to apply the changes.');
        }
    }

    private function fixQuranTeachers(bool $isDryRun): void
    {
        $this->info('=== Checking Quran Teachers ===');

        // Find Quran teacher profiles whose User.active_status is false
        $problematicTeachers = QuranTeacherProfile::with('user')
            ->whereHas('user', fn ($q) => $q->where('active_status', false))
            ->get();

        if ($problematicTeachers->isEmpty()) {
            $this->info('No Quran teachers found with activation issues.');

            return;
        }

        $this->warn("Found {$problematicTeachers->count()} Quran teachers with inactive User accounts:");

        foreach ($problematicTeachers as $teacher) {
            $user = $teacher->user;
            $this->line("- Teacher: {$user->name} ({$teacher->teacher_code})");
            $this->line("  User: {$user->email} | Active: ".($user->active_status ? 'true' : 'false'));

            if (! $isDryRun) {
                $user->update(['active_status' => true]);
                $this->info('  Fixed User activation status');
            } else {
                $this->info('  Would set active_status=true');
            }
            $this->newLine();
        }
    }

    private function fixAcademicTeachers(bool $isDryRun): void
    {
        $this->info('=== Checking Academic Teachers ===');

        // Find Academic teacher profiles whose User.active_status is false
        $problematicTeachers = AcademicTeacherProfile::with('user')
            ->whereHas('user', fn ($q) => $q->where('active_status', false))
            ->get();

        if ($problematicTeachers->isEmpty()) {
            $this->info('No Academic teachers found with activation issues.');

            return;
        }

        $this->warn("Found {$problematicTeachers->count()} Academic teachers with inactive User accounts:");

        foreach ($problematicTeachers as $teacher) {
            $user = $teacher->user;
            $this->line("- Teacher: {$user->name} ({$teacher->teacher_code})");
            $this->line("  User: {$user->email} | Active: ".($user->active_status ? 'true' : 'false'));

            if (! $isDryRun) {
                $user->update(['active_status' => true]);
                $this->info('  Fixed User activation status');
            } else {
                $this->info('  Would set active_status=true');
            }
            $this->newLine();
        }
    }
}
