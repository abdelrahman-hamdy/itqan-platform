<?php

namespace App\Console\Commands\Archived;

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
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

/**
 * Migrate existing files to tenant-aware storage structure.
 *
 * Moves files from old paths to: tenants/{academy_id}/{original_path}
 * Updates database records with new paths.
 *
 * Usage:
 *   php artisan files:migrate-to-tenant --dry-run    # Preview changes
 *   php artisan files:migrate-to-tenant              # Execute migration
 */
class MigrateFilesToTenantStorage extends Command
{
    protected $signature = 'files:migrate-to-tenant
                            {--dry-run : Preview changes without executing}
                            {--batch-size=100 : Number of records to process per batch}
                            {--model= : Migrate specific model only (user, student, parent, quran_teacher, academic_teacher, supervisor, academy, lesson, course)}';

    protected $description = 'Migrate existing files to tenant-aware storage structure';

    /**
     * Hide this command in production - one-time migration only.
     */
    public function isHidden(): bool
    {
        return app()->environment('production');
    }

    private int $movedCount = 0;

    private int $skippedCount = 0;

    private int $errorCount = 0;

    private array $errors = [];

    private array $manifest = [];

    private bool $dryRun;

    private int $batchSize;

    public function handle(): int
    {
        $this->dryRun = $this->option('dry-run');
        $this->batchSize = (int) $this->option('batch-size');
        $specificModel = $this->option('model');

        $this->info($this->dryRun ? '🔍 DRY RUN MODE - No changes will be made' : '🚀 EXECUTING MIGRATION');
        $this->newLine();

        $models = $specificModel ? [$specificModel] : [
            'user', 'student', 'parent', 'quran_teacher',
            'academic_teacher', 'supervisor', 'academy', 'lesson', 'course',
        ];

        foreach ($models as $model) {
            $this->migrateModel($model);
        }

        $this->displaySummary();
        $this->saveManifest();

        return $this->errorCount > 0 ? Command::FAILURE : Command::SUCCESS;
    }

    private function migrateModel(string $model): void
    {
        match ($model) {
            'user' => $this->migrateUsers(),
            'student' => $this->migrateStudentProfiles(),
            'parent' => $this->migrateParentProfiles(),
            'quran_teacher' => $this->migrateQuranTeacherProfiles(),
            'academic_teacher' => $this->migrateAcademicTeacherProfiles(),
            'supervisor' => $this->migrateSupervisorProfiles(),
            'academy' => $this->migrateAcademies(),
            'lesson' => $this->migrateLessons(),
            'course' => $this->migrateRecordedCourses(),
            default => $this->error("Unknown model: {$model}"),
        };
    }

    private function migrateUsers(): void
    {
        $this->info('📁 Migrating User avatars...');

        User::withoutGlobalScopes()
            ->whereNotNull('avatar')
            ->where('avatar', 'not like', 'tenants/%')
            ->whereNotNull('academy_id')
            ->chunk($this->batchSize, function ($users) {
                foreach ($users as $user) {
                    $this->migrateFile(
                        $user,
                        'avatar',
                        $user->academy_id,
                        'avatars/users'
                    );
                }
            });
    }

    private function migrateStudentProfiles(): void
    {
        $this->info('📁 Migrating StudentProfile avatars...');

        // StudentProfile gets academy_id through user relationship
        StudentProfile::withoutGlobalScopes()
            ->with('user')
            ->whereNotNull('avatar')
            ->where('avatar', 'not like', 'tenants/%')
            ->whereHas('user', function ($query) {
                $query->whereNotNull('academy_id');
            })
            ->chunk($this->batchSize, function ($profiles) {
                foreach ($profiles as $profile) {
                    $academyId = $profile->user?->academy_id;
                    if (! $academyId) {
                        continue;
                    }
                    $this->migrateFile(
                        $profile,
                        'avatar',
                        $academyId,
                        'avatars/students'
                    );
                }
            });
    }

    private function migrateParentProfiles(): void
    {
        $this->info('📁 Migrating ParentProfile avatars...');

        ParentProfile::withoutGlobalScopes()
            ->whereNotNull('avatar')
            ->where('avatar', 'not like', 'tenants/%')
            ->whereNotNull('academy_id')
            ->chunk($this->batchSize, function ($profiles) {
                foreach ($profiles as $profile) {
                    $this->migrateFile(
                        $profile,
                        'avatar',
                        $profile->academy_id,
                        'avatars/parents'
                    );
                }
            });
    }

