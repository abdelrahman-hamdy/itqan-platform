<?php

namespace App\Console\Commands;

use App\Models\AcademicSession;
use App\Models\Academy;
use App\Models\InteractiveCourse;
use App\Models\QuranCircle;
use App\Models\QuranSession;
use App\Models\RecordedCourse;
use App\Services\ChatGroupService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class CreateChatGroupsForEntities extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'chat:create-groups 
                            {--type= : Type of entity to create groups for (circle, session, academic, interactive, recorded, announcement, all)}
                            {--academy= : Academy ID to limit groups creation to}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create chat groups for existing educational entities';

    protected $chatGroupService;

    public function __construct(ChatGroupService $chatGroupService)
    {
        parent::__construct();
        $this->chatGroupService = $chatGroupService;
    }

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $type = $this->option('type') ?? 'all';
        $academyId = $this->option('academy');

        $this->info('Creating chat groups for educational entities...');

        DB::beginTransaction();

        try {
            if ($type === 'all' || $type === 'circle') {
                $this->createQuranCircleGroups($academyId);
            }

            if ($type === 'all' || $type === 'session') {
                $this->createQuranSessionGroups($academyId);
            }

            if ($type === 'all' || $type === 'academic') {
                $this->createAcademicSessionGroups($academyId);
            }

            if ($type === 'all' || $type === 'interactive') {
                $this->createInteractiveCourseGroups($academyId);
            }

            if ($type === 'all' || $type === 'recorded') {
                $this->createRecordedCourseGroups($academyId);
            }

            if ($type === 'all' || $type === 'announcement') {
                $this->createAnnouncementGroups($academyId);
            }

            DB::commit();
            $this->info('Chat groups created successfully!');

        } catch (\Exception $e) {
            DB::rollBack();
            $this->error('Error creating chat groups: '.$e->getMessage());

            return 1;
        }

        return 0;
    }

    private function createQuranCircleGroups($academyId = null)
    {
        $this->info('Creating Quran Circle groups...');

        $query = QuranCircle::with(['teacher', 'students']);
        if ($academyId) {
            $query->where('academy_id', $academyId);
        }

        $circles = $query->get();
        $count = 0;

        foreach ($circles as $circle) {
            try {
                $group = $this->chatGroupService->createForQuranCircle($circle);
                $count++;
                $this->line("Created group for circle: {$circle->name}");
            } catch (\Exception $e) {
                $this->warn("Failed to create group for circle {$circle->name}: ".$e->getMessage());
            }
        }

        $this->info("Created {$count} Quran Circle groups");
    }

    private function createQuranSessionGroups($academyId = null)
    {
        $this->info('Creating Individual Quran Session groups...');

        $query = QuranSession::with(['teacher', 'student', 'student.parent']);
        if ($academyId) {
            $query->where('academy_id', $academyId);
        }

        $sessions = $query->get();
        $count = 0;

        foreach ($sessions as $session) {
            try {
                $group = $this->chatGroupService->createForQuranSession($session);
                $count++;
                $this->line("Created group for session ID: {$session->id}");
            } catch (\Exception $e) {
                $this->warn("Failed to create group for session {$session->id}: ".$e->getMessage());
            }
        }

        $this->info("Created {$count} Individual Quran Session groups");
    }

    private function createAcademicSessionGroups($academyId = null)
    {
        $this->info('Creating Academic Session groups...');

        $query = AcademicSession::with(['teacher', 'student', 'subject']);
        if ($academyId) {
            $query->where('academy_id', $academyId);
        }

        $sessions = $query->get();
        $count = 0;

        foreach ($sessions as $session) {
            try {
                $group = $this->chatGroupService->createForAcademicSession($session);
                $count++;
                $this->line("Created group for academic session ID: {$session->id}");
            } catch (\Exception $e) {
                $this->warn("Failed to create group for academic session {$session->id}: ".$e->getMessage());
            }
        }

        $this->info("Created {$count} Academic Session groups");
    }

    private function createInteractiveCourseGroups($academyId = null)
    {
        $this->info('Creating Interactive Course groups...');

        $query = InteractiveCourse::with(['teacher', 'enrolledStudents']);
        if ($academyId) {
            $query->where('academy_id', $academyId);
        }

        $courses = $query->get();
        $count = 0;

        foreach ($courses as $course) {
            try {
                $group = $this->chatGroupService->createForInteractiveCourse($course);
                $count++;
                $this->line("Created group for course: {$course->title}");
            } catch (\Exception $e) {
                $this->warn("Failed to create group for course {$course->title}: ".$e->getMessage());
            }
        }

        $this->info("Created {$count} Interactive Course groups");
    }

    private function createRecordedCourseGroups($academyId = null)
    {
        $this->info('Creating Recorded Course discussion groups...');

        $query = RecordedCourse::with(['enrolledStudents']);
        if ($academyId) {
            $query->where('academy_id', $academyId);
        }

        $courses = $query->get();
        $count = 0;

        foreach ($courses as $course) {
            try {
                $group = $this->chatGroupService->createForRecordedCourse($course);
                $count++;
                $this->line("Created discussion group for course: {$course->title}");
            } catch (\Exception $e) {
                $this->warn("Failed to create group for course {$course->title}: ".$e->getMessage());
            }
        }

        $this->info("Created {$count} Recorded Course discussion groups");
    }

    private function createAnnouncementGroups($academyId = null)
    {
        $this->info('Creating Academy Announcement groups...');

        $query = Academy::with(['admin']);
        if ($academyId) {
            $query->where('id', $academyId);
        }

        $academies = $query->get();
        $count = 0;

        foreach ($academies as $academy) {
            try {
                $group = $this->chatGroupService->createAnnouncementGroup($academy);
                $count++;
                $this->line("Created announcement group for academy: {$academy->name}");
            } catch (\Exception $e) {
                $this->warn("Failed to create announcement group for academy {$academy->name}: ".$e->getMessage());
            }
        }

        $this->info("Created {$count} Academy Announcement groups");
    }
}
