<?php

namespace App\Console\Commands;

use App\Models\AcademicTeacherProfile;
use App\Models\Academy;
use App\Models\Lesson;
use App\Models\ParentProfile;
use App\Models\QuranTeacherProfile;
use App\Models\RecordedCourse;
use App\Models\StudentProfile;
use App\Models\SupervisorProfile;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

/**
 * Verify that all file paths in the database exist in storage.
 *
 * This command helps identify broken file links after the tenant migration.
 *
 * Usage:
 *   php artisan files:verify-tenant-migration         # Check all files
 *   php artisan files:verify-tenant-migration --fix   # Report only (no auto-fix)
 */
class VerifyTenantFileMigration extends Command
{
    protected $signature = 'files:verify-tenant-migration
                            {--model= : Verify specific model only}
                            {--show-valid : Also show valid files (verbose output)}';

    protected $description = 'Verify all file paths in database exist in storage';

    private int $validCount = 0;

    private int $missingCount = 0;

    private int $emptyCount = 0;

    private array $missingFiles = [];

    private array $wrongTenantFiles = [];

    public function handle(): int
    {
        $this->info('🔍 Verifying tenant file migration...');
        $this->newLine();

        $specificModel = $this->option('model');
        $showValid = $this->option('show-valid');

        $models = $specificModel ? [$specificModel] : [
            'user', 'student', 'parent', 'quran_teacher',
            'academic_teacher', 'supervisor', 'academy', 'lesson', 'course',
        ];

        foreach ($models as $model) {
            $this->verifyModel($model, $showValid);
        }

        $this->displaySummary();

        return $this->missingCount > 0 ? Command::FAILURE : Command::SUCCESS;
    }

    private function verifyModel(string $model, bool $showValid): void
    {
        match ($model) {
            'user' => $this->verifyUsers($showValid),
            'student' => $this->verifyStudentProfiles($showValid),
            'parent' => $this->verifyParentProfiles($showValid),
            'quran_teacher' => $this->verifyQuranTeacherProfiles($showValid),
            'academic_teacher' => $this->verifyAcademicTeacherProfiles($showValid),
            'supervisor' => $this->verifySupervisorProfiles($showValid),
            'academy' => $this->verifyAcademies($showValid),
            'lesson' => $this->verifyLessons($showValid),
            'course' => $this->verifyRecordedCourses($showValid),
            default => $this->error("Unknown model: {$model}"),
        };
    }

    private function verifyUsers(bool $showValid): void
    {
        $this->info('📁 Verifying User avatars...');

        User::whereNotNull('avatar')
            ->where('avatar', '!=', '')
            ->chunk(100, function ($users) use ($showValid) {
                foreach ($users as $user) {
                    $this->verifyFile(
                        $user,
                        'avatar',
                        $user->academy_id,
                        $showValid
                    );
                }
            });
    }

    private function verifyStudentProfiles(bool $showValid): void
    {
        $this->info('📁 Verifying StudentProfile avatars...');

        StudentProfile::whereNotNull('avatar')
            ->where('avatar', '!=', '')
            ->chunk(100, function ($profiles) use ($showValid) {
                foreach ($profiles as $profile) {
                    $this->verifyFile(
                        $profile,
                        'avatar',
                        $profile->academy_id,
                        $showValid
                    );
                }
            });
    }

    private function verifyParentProfiles(bool $showValid): void
    {
        $this->info('📁 Verifying ParentProfile avatars...');

        ParentProfile::whereNotNull('avatar')
            ->where('avatar', '!=', '')
            ->chunk(100, function ($profiles) use ($showValid) {
                foreach ($profiles as $profile) {
                    $this->verifyFile(
                        $profile,
                        'avatar',
                        $profile->academy_id,
                        $showValid
                    );
                }
            });
    }

    private function verifyQuranTeacherProfiles(bool $showValid): void
    {
        $this->info('📁 Verifying QuranTeacherProfile avatars...');

        QuranTeacherProfile::whereNotNull('avatar')
            ->where('avatar', '!=', '')
            ->chunk(100, function ($profiles) use ($showValid) {
                foreach ($profiles as $profile) {
                    $this->verifyFile(
                        $profile,
                        'avatar',
                        $profile->academy_id,
                        $showValid
                    );
                }
            });
    }

    private function verifyAcademicTeacherProfiles(bool $showValid): void
    {
        $this->info('📁 Verifying AcademicTeacherProfile avatars...');

        AcademicTeacherProfile::whereNotNull('avatar')
            ->where('avatar', '!=', '')
            ->chunk(100, function ($profiles) use ($showValid) {
                foreach ($profiles as $profile) {
                    $this->verifyFile(
                        $profile,
                        'avatar',
                        $profile->academy_id,
                        $showValid
                    );
                }
            });
    }

    private function verifySupervisorProfiles(bool $showValid): void
    {
        $this->info('📁 Verifying SupervisorProfile avatars...');

        SupervisorProfile::whereNotNull('avatar')
            ->where('avatar', '!=', '')
            ->chunk(100, function ($profiles) use ($showValid) {
                foreach ($profiles as $profile) {
                    $this->verifyFile(
                        $profile,
                        'avatar',
                        $profile->academy_id,
                        $showValid
                    );
                }
            });
    }