    private function migrateQuranTeacherProfiles(): void
    {
        $this->info('📁 Migrating QuranTeacherProfile avatars...');

        QuranTeacherProfile::withoutGlobalScopes()
            ->whereNotNull('avatar')
            ->where('avatar', 'not like', 'tenants/%')
            ->whereNotNull('academy_id')
            ->chunk($this->batchSize, function ($profiles) {
                foreach ($profiles as $profile) {
                    $this->migrateFile(
                        $profile,
                        'avatar',
                        $profile->academy_id,
                        'avatars/quran-teachers'
                    );
                }
            });
    }

    private function migrateAcademicTeacherProfiles(): void
    {
        $this->info('📁 Migrating AcademicTeacherProfile avatars...');

        AcademicTeacherProfile::withoutGlobalScopes()
            ->whereNotNull('avatar')
            ->where('avatar', 'not like', 'tenants/%')
            ->whereNotNull('academy_id')
            ->chunk($this->batchSize, function ($profiles) {
                foreach ($profiles as $profile) {
                    $this->migrateFile(
                        $profile,
                        'avatar',
                        $profile->academy_id,
                        'avatars/academic-teachers'
                    );
                }
            });
    }

    private function migrateSupervisorProfiles(): void
    {
        $this->info('📁 Migrating SupervisorProfile avatars...');

        SupervisorProfile::withoutGlobalScopes()
            ->whereNotNull('avatar')
            ->where('avatar', 'not like', 'tenants/%')
            ->whereNotNull('academy_id')
            ->chunk($this->batchSize, function ($profiles) {
                foreach ($profiles as $profile) {
                    $this->migrateFile(
                        $profile,
                        'avatar',
                        $profile->academy_id,
                        'avatars/supervisors'
                    );
                }
            });
    }

    private function migrateAcademies(): void
    {
        $this->info('📁 Migrating Academy branding files...');

        Academy::withoutGlobalScopes()->chunk($this->batchSize, function ($academies) {
            foreach ($academies as $academy) {
                // Logo
                if ($academy->logo && ! str_starts_with($academy->logo, 'tenants/')) {
                    $this->migrateFile($academy, 'logo', $academy->id, 'branding');
                }

                // Favicon
                if ($academy->favicon && ! str_starts_with($academy->favicon, 'tenants/')) {
                    $this->migrateFile($academy, 'favicon', $academy->id, 'branding');
                }

                // Hero image
                if ($academy->hero_image && ! str_starts_with($academy->hero_image, 'tenants/')) {
                    $this->migrateFile($academy, 'hero_image', $academy->id, 'branding');
                }
            }
        });
    }

    private function migrateLessons(): void
    {
        $this->info('📁 Migrating Lesson videos and attachments...');

        Lesson::withoutGlobalScopes()
            ->with('recordedCourse')
            ->where(function ($query) {
                $query->whereNotNull('video_url')
                    ->orWhereNotNull('attachments');
            })
            ->chunk($this->batchSize, function ($lessons) {
                foreach ($lessons as $lesson) {
                    $academyId = $lesson->recordedCourse?->academy_id;
                    if (! $academyId) {
                        continue;
                    }

                    // Video
                    if ($lesson->video_url && ! str_starts_with($lesson->video_url, 'tenants/')) {
                        $this->migrateFile($lesson, 'video_url', $academyId, 'lessons/videos');
                    }

                    // Attachments (JSON array)
                    if ($lesson->attachments && is_array($lesson->attachments)) {
                        $this->migrateJsonArrayField($lesson, 'attachments', $academyId, 'lessons/attachments');
                    }
                }
            });
    }

    private function migrateRecordedCourses(): void
    {
        $this->info('📁 Migrating RecordedCourse thumbnails...');

        RecordedCourse::withoutGlobalScopes()
            ->whereNotNull('thumbnail_url')
            ->where('thumbnail_url', 'not like', 'tenants/%')
            ->whereNotNull('academy_id')
            ->chunk($this->batchSize, function ($courses) {
                foreach ($courses as $course) {
                    $this->migrateFile(
                        $course,
                        'thumbnail_url',
                        $course->academy_id,
                        'courses/thumbnails'
                    );
                }
            });
    }

