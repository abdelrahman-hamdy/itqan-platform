<?php

namespace App\Services;

use App\Contracts\SupervisedChatGroupServiceInterface;
use App\Models\AcademicIndividualLesson;
use App\Models\ChatGroup;
use App\Models\InteractiveCourse;
use App\Models\QuranCircle;
use App\Models\QuranIndividualCircle;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Wirechat\Wirechat\Enums\ConversationType;
use Wirechat\Wirechat\Enums\ParticipantRole;
use Wirechat\Wirechat\Models\Conversation;
use Wirechat\Wirechat\Models\Participant;

class SupervisedChatGroupService extends ChatGroupService implements SupervisedChatGroupServiceInterface
{
    protected SupervisorResolutionService $supervisorService;

    public function __construct(SupervisorResolutionService $supervisorService)
    {
        $this->supervisorService = $supervisorService;
    }

    /**
     * Check if chat functionality is available for a teacher.
     * Returns false if teacher has no supervisor assigned.
     */
    public function isChatAvailable(User $teacher): bool
    {
        return $this->supervisorService->teacherHasSupervisor($teacher);
    }

    /**
     * Get or create a supervised chat group for a Quran individual circle.
     * Includes: teacher + student + supervisor
     *
     * @return ChatGroup|null Null if no supervisor assigned
     */
    public function getOrCreateSupervisedQuranIndividualGroup(QuranIndividualCircle $individualCircle): ?ChatGroup
    {
        $teacher = $individualCircle->quranTeacher;
        $student = $individualCircle->student;

        if (! $teacher || ! $student) {
            return null;
        }

        // Get supervisor for this teacher
        $supervisor = $this->supervisorService->getSupervisorForTeacher($teacher);

        if (! $supervisor) {
            Log::debug('No supervisor assigned to teacher', ['teacher_id' => $teacher->id]);

            return null;
        }

        return DB::transaction(function () use ($individualCircle, $teacher, $student, $supervisor) {
            // Check if group already exists
            $existingGroup = ChatGroup::where('quran_individual_circle_id', $individualCircle->id)->first();

            if ($existingGroup) {
                // Ensure supervisor is current
                $this->ensureSupervisorIsCurrent($existingGroup, $supervisor);

                return $existingGroup;
            }

            // Create the group
            $group = ChatGroup::create([
                'academy_id' => $individualCircle->academy_id,
                'name' => 'حلقة فردية - '.($student->first_name ?? $student->name ?? 'طالب'),
                'type' => ChatGroup::TYPE_SUPERVISED_INDIVIDUAL,
                'owner_id' => $teacher->id,
                'supervisor_id' => $supervisor->id,
                'quran_individual_circle_id' => $individualCircle->id,
                'metadata' => [
                    'circle_code' => $individualCircle->circle_code,
                    'teacher_name' => $teacher->first_name ?? $teacher->name,
                    'student_name' => $student->first_name ?? $student->name,
                    'supervisor_name' => $supervisor->first_name ?? $supervisor->name,
                    'specialization' => $individualCircle->specialization,
                ],
                'is_active' => true,
            ]);

            // Add teacher as admin
            $this->addMember($group, $teacher, ChatGroup::ROLE_ADMIN);

            // Add student as member
            $this->addMember($group, $student, ChatGroup::ROLE_MEMBER);

            // Add all assigned supervisors as moderators + wire_participants
            $this->addAllSupervisorsToGroup($group, $teacher);

            Log::info('Created supervised Quran individual group', [
                'group_id' => $group->id,
                'circle_id' => $individualCircle->id,
                'teacher_id' => $teacher->id,
                'student_id' => $student->id,
                'supervisor_id' => $supervisor->id,
            ]);

            return $group;
        });
    }

    /**
     * Get or create a supervised chat group for an Academic individual lesson.
     * Includes: teacher + student + supervisor
     *
     * @return ChatGroup|null Null if no supervisor assigned
     */
    public function getOrCreateSupervisedAcademicLessonGroup(AcademicIndividualLesson $lesson): ?ChatGroup
    {
        $teacherProfile = $lesson->academicTeacher;
        $teacher = $teacherProfile?->user;
        $student = $lesson->student;

        if (! $teacher || ! $student) {
            return null;
        }

        // Get supervisor for this teacher
        $supervisor = $this->supervisorService->getSupervisorForTeacher($teacher);

        if (! $supervisor) {
            Log::debug('No supervisor assigned to teacher', ['teacher_id' => $teacher->id]);

            return null;
        }

        return DB::transaction(function () use ($lesson, $teacher, $student, $supervisor) {
            // Check if group already exists
            $existingGroup = ChatGroup::where('academic_individual_lesson_id', $lesson->id)->first();

            if ($existingGroup) {
                // Ensure supervisor is current
                $this->ensureSupervisorIsCurrent($existingGroup, $supervisor);

                return $existingGroup;
            }

            // Create the group
            $group = ChatGroup::create([
                'academy_id' => $lesson->academy_id,
                'name' => 'درس أكاديمي - '.($student->first_name ?? $student->name ?? 'طالب'),
                'type' => ChatGroup::TYPE_SUPERVISED_INDIVIDUAL,
                'owner_id' => $teacher->id,
                'supervisor_id' => $supervisor->id,
                'academic_individual_lesson_id' => $lesson->id,
                'metadata' => [
                    'lesson_code' => $lesson->lesson_code,
                    'teacher_name' => $teacher->first_name ?? $teacher->name,
                    'student_name' => $student->first_name ?? $student->name,
                    'supervisor_name' => $supervisor->first_name ?? $supervisor->name,
                    'subject' => $lesson->name,
                ],
                'is_active' => true,
            ]);

            // Add teacher as admin
            $this->addMember($group, $teacher, ChatGroup::ROLE_ADMIN);

            // Add student as member
            $this->addMember($group, $student, ChatGroup::ROLE_MEMBER);

            // Add all assigned supervisors as moderators + wire_participants
            $this->addAllSupervisorsToGroup($group, $teacher);

            Log::info('Created supervised Academic lesson group', [
                'group_id' => $group->id,
                'lesson_id' => $lesson->id,
                'teacher_id' => $teacher->id,
                'student_id' => $student->id,
                'supervisor_id' => $supervisor->id,
            ]);

            return $group;
        });
    }

    /**
     * Create a supervised group chat for a Quran group circle.
     * Includes: teacher + all enrolled students + supervisor
     *
     * @return ChatGroup|null Null if no supervisor assigned
     */
    public function getOrCreateSupervisedQuranCircleGroup(QuranCircle $circle): ?ChatGroup
    {
        // QuranCircle::teacher() returns User directly (not a profile)
        $teacher = $circle->teacher;

        if (! $teacher) {
            return null;
        }

        // Get supervisor for this teacher
        $supervisor = $this->supervisorService->getSupervisorForTeacher($teacher);

        if (! $supervisor) {
            Log::debug('No supervisor assigned to teacher', ['teacher_id' => $teacher->id]);

            return null;
        }

        return DB::transaction(function () use ($circle, $teacher, $supervisor) {
            // Check if group already exists
            $existingGroup = ChatGroup::where('quran_circle_id', $circle->id)->first();

            if ($existingGroup) {
                $this->ensureSupervisorIsCurrent($existingGroup, $supervisor);

                return $existingGroup;
            }

            $group = parent::createForQuranCircle($circle);
            $group->update(['supervisor_id' => $supervisor->id]);

            $this->addAllSupervisorsToGroup($group, $teacher);

            Log::info('Created supervised Quran circle group', [
                'group_id' => $group->id,
                'circle_id' => $circle->id,
                'supervisor_id' => $supervisor->id,
            ]);

            return $group;
        });
    }

    /**
     * Create a supervised group chat for an Interactive course.
     * Includes: teacher + all enrolled students + supervisor
     *
     * @return ChatGroup|null Null if no supervisor assigned
     */
    public function getOrCreateSupervisedInteractiveCourseGroup(InteractiveCourse $course): ?ChatGroup
    {
        $teacher = $course->assignedTeacher?->user;

        if (! $teacher) {
            return null;
        }

        // Get supervisor for this teacher
        $supervisor = $this->supervisorService->getSupervisorForTeacher($teacher);

        if (! $supervisor) {
            Log::debug('No supervisor assigned to teacher', ['teacher_id' => $teacher->id]);

            return null;
        }

        return DB::transaction(function () use ($course, $teacher, $supervisor) {
            $existingGroup = ChatGroup::where('interactive_course_id', $course->id)->first();

            if ($existingGroup) {
                $this->ensureSupervisorIsCurrent($existingGroup, $supervisor);

                return $existingGroup;
            }

            $group = parent::createForInteractiveCourse($course);
            $group->update(['supervisor_id' => $supervisor->id]);

            $this->addAllSupervisorsToGroup($group, $teacher);

            Log::info('Created supervised Interactive course group', [
                'group_id' => $group->id,
                'course_id' => $course->id,
                'supervisor_id' => $supervisor->id,
            ]);

            return $group;
        });
    }

    /**
     * Get or create a supervised chat group for direct teacher-student communication.
     * Used when there's no specific entity (circle/lesson) associated.
     */
    public function getOrCreateSupervisedChat(
        User $teacher,
        User $student,
        string $entityType,
        int $entityId
    ): ?ChatGroup {
        // Validate entity type
        $validTypes = ['quran_individual', 'academic_lesson', 'quran_circle', 'interactive_course'];
        if (! in_array($entityType, $validTypes)) {
            Log::warning('Invalid entity type for supervised chat', ['entity_type' => $entityType]);

            return null;
        }

        // Route to appropriate method based on entity type
        // First, find the entity and ensure it exists
        $entity = match ($entityType) {
            'quran_individual' => QuranIndividualCircle::find($entityId),
            'academic_lesson' => AcademicIndividualLesson::find($entityId),
            'quran_circle' => QuranCircle::find($entityId),
            'interactive_course' => InteractiveCourse::find($entityId),
            default => null,
        };

        if (! $entity) {
            Log::warning('Entity not found for supervised chat', [
                'entity_type' => $entityType,
                'entity_id' => $entityId,
            ]);

            return null;
        }

        $group = match ($entityType) {
            'quran_individual' => $this->getOrCreateSupervisedQuranIndividualGroup($entity),
            'academic_lesson' => $this->getOrCreateSupervisedAcademicLessonGroup($entity),
            'quran_circle' => $this->getOrCreateSupervisedQuranCircleGroup($entity),
            'interactive_course' => $this->getOrCreateSupervisedInteractiveCourseGroup($entity),
            default => null,
        };

        if ($group) {
            $this->ensureConversationExists($group, $teacher, $student, $entityType, $entityId);
        }

        return $group;
    }

    /**
     * Ensure a WireChat Conversation exists for this ChatGroup.
     * Creates the conversation and participants if missing.
     */
    protected function ensureConversationExists(
        ChatGroup $group,
        User $teacher,
        User $student,
        string $entityType,
        int $entityId
    ): void {
        // Already has a conversation linked
        if ($group->conversation_id && Conversation::find($group->conversation_id)) {
            return;
        }

        DB::transaction(function () use ($group, $teacher, $student, $entityType, $entityId) {
            $conversation = new Conversation;
            $conversation->type = ConversationType::GROUP;
            $conversation->save();

            // Create WireChat group record
            $conversation->group()->create([
                'name' => $group->name,
                'description' => 'محادثة مُشرف عليها',
            ]);

            // Add teacher as owner
            Participant::create([
                'conversation_id' => $conversation->id,
                'participantable_id' => $teacher->id,
                'participantable_type' => $teacher->getMorphClass(),
                'role' => ParticipantRole::OWNER,
            ]);

            // Collect students to add based on entity type
            $studentsToAdd = collect();

            if ($entityType === 'quran_circle') {
                $circle = QuranCircle::with('students')->find($entityId);
                if ($circle) {
                    $studentsToAdd = $circle->students;
                }
            } elseif ($entityType === 'interactive_course') {
                $course = InteractiveCourse::with('enrolledStudents.student.user')->find($entityId);
                if ($course) {
                    $studentsToAdd = $course->enrolledStudents->map(fn ($e) => $e->student?->user)->filter();
                }
            } else {
                $studentsToAdd = collect([$student]);
            }

            foreach ($studentsToAdd as $studentUser) {
                if ($studentUser && $studentUser->id !== $teacher->id) {
                    Participant::create([
                        'conversation_id' => $conversation->id,
                        'participantable_id' => $studentUser->id,
                        'participantable_type' => $studentUser->getMorphClass(),
                        'role' => ParticipantRole::PARTICIPANT,
                    ]);
                }
            }

            // Link conversation to ChatGroup first so addAllSupervisorsToGroup can sync wire_participants
            $group->update(['conversation_id' => $conversation->id]);

            // Add all assigned supervisors to both chat_group_members and wire_participants
            $this->addAllSupervisorsToGroup($group, $teacher);

            Log::info('Created WireChat conversation for supervised chat group', [
                'group_id' => $group->id,
                'conversation_id' => $conversation->id,
                'entity_type' => $entityType,
                'entity_id' => $entityId,
            ]);
        });
    }

    /**
     * Replace supervisor in a chat group.
     */
    public function replaceSupervisor(ChatGroup $group, ?User $oldSupervisor, User $newSupervisor): void
    {
        if ($oldSupervisor && $group->hasMember($oldSupervisor)) {
            $this->removeMember($group, $oldSupervisor);
        }

        if (! $group->hasMember($newSupervisor)) {
            $this->addMember($group, $newSupervisor, ChatGroup::ROLE_MODERATOR);
        }

        $group->update(['supervisor_id' => $newSupervisor->id]);

        Log::info('Replaced supervisor in chat group', [
            'group_id' => $group->id,
            'old_supervisor_id' => $oldSupervisor?->id,
            'new_supervisor_id' => $newSupervisor->id,
        ]);
    }

    /**
     * Ensure the current supervisor is correct in a chat group.
     */
    protected function ensureSupervisorIsCurrent(ChatGroup $group, User $expectedSupervisor): void
    {
        if ($group->supervisor_id !== $expectedSupervisor->id) {
            $oldSupervisor = $group->supervisor;
            $this->replaceSupervisor($group, $oldSupervisor, $expectedSupervisor);
        }
    }

    /**
     * Add all assigned supervisors to a chat group (both chat_group_members and wire_participants).
     */
    protected function addAllSupervisorsToGroup(ChatGroup $group, User $teacher): void
    {
        $allSupervisors = $this->supervisorService->getAllSupervisorsForTeacher($teacher);

        foreach ($allSupervisors as $sup) {
            $this->addMember($group, $sup, ChatGroup::ROLE_MODERATOR);

            if ($group->conversation_id) {
                Participant::firstOrCreate([
                    'conversation_id' => $group->conversation_id,
                    'participantable_id' => $sup->id,
                    'participantable_type' => $sup->getMorphClass(),
                ], [
                    'role' => ParticipantRole::ADMIN,
                ]);
            }
        }
    }

    /**
     * Add supervisor to all existing groups for a teacher.
     * Called when a supervisor is newly assigned to a teacher.
     */
    public function addSupervisorToTeacherGroups(User $teacher, User $supervisor): int
    {
        $groups = $this->supervisorService->getTeacherChatGroups($teacher);
        $count = 0;

        foreach ($groups as $group) {
            $existed = $group->hasMember($supervisor);
            $this->addMember($group, $supervisor, ChatGroup::ROLE_MODERATOR);

            if (! $existed) {
                $count++;
            }

            // Also add to wire_participants so the supervisor can see the
            // conversation in WireChat (chat_group_members alone is not enough)
            if ($group->conversation_id) {
                Participant::firstOrCreate([
                    'conversation_id' => $group->conversation_id,
                    'participantable_id' => $supervisor->id,
                    'participantable_type' => $supervisor->getMorphClass(),
                ], [
                    'role' => ParticipantRole::ADMIN,
                ]);
            }
        }

        Log::info('Added supervisor to teacher groups', [
            'teacher_id' => $teacher->id,
            'supervisor_id' => $supervisor->id,
            'groups_updated' => $count,
        ]);

        return $count;
    }
}
