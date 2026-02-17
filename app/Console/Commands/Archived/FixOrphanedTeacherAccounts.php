<?php

namespace App\Console\Commands\Archived;

use Exception;
use App\Models\AcademicTeacherProfile;
use App\Models\QuranTeacherProfile;
use App\Models\User;
use Illuminate\Console\Command;

class FixOrphanedTeacherAccounts extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'teachers:fix-orphaned {--dry-run : Show what would be fixed without making changes} {--delete-users : Delete orphaned users instead of creating profiles}';

    /**
     * The console command description.
     */
    protected $description = 'Fix teacher User accounts that exist without corresponding teacher profiles';

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
        $deleteUsers = $this->option('delete-users');

        if ($isDryRun) {
            $this->info('DRY RUN MODE - No changes will be made');
            $this->newLine();
        }

        $this->info('Checking for orphaned teacher accounts...');
        $this->newLine();

        // Fix Academic Teachers
        $this->fixOrphanedAcademicTeachers($isDryRun, $deleteUsers);

        // Fix Quran Teachers
        $this->fixOrphanedQuranTeachers($isDryRun, $deleteUsers);

        $this->newLine();
        $this->info('Orphaned teacher accounts fix completed!');

        if ($isDryRun) {
            $this->warn('This was a dry run. Run without --dry-run to apply the changes.');
        }
    }

    private function fixOrphanedAcademicTeachers(bool $isDryRun, bool $deleteUsers): void
    {
        $this->info('=== Checking Academic Teachers ===');

        $orphanedUsers = User::where('user_type', 'academic_teacher')
            ->whereDoesntHave('academicTeacherProfile')
            ->get();

        if ($orphanedUsers->isEmpty()) {
            $this->info('✅ No orphaned Academic teacher users found.');

            return;
        }

        $this->warn("Found {$orphanedUsers->count()} orphaned Academic teacher users:");

        foreach ($orphanedUsers as $user) {
            $this->line("- User: {$user->email} (ID: {$user->id}, Academy: {$user->academy->name})");

            if (! $isDryRun) {
                if ($deleteUsers) {
                    // Delete the orphaned user
                    $user->delete();
                    $this->info('  🗑️ Deleted orphaned user');
                } else {
                    // Create missing profile
                    try {
                        AcademicTeacherProfile::create([
                            'user_id' => $user->id,
                            'academy_id' => $user->academy_id,
                            'email' => $user->email,
                            'first_name' => $user->first_name,
                            'last_name' => $user->last_name,
                            'phone' => $user->phone,
                            'education_level' => 'bachelor',
                            'teaching_experience_years' => 1,
                            'subject_ids' => json_encode([]), // Empty subjects
                            'grade_level_ids' => json_encode([]), // Empty grade levels
                            'session_price_individual' => 60,
                        ]);

                        // Deactivate user until approved (single source of truth)
                        $user->update(['active_status' => false]);

                        $this->info('  ✅ Created Academic Teacher Profile');
                    } catch (Exception $e) {
                        $this->error('  ❌ Failed to create profile: '.$e->getMessage());
                    }
                }
            } else {
                if ($deleteUsers) {
                    $this->info('  🔄 Would delete orphaned user');
                } else {
                    $this->info('  🔄 Would create Academic Teacher Profile');
                }
            }
            $this->newLine();
        }
    }

    private function fixOrphanedQuranTeachers(bool $isDryRun, bool $deleteUsers): void
    {
        $this->info('=== Checking Quran Teachers ===');

        $orphanedUsers = User::where('user_type', 'quran_teacher')
            ->whereDoesntHave('quranTeacherProfile')
            ->get();

        if ($orphanedUsers->isEmpty()) {
            $this->info('✅ No orphaned Quran teacher users found.');

            return;
        }

        $this->warn("Found {$orphanedUsers->count()} orphaned Quran teacher users:");

        foreach ($orphanedUsers as $user) {
            $this->line("- User: {$user->email} (ID: {$user->id}, Academy: {$user->academy->name})");

            if (! $isDryRun) {
                if ($deleteUsers) {
                    // Delete the orphaned user
                    $user->delete();
                    $this->info('  🗑️ Deleted orphaned user');
                } else {
                    // Create missing profile
                    try {
                        QuranTeacherProfile::create([
                            'user_id' => $user->id,
                            'academy_id' => $user->academy_id,
                            'email' => $user->email,
                            'first_name' => $user->first_name,
                            'last_name' => $user->last_name,
                            'phone' => $user->phone,
                            'educational_qualification' => 'bachelor',
                            'teaching_experience_years' => 1,
                            'session_price_individual' => 50,
                        ]);

                        // Deactivate user until approved (single source of truth)
                        $user->update(['active_status' => false]);

                        $this->info('  ✅ Created Quran Teacher Profile');
                    } catch (Exception $e) {
                        $this->error('  ❌ Failed to create profile: '.$e->getMessage());
                    }
                }
            } else {
                if ($deleteUsers) {
                    $this->info('  🔄 Would delete orphaned user');
                } else {
                    $this->info('  🔄 Would create Quran Teacher Profile');
                }
            }
            $this->newLine();
        }
    }
}