    /**
     * Migrate a single file field.
     */
    private function migrateFile($model, string $field, int $academyId, string $baseDirectory): void
    {
        $oldPath = $model->{$field};

        if (! $oldPath || str_starts_with($oldPath, 'tenants/')) {
            $this->skippedCount++;

            return;
        }

        $filename = basename($oldPath);
        $newPath = "tenants/{$academyId}/{$baseDirectory}/{$filename}";

        if ($this->dryRun) {
            $this->line("  [DRY] Would move: {$oldPath} -> {$newPath}");
            $this->manifest[] = [
                'model' => get_class($model),
                'id' => $model->id,
                'field' => $field,
                'old_path' => $oldPath,
                'new_path' => $newPath,
                'action' => 'pending',
            ];
            $this->movedCount++;

            return;
        }

        try {
            DB::transaction(function () use ($model, $field, $oldPath, $newPath) {
                $disk = Storage::disk('public');

                // Check if source file exists
                if (! $disk->exists($oldPath)) {
                    throw new \Exception("Source file not found: {$oldPath}");
                }

                // Ensure target directory exists
                $targetDir = dirname($newPath);
                if (! $disk->exists($targetDir)) {
                    $disk->makeDirectory($targetDir);
                }

                // Move the file
                $disk->move($oldPath, $newPath);

                // Update database
                $model->update([$field => $newPath]);

                $this->manifest[] = [
                    'model' => get_class($model),
                    'id' => $model->id,
                    'field' => $field,
                    'old_path' => $oldPath,
                    'new_path' => $newPath,
                    'action' => 'completed',
                ];
            });

            $this->line("  ✓ Moved: {$oldPath} -> {$newPath}");
            $this->movedCount++;

        } catch (\Exception $e) {
            $this->error("  ✗ Error: {$oldPath} - {$e->getMessage()}");
            $this->errors[] = [
                'model' => get_class($model),
                'id' => $model->id,
                'field' => $field,
                'path' => $oldPath,
                'error' => $e->getMessage(),
            ];
            $this->errorCount++;
        }
    }

    /**
     * Migrate a JSON array field containing multiple file paths.
     */
    private function migrateJsonArrayField($model, string $field, int $academyId, string $baseDirectory): void
    {
        $oldPaths = $model->{$field};
        if (! is_array($oldPaths)) {
            return;
        }

        $newPaths = [];
        $hasChanges = false;

        foreach ($oldPaths as $oldPath) {
            if (str_starts_with($oldPath, 'tenants/')) {
                $newPaths[] = $oldPath;

                continue;
            }

            $filename = basename($oldPath);
            $newPath = "tenants/{$academyId}/{$baseDirectory}/{$filename}";
            $newPaths[] = $newPath;

            if ($this->dryRun) {
                $this->line("  [DRY] Would move: {$oldPath} -> {$newPath}");
                $hasChanges = true;

                continue;
            }

            try {
                $disk = Storage::disk('public');

                if ($disk->exists($oldPath)) {
                    $targetDir = dirname($newPath);
                    if (! $disk->exists($targetDir)) {
                        $disk->makeDirectory($targetDir);
                    }
                    $disk->move($oldPath, $newPath);
                    $this->line("  ✓ Moved: {$oldPath} -> {$newPath}");
                    $hasChanges = true;
                }
            } catch (\Exception $e) {
                $this->error("  ✗ Error: {$oldPath} - {$e->getMessage()}");
                $this->errorCount++;
                $newPaths[array_key_last($newPaths)] = $oldPath; // Revert to old path on error
            }
        }

        if ($hasChanges && ! $this->dryRun) {
            $model->update([$field => $newPaths]);
        }

        if ($hasChanges) {
            $this->movedCount++;
        }
    }

    private function displaySummary(): void
    {
        $this->newLine();
        $this->info('═══════════════════════════════════════════');
        $this->info('📊 MIGRATION SUMMARY');
        $this->info('═══════════════════════════════════════════');
        $this->line("  Files migrated: {$this->movedCount}");
        $this->line("  Files skipped:  {$this->skippedCount}");
        $this->line("  Errors:         {$this->errorCount}");

        if ($this->dryRun) {
            $this->newLine();
            $this->warn('⚠️  This was a DRY RUN. No files were actually moved.');
            $this->warn('   Run without --dry-run to execute the migration.');
        }

        if (count($this->errors) > 0) {
            $this->newLine();
            $this->error('Errors encountered:');
            foreach ($this->errors as $error) {
                $this->error("  - {$error['model']}#{$error['id']}: {$error['error']}");
            }
        }
    }

    private function saveManifest(): void
    {
        $manifestPath = storage_path('logs/tenant-migration-manifest-'.date('Y-m-d-His').'.json');

        file_put_contents($manifestPath, json_encode([
            'timestamp' => now()->toIso8601String(),
            'dry_run' => $this->dryRun,
            'summary' => [
                'moved' => $this->movedCount,
                'skipped' => $this->skippedCount,
                'errors' => $this->errorCount,
            ],
            'files' => $this->manifest,
            'errors' => $this->errors,
        ], JSON_PRETTY_PRINT));

        $this->newLine();
        $this->info("📝 Manifest saved to: {$manifestPath}");
    }
}
