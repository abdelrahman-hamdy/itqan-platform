<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\User;
use App\Models\QuranTeacherProfile;
use App\Models\AcademicTeacherProfile;

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

        $problematicTeachers = QuranTeacherProfile::with('user')
            ->where('is_active', true)
            ->where('approval_status', 'approved')
            ->whereHas('user', function ($query) {
                $query->where(function ($q) {
                    $q->where('status', '!=', 'active')
                      ->orWhere('active_status', false);
                });
            })
            ->get();

        if ($problematicTeachers->isEmpty()) {
            $this->info('✅ No Quran teachers found with activation issues.');
            return;
        }

        $this->warn("Found {$problematicTeachers->count()} Quran teachers with activation issues:");
        
        foreach ($problematicTeachers as $teacher) {
            $user = $teacher->user;
            $this->line("- Teacher: {$teacher->full_name} ({$teacher->teacher_code})");
            $this->line("  User: {$user->email} | Current status: {$user->status} | Active: " . ($user->active_status ? 'true' : 'false'));
            
            if (!$isDryRun) {
                $user->update([
                    'status' => 'active',
                    'active_status' => true,
                ]);
                $this->info("  ✅ Fixed User activation status");
            } else {
                $this->info("  🔄 Would set status='active' and active_status=true");
            }
            $this->newLine();
        }
    }

    private function fixAcademicTeachers(bool $isDryRun): void
    {
        $this->info('=== Checking Academic Teachers ===');

        $problematicTeachers = AcademicTeacherProfile::with('user')
            ->where('is_active', true)
            ->where('approval_status', 'approved')
            ->whereHas('user', function ($query) {
                $query->where(function ($q) {
                    $q->where('status', '!=', 'active')
                      ->orWhere('active_status', false);
                });
            })
            ->get();

        if ($problematicTeachers->isEmpty()) {
            $this->info('✅ No Academic teachers found with activation issues.');
            return;
        }

        $this->warn("Found {$problematicTeachers->count()} Academic teachers with activation issues:");
        
        foreach ($problematicTeachers as $teacher) {
            $user = $teacher->user;
            $this->line("- Teacher: {$teacher->full_name} ({$teacher->teacher_code})");
            $this->line("  User: {$user->email} | Current status: {$user->status} | Active: " . ($user->active_status ? 'true' : 'false'));
            
            if (!$isDryRun) {
                $user->update([
                    'status' => 'active',
                    'active_status' => true,
                ]);
                $this->info("  ✅ Fixed User activation status");
            } else {
                $this->info("  🔄 Would set status='active' and active_status=true");
            }
            $this->newLine();
        }
    }
}