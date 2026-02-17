<?php

namespace App\Console\Commands\Archived;

use Exception;
use App\Models\Academy;
use App\Enums\InteractiveCourseStatus;
use App\Models\AcademicIndividualLesson;
use App\Models\ChatGroup;
use App\Models\InteractiveCourse;
use App\Models\QuranCircle;
use App\Models\QuranIndividualCircle;
use App\Models\User;
use App\Services\SupervisedChatGroupService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Namu\WireChat\Models\Conversation;

class MigrateToSupervisedChats extends Command
{
    protected $signature = 'chat:migrate-supervised
                            {--dry-run : Preview changes without making them}
                            {--academy= : Process specific academy by subdomain}
                            {--delete-private : Delete existing private teacher-student chats}
                            {--create-groups : Create supervised groups for existing subscriptions}';

    protected $description = 'Migrate chat system to supervised group chats. Deletes private teacher-student conversations and creates supervised groups.';

    /**
     * Hide this command in production - one-time migration only.
     */
    public function isHidden(): bool
    {
        return app()->environment('production');
    }

    protected SupervisedChatGroupService $chatService;

    protected bool $dryRun;

    protected int $deletedConversations = 0;

    protected int $createdGroups = 0;

    protected int $errors = 0;

    public function handle(SupervisedChatGroupService $chatService): int
    {
        $this->chatService = $chatService;
        $this->dryRun = $this->option('dry-run');

        if ($this->dryRun) {
            $this->warn('🔍 DRY RUN MODE - No changes will be made');
            $this->newLine();
        }

        $this->info('📊 Analyzing current chat state...');
        $this->analyzeCurrentState();

        if ($this->option('delete-private')) {
            $this->deletePrivateTeacherStudentChats();
        }

        if ($this->option('create-groups')) {
            $this->createSupervisedGroups();
        }

        $this->newLine();
        $this->displaySummary();

        return self::SUCCESS;
    }

    protected function analyzeCurrentState(): void
    {
        // Count teachers with/without supervisors
        $teacherUsers = User::whereIn('user_type', ['teacher', 'quran_teacher', 'academic_teacher'])->get();
        $withSupervisor = $teacherUsers->filter(fn ($u) => $u->hasSupervisor())->count();
        $withoutSupervisor = $teacherUsers->count() - $withSupervisor;

        $this->info("Teachers with supervisor: {$withSupervisor}");
        $this->warn("Teachers WITHOUT supervisor: {$withoutSupervisor}");

        // Count private conversations between teachers and students
        $privateConversationCount = $this->countPrivateTeacherStudentConversations();
        $this->info("Private teacher-student conversations: {$privateConversationCount}");

        // Count existing supervised chat groups
        $supervisedGroupCount = ChatGroup::whereNotNull('supervisor_id')->count();
        $this->info("Existing supervised chat groups: {$supervisedGroupCount}");

        // Count active subscriptions that need groups
        $activeQuranCircles = QuranCircle::where('status', 'active')->count();
        $activeIndividualCircles = QuranIndividualCircle::where('is_active', true)->count();
        $activeAcademicLessons = AcademicIndividualLesson::where('status', 'active')->count();
        $activeInteractiveCourses = InteractiveCourse::where('status', InteractiveCourseStatus::PUBLISHED)->count();

        $this->info("Active Quran group circles: {$activeQuranCircles}");
        $this->info("Active Quran individual circles: {$activeIndividualCircles}");
        $this->info("Active academic lessons: {$activeAcademicLessons}");
        $this->info("Active interactive courses: {$activeInteractiveCourses}");

        $this->newLine();
    }

    protected function countPrivateTeacherStudentConversations(): int
    {
        // Get all teacher user IDs
        $teacherIds = User::whereIn('user_type', ['teacher', 'quran_teacher', 'academic_teacher'])
            ->pluck('id')
            ->toArray();

        // Get all student user IDs
        $studentIds = User::where('user_type', 'student')
            ->pluck('id')
            ->toArray();

        if (empty($teacherIds) || empty($studentIds)) {
            return 0;
        }

        // Count private conversations between teachers and students
        // A private conversation has exactly 2 participants
        return Conversation::where('type', 'private')
            ->whereHas('participants', function ($q) use ($teacherIds) {
                $q->whereIn('participantable_id', $teacherIds)
                    ->where('participantable_type', User::class);
            })
            ->whereHas('participants', function ($q) use ($studentIds) {
                $q->whereIn('participantable_id', $studentIds)
                    ->where('participantable_type', User::class);
            })
            ->count();
    }

    protected function deletePrivateTeacherStudentChats(): void
    {
        $this->newLine();
        $this->info('🗑️ Deleting private teacher-student conversations...');

        $teacherIds = User::whereIn('user_type', ['teacher', 'quran_teacher', 'academic_teacher'])
            ->pluck('id')
            ->toArray();

        $studentIds = User::where('user_type', 'student')
            ->pluck('id')
            ->toArray();

        if (empty($teacherIds) || empty($studentIds)) {
            $this->warn('No teachers or students found.');

            return;
        }

        $conversations = Conversation::where('type', 'private')
            ->whereHas('participants', function ($q) use ($teacherIds) {
                $q->whereIn('participantable_id', $teacherIds)
                    ->where('participantable_type', User::class);
            })
            ->whereHas('participants', function ($q) use ($studentIds) {
                $q->whereIn('participantable_id', $studentIds)
                    ->where('participantable_type', User::class);
            })
            ->get();

        $this->info("Found {$conversations->count()} conversations to delete");

        $progressBar = $this->output->createProgressBar($conversations->count());

        foreach ($conversations as $conversation) {
            try {
                if (! $this->dryRun) {
                    // Delete messages first
                    $conversation->messages()->delete();
                    // Delete participants
                    $conversation->participants()->delete();
                    // Delete conversation
                    $conversation->delete();
                }
                $this->deletedConversations++;
            } catch (Exception $e) {
                $this->errors++;
                Log::error('Error deleting conversation', [
                    'conversation_id' => $conversation->id,
                    'error' => $e->getMessage(),
                ]);
            }
            $progressBar->advance();
        }

        $progressBar->finish();
        $this->newLine();
        $this->info("Deleted {$this->deletedConversations} conversations");
    }

    protected function createSupervisedGroups(): void
    {
        $this->newLine();
        $this->info('📦 Creating supervised chat groups...');

        $academy = null;
        if ($this->option('academy')) {
            $academy = Academy::where('subdomain', $this->option('academy'))->first();
            if (! $academy) {
                $this->error("Academy not found: {$this->option('academy')}");

                return;
            }
            $this->info("Processing academy: {$academy->name}");
        }

        // Create groups for Quran group circles
        $this->createQuranCircleGroups($academy);

        // Create groups for Quran individual circles
        $this->createQuranIndividualGroups($academy);

        // Create groups for academic lessons
        $this->createAcademicLessonGroups($academy);

        // Create groups for interactive courses
        $this->createInteractiveCourseGroups($academy);
    }

    protected function createQuranCircleGroups($academy): void
    {
        $this->info('Creating Quran group circle chats...');

        $query = QuranCircle::where('status', 'active');
        if ($academy) {
            $query->where('academy_id', $academy->id);
        }

        $circles = $query->with(['quranTeacher', 'students'])->get();
        $progressBar = $this->output->createProgressBar($circles->count());

        foreach ($circles as $circle) {
            try {
                if (! $this->dryRun) {
                    $group = $this->chatService->getOrCreateSupervisedQuranCircleGroup($circle);
                    if ($group) {
                        $this->createdGroups++;
                    }
                } else {
                    // Just count what would be created
                    if ($circle->quranTeacher?->hasSupervisor()) {
                        $this->createdGroups++;
                    }
                }
            } catch (Exception $e) {
                $this->errors++;
                Log::error('Error creating Quran circle group', [
                    'circle_id' => $circle->id,
                    'error' => $e->getMessage(),
                ]);
            }
            $progressBar->advance();
        }

        $progressBar->finish();
        $this->newLine();
    }

    protected function createQuranIndividualGroups($academy): void
    {
        $this->info('Creating Quran individual circle chats...');

        $query = QuranIndividualCircle::where('is_active', true);
        if ($academy) {
            $query->where('academy_id', $academy->id);
        }

        $circles = $query->with(['quranTeacher', 'student.user'])->get();
        $progressBar = $this->output->createProgressBar($circles->count());

        foreach ($circles as $circle) {
            try {
                if (! $this->dryRun) {
                    $group = $this->chatService->getOrCreateSupervisedQuranIndividualGroup($circle);
                    if ($group) {
                        $this->createdGroups++;
                    }
                } else {
                    if ($circle->quranTeacher?->hasSupervisor()) {
                        $this->createdGroups++;
                    }
                }
            } catch (Exception $e) {
                $this->errors++;
                Log::error('Error creating Quran individual group', [
                    'circle_id' => $circle->id,
                    'error' => $e->getMessage(),
                ]);
            }
            $progressBar->advance();
        }

        $progressBar->finish();
        $this->newLine();
    }

    protected function createAcademicLessonGroups($academy): void
    {
        $this->info('Creating academic lesson chats...');

        $query = AcademicIndividualLesson::where('status', 'active');
        if ($academy) {
            $query->where('academy_id', $academy->id);
        }

        $lessons = $query->with(['academicTeacher.user', 'student.user'])->get();
        $progressBar = $this->output->createProgressBar($lessons->count());

        foreach ($lessons as $lesson) {
            try {
                if (! $this->dryRun) {
                    $group = $this->chatService->getOrCreateSupervisedAcademicLessonGroup($lesson);
                    if ($group) {
                        $this->createdGroups++;
                    }
                } else {
                    if ($lesson->teacher?->user?->hasSupervisor()) {
                        $this->createdGroups++;
                    }
                }
            } catch (Exception $e) {
                $this->errors++;
                Log::error('Error creating academic lesson group', [
                    'lesson_id' => $lesson->id,
                    'error' => $e->getMessage(),
                ]);
            }
            $progressBar->advance();
        }

        $progressBar->finish();
        $this->newLine();
    }

    protected function createInteractiveCourseGroups($academy): void
    {
        $this->info('Creating interactive course chats...');

        $query = InteractiveCourse::where('status', InteractiveCourseStatus::PUBLISHED);
        if ($academy) {
            $query->where('academy_id', $academy->id);
        }

        $courses = $query->with(['assignedTeacher.user', 'enrollments.student.user'])->get();
        $progressBar = $this->output->createProgressBar($courses->count());

        foreach ($courses as $course) {
            try {
                if (! $this->dryRun) {
                    $group = $this->chatService->getOrCreateSupervisedInteractiveCourseGroup($course);
                    if ($group) {
                        $this->createdGroups++;
                    }
                } else {
                    if ($course->assignedTeacher?->user?->hasSupervisor()) {
                        $this->createdGroups++;
                    }
                }
            } catch (Exception $e) {
                $this->errors++;
                Log::error('Error creating interactive course group', [
                    'course_id' => $course->id,
                    'error' => $e->getMessage(),
                ]);
            }
            $progressBar->advance();
        }

        $progressBar->finish();
        $this->newLine();
    }

    protected function displaySummary(): void
    {
        $this->newLine();
        $this->info('═══════════════════════════════════════════');
        $this->info('                 SUMMARY                    ');
        $this->info('═══════════════════════════════════════════');

        if ($this->dryRun) {
            $this->warn('DRY RUN - No changes were made');
            $this->newLine();
        }

        if ($this->option('delete-private')) {
            $this->info("Conversations deleted: {$this->deletedConversations}");
        }

        if ($this->option('create-groups')) {
            $this->info("Chat groups created: {$this->createdGroups}");
        }

        if ($this->errors > 0) {
            $this->error("Errors encountered: {$this->errors}");
            $this->warn('Check the logs for details.');
        }

        $this->info('═══════════════════════════════════════════');

        if (! $this->dryRun && ($this->deletedConversations > 0 || $this->createdGroups > 0)) {
            $this->info('✅ Migration completed successfully!');
        }
    }
}