    private function verifyAcademies(bool $showValid): void
    {
        $this->info('📁 Verifying Academy branding files...');

        Academy::query()->chunk(100, function ($academies) use ($showValid) {
            foreach ($academies as $academy) {
                if ($academy->logo && $academy->logo !== '') {
                    $this->verifyFile($academy, 'logo', $academy->id, $showValid);
                }
                if ($academy->favicon && $academy->favicon !== '') {
                    $this->verifyFile($academy, 'favicon', $academy->id, $showValid);
                }
                if ($academy->hero_image && $academy->hero_image !== '') {
                    $this->verifyFile($academy, 'hero_image', $academy->id, $showValid);
                }
            }
        });
    }

    private function verifyLessons(bool $showValid): void
    {
        $this->info('📁 Verifying Lesson files...');

        Lesson::withoutGlobalScopes()
            ->with('recordedCourse')
            ->where(function ($query) {
                $query->whereNotNull('video_url')
                    ->orWhereNotNull('attachments');
            })
            ->chunk(100, function ($lessons) use ($showValid) {
                foreach ($lessons as $lesson) {
                    $academyId = $lesson->recordedCourse?->academy_id;

                    if ($lesson->video_url && $lesson->video_url !== '') {
                        $this->verifyFile($lesson, 'video_url', $academyId, $showValid);
                    }

                    if ($lesson->attachments && is_array($lesson->attachments)) {
                        foreach ($lesson->attachments as $attachment) {
                            $this->verifyFilePath(
                                get_class($lesson),
                                $lesson->id,
                                'attachments[]',
                                $attachment,
                                $academyId,
                                $showValid
                            );
                        }
                    }
                }
            });
    }

    private function verifyRecordedCourses(bool $showValid): void
    {
        $this->info('📁 Verifying RecordedCourse thumbnails...');

        RecordedCourse::whereNotNull('thumbnail_url')
            ->where('thumbnail_url', '!=', '')
            ->chunk(100, function ($courses) use ($showValid) {
                foreach ($courses as $course) {
                    $this->verifyFile(
                        $course,
                        'thumbnail_url',
                        $course->academy_id,
                        $showValid
                    );
                }
            });
    }

    private function verifyFile($model, string $field, ?int $expectedAcademyId, bool $showValid): void
    {
        $path = $model->{$field};

        $this->verifyFilePath(
            get_class($model),
            $model->id,
            $field,
            $path,
            $expectedAcademyId,
            $showValid
        );
    }

    private function verifyFilePath(
        string $modelClass,
        int $modelId,
        string $field,
        string $path,
        ?int $expectedAcademyId,
        bool $showValid
    ): void {
        if (empty($path)) {
            $this->emptyCount++;

            return;
        }

        $disk = Storage::disk('public');

        // Check if file exists
        if (! $disk->exists($path)) {
            $this->missingCount++;
            $this->missingFiles[] = [
                'model' => class_basename($modelClass),
                'id' => $modelId,
                'field' => $field,
                'path' => $path,
                'expected_academy_id' => $expectedAcademyId,
            ];
            $this->error("  ✗ Missing: {$path}");

            return;
        }

        // Check if file is in correct tenant folder
        if ($expectedAcademyId && ! str_starts_with($path, "tenants/{$expectedAcademyId}/")) {
            $this->wrongTenantFiles[] = [
                'model' => class_basename($modelClass),
                'id' => $modelId,
                'field' => $field,
                'path' => $path,
                'expected_tenant' => "tenants/{$expectedAcademyId}/",
            ];
            $this->warn("  ⚠ Wrong tenant folder: {$path} (expected tenants/{$expectedAcademyId}/)");
        }

        $this->validCount++;

        if ($showValid) {
            $this->line("  ✓ Valid: {$path}");
        }
    }

    private function displaySummary(): void
    {
        $this->newLine();
        $this->info('═══════════════════════════════════════════');
        $this->info('📊 VERIFICATION SUMMARY');
        $this->info('═══════════════════════════════════════════');
        $this->line("  Valid files:              {$this->validCount}");
        $this->line("  Missing files:            {$this->missingCount}");
        $this->line("  Empty/null paths:         {$this->emptyCount}");
        $this->line('  Wrong tenant folder:      '.count($this->wrongTenantFiles));

        if ($this->missingCount === 0 && count($this->wrongTenantFiles) === 0) {
            $this->newLine();
            $this->info('✅ All files verified successfully!');
        } else {
            if ($this->missingCount > 0) {
                $this->newLine();
                $this->error("❌ {$this->missingCount} files are missing from storage");
                $this->line('   Missing files have been logged above.');
            }

            if (count($this->wrongTenantFiles) > 0) {
                $this->newLine();
                $this->warn('⚠️  '.count($this->wrongTenantFiles).' files are not in the correct tenant folder');
                $this->line('   Run files:migrate-to-tenant to move them.');
            }
        }

        // Save report
        $reportPath = storage_path('logs/tenant-verification-'.date('Y-m-d-His').'.json');
        file_put_contents($reportPath, json_encode([
            'timestamp' => now()->toIso8601String(),
            'summary' => [
                'valid' => $this->validCount,
                'missing' => $this->missingCount,
                'empty' => $this->emptyCount,
                'wrong_tenant' => count($this->wrongTenantFiles),
            ],
            'missing_files' => $this->missingFiles,
            'wrong_tenant_files' => $this->wrongTenantFiles,
        ], JSON_PRETTY_PRINT));

        $this->newLine();
        $this->info("📝 Report saved to: {$reportPath}");
    }
}
