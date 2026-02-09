<?php

namespace App\Console\Commands\Archived;

use App\Models\AcademicGradeLevel;
use App\Models\StudentProfile;
use Illuminate\Console\Command;

class RepairOrphanedStudentProfiles extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'students:repair-orphaned 
                            {--academy-id= : Specific academy ID to repair}
                            {--dry-run : Show what would be repaired without making changes}
                            {--assign-default : Assign orphaned students to default grade level}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Repair student profiles that have NULL grade_level_id due to deleted grade levels';

    /**
     * Hide this command in production - one-time repair only.
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
        $this->info('🔍 Scanning for orphaned student profiles...');

        $isDryRun = $this->option('dry-run');
        $academyId = $this->option('academy-id');
        $assignDefault = $this->option('assign-default');

        // Get orphaned student profiles
        $orphanedQuery = StudentProfile::whereNull('grade_level_id')
            ->with(['user.academy']);

        if ($academyId) {
            $orphanedQuery->whereHas('user', function ($query) use ($academyId) {
                $query->where('academy_id', $academyId);
            });
        }

        $orphanedProfiles = $orphanedQuery->get();

        if ($orphanedProfiles->isEmpty()) {
            $this->info('✅ No orphaned student profiles found.');

            return 0;
        }

        $this->warn("Found {$orphanedProfiles->count()} orphaned student profiles:");

        // Group by academy for organized output
        $groupedByAcademy = $orphanedProfiles->groupBy('user.academy.name');

        foreach ($groupedByAcademy as $academyName => $profiles) {
            $this->line('');
            $this->info("📚 Academy: {$academyName} ({$profiles->count()} students)");

            $academy = $profiles->first()->user->academy;

            foreach ($profiles as $profile) {
                $this->line("  👤 {$profile->full_name} ({$profile->student_code}) - User ID: {$profile->user_id}");
            }

            if ($assignDefault && ! $isDryRun) {
                $this->assignToDefaultGradeLevel($profiles, $academy);
            }
        }

        if ($isDryRun) {
            $this->line('');
            $this->comment('🧪 This was a dry run. No changes were made.');
            $this->comment('Run without --dry-run to apply fixes.');
            if (! $assignDefault) {
                $this->comment('Use --assign-default to assign students to default grade levels.');
            }
        } else {
            $this->line('');
            $this->info('✅ Repair completed!');
        }

        return 0;
    }

    /**
     * Assign orphaned students to a default grade level
     */
    private function assignToDefaultGradeLevel($profiles, $academy)
    {
        // Try to find an appropriate default grade level
        $defaultGradeLevel = AcademicGradeLevel::where('academy_id', $academy->id)
            ->where('is_active', true)
            ->orderBy('name')
            ->first();

        if (! $defaultGradeLevel) {
            // Create a default grade level if none exists
            $defaultGradeLevel = AcademicGradeLevel::create([
                'academy_id' => $academy->id,
                'name' => 'مرحلة عامة',
                'name_en' => 'General Level',
                'description' => 'مرحلة افتراضية للطلاب غير المصنفين',
                'level' => 0,
                'is_active' => true,
            ]);

            $this->info("  📝 Created default grade level: {$defaultGradeLevel->name}");
        }

        $updated = 0;
        foreach ($profiles as $profile) {
            try {
                $profile->update([
                    'grade_level_id' => $defaultGradeLevel->id,
                ]);
                $updated++;
                $this->line("    ✅ Assigned {$profile->full_name} to {$defaultGradeLevel->name}");
            } catch (\Exception $e) {
                $this->error("    ❌ Failed to assign {$profile->full_name}: {$e->getMessage()}");
            }
        }

        $this->info("  📊 Successfully assigned {$updated} students to grade level: {$defaultGradeLevel->name}");
    }
}
