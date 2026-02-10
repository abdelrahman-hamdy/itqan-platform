<?php

namespace App\Services;

use App\Enums\UserType;
use App\Models\AcademicSession;
use App\Models\ChatGroup;
use App\Models\ChatGroupMember;
use App\Models\InteractiveCourse;
use App\Models\QuranCircle;
use App\Models\QuranSession;
use App\Models\RecordedCourse;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class ChatGroupService
{
    /**
     * Create a chat group for a Quran Circle
     */
    public function createForQuranCircle(QuranCircle $circle): ChatGroup
    {
        return DB::transaction(function () use ($circle) {
            // Check if group already exists
            $existingGroup = ChatGroup::where('quran_circle_id', $circle->id)->first();
            if ($existingGroup) {
                return $existingGroup;
            }

            // Get the teacher (QuranCircle::teacher() returns User directly)
            $teacher = $circle->teacher;

            // Create the group
            $group = ChatGroup::create([
                'academy_id' => $circle->academy_id,
                'name' => 'حلقة '.$circle->name,
                'type' => ChatGroup::TYPE_QURAN_CIRCLE,
                'owner_id' => $teacher?->id,
                'quran_circle_id' => $circle->id,
                'metadata' => [
                    'circle_name' => $circle->name,
                    'teacher_name' => $teacher?->name ?? $teacher?->full_name,
                ],
                'is_active' => true,
            ]);

            // Add teacher as admin if exists
            if ($teacher) {
                $this->addMember($group, $teacher, ChatGroup::ROLE_ADMIN);
            }

            // Add all enrolled students as members
            // students() returns User models directly via many-to-many
            foreach ($circle->students as $studentUser) {
                $this->addMember($group, $studentUser, ChatGroup::ROLE_MEMBER);
            }

            return $group;
        });
    }

    /**
     * Create a chat group for an Individual Quran Session
     */
    public function createForQuranSession(QuranSession $session): ChatGroup
    {
        return DB::transaction(function () use ($session) {
            // Check if group already exists
            $existingGroup = ChatGroup::where('quran_session_id', $session->id)->first();
            if ($existingGroup) {
                return $existingGroup;
            }

            $teacher = $session->teacher;
            $student = $session->student;

            // Create the group
            $group = ChatGroup::create([
                'academy_id' => $session->academy_id,
                'name' => 'جلسة فردية - '.($student ? $student->user->getChatifyName() : 'طالب'),
                'type' => ChatGroup::TYPE_INDIVIDUAL_SESSION,
                'owner_id' => $teacher ? $teacher->user_id : null,
                'quran_session_id' => $session->id,
                'metadata' => [
                    'session_id' => $session->id,
                    'teacher_name' => $teacher ? $teacher->user->getChatifyName() : null,
                    'student_name' => $student ? $student->user->getChatifyName() : null,
                ],
                'is_active' => true,
            ]);

            // Add teacher as admin
            if ($teacher && $teacher->user) {
                $this->addMember($group, $teacher->user, ChatGroup::ROLE_ADMIN);
            }

            // Add student as member
            if ($student && $student->user) {
                $this->addMember($group, $student->user, ChatGroup::ROLE_MEMBER);
            }

            // Add parent as observer if exists
            if ($student && $student->parent && $student->parent->user) {
                $this->addMember($group, $student->parent->user, ChatGroup::ROLE_MEMBER, false);
            }

            return $group;
        });
    }

    /**
     * Create a chat group for an Academic Session
     */
    public function createForAcademicSession(AcademicSession $session): ChatGroup
    {
        return DB::transaction(function () use ($session) {
            // Check if group already exists
            $existingGroup = ChatGroup::where('academic_session_id', $session->id)->first();
            if ($existingGroup) {
                return $existingGroup;
            }

            $teacher = $session->teacher;
            $student = $session->student;

            // Create the group
            $group = ChatGroup::create([
                'academy_id' => $session->academy_id,
                'name' => 'جلسة أكاديمية - '.($session->subject ? $session->subject->name : 'مادة'),
                'type' => ChatGroup::TYPE_ACADEMIC_SESSION,
                'owner_id' => $teacher ? $teacher->user_id : null,
                'academic_session_id' => $session->id,
                'metadata' => [
                    'session_id' => $session->id,
                    'subject' => $session->subject ? $session->subject->name : null,
                    'teacher_name' => $teacher ? $teacher->user->getChatifyName() : null,
                    'student_name' => $student ? $student->user->getChatifyName() : null,
                ],
                'is_active' => true,
            ]);

            // Add teacher as admin
            if ($teacher && $teacher->user) {
                $this->addMember($group, $teacher->user, ChatGroup::ROLE_ADMIN);
            }

            // Add student as member
            if ($student && $student->user) {
                $this->addMember($group, $student->user, ChatGroup::ROLE_MEMBER);
            }

            return $group;
        });
    }

    /**
     * Create a chat group for an Interactive Course
     */
    public function createForInteractiveCourse(InteractiveCourse $course): ChatGroup
    {
        return DB::transaction(function () use ($course) {
            // Check if group already exists
            $existingGroup = ChatGroup::where('interactive_course_id', $course->id)->first();
            if ($existingGroup) {
                return $existingGroup;
            }

            $teacher = $course->assignedTeacher;

            // Create the group
            $group = ChatGroup::create([
                'academy_id' => $course->academy_id,
                'name' => 'دورة تفاعلية - '.$course->title,
                'type' => ChatGroup::TYPE_INTERACTIVE_COURSE,
                'owner_id' => $teacher ? $teacher->user_id : null,
                'interactive_course_id' => $course->id,
                'metadata' => [
                    'course_title' => $course->title,
                    'teacher_name' => $teacher ? $teacher->user->getChatifyName() : null,
                    'course_description' => $course->description,
                ],
                'is_active' => true,
            ]);

            // Add teacher as admin
            if ($teacher && $teacher->user) {
                $this->addMember($group, $teacher->user, ChatGroup::ROLE_ADMIN);
            }

            // Add all enrolled students as members
            // enrolledStudents() returns Enrollment models with student.user relationship
            $course->load('enrolledStudents.student.user');
            foreach ($course->enrolledStudents as $enrollment) {
                if ($enrollment->student?->user) {
                    $this->addMember($group, $enrollment->student->user, ChatGroup::ROLE_MEMBER);
                }
            }

            return $group;
        });
    }

    /**
     * Create a chat group for a Recorded Course
     */
    public function createForRecordedCourse(RecordedCourse $course): ChatGroup
    {
        return DB::transaction(function () use ($course) {
            // Check if group already exists
            $existingGroup = ChatGroup::where('recorded_course_id', $course->id)->first();
            if ($existingGroup) {
                return $existingGroup;
            }

            // For recorded courses, the academy admin is the owner
            $academy = $course->academy;
            $owner = $academy ? $academy->admin : null;

            // Create the group
            $group = ChatGroup::create([
                'academy_id' => $course->academy_id,
                'name' => 'نقاش دورة - '.$course->title,
                'type' => ChatGroup::TYPE_RECORDED_COURSE,
                'owner_id' => $owner ? $owner->id : null,
                'recorded_course_id' => $course->id,
                'metadata' => [
                    'course_title' => $course->title,
                    'course_description' => $course->description,
                ],
                'is_active' => true,
            ]);

            // Add academy admin as moderator if exists
            if ($owner) {
                $this->addMember($group, $owner, ChatGroup::ROLE_MODERATOR);
            }

            // Add all enrolled students as members
            // enrolledStudents() returns User models directly via many-to-many
            foreach ($course->enrolledStudents as $studentUser) {
                $this->addMember($group, $studentUser, ChatGroup::ROLE_MEMBER);
            }

            return $group;
        });
    }

    /**
     * Create an announcement group for academy
     */
    public function createAnnouncementGroup($academy): ChatGroup
    {
        return DB::transaction(function () use ($academy) {
            // Check if announcement group already exists
            $existingGroup = ChatGroup::where('academy_id', $academy->id)
                ->where('type', ChatGroup::TYPE_ANNOUNCEMENT)
                ->first();
            if ($existingGroup) {
                return $existingGroup;
            }

            $admin = $academy->admin;

            // Create the group
            $group = ChatGroup::create([
                'academy_id' => $academy->id,
                'name' => 'إعلانات الأكاديمية',
                'type' => ChatGroup::TYPE_ANNOUNCEMENT,
                'owner_id' => $admin ? $admin->id : null,
                'metadata' => [
                    'academy_name' => $academy->name,
                    'broadcast' => true,
                ],
                'is_active' => true,
            ]);

            // Add academy admin as admin
            if ($admin) {
                $this->addMember($group, $admin, ChatGroup::ROLE_ADMIN);
            }

            // Add all supervisors as moderators
            $supervisors = User::where('academy_id', $academy->id)
                ->where('user_type', UserType::SUPERVISOR->value)
                ->get();
            foreach ($supervisors as $supervisor) {
                $this->addMember($group, $supervisor, ChatGroup::ROLE_MODERATOR);
            }

            // Add all users in academy as members (read-only for announcement groups)
            $users = User::where('academy_id', $academy->id)
                ->whereNotIn('user_type', [UserType::SUPER_ADMIN->value])
                ->get();
            foreach ($users as $user) {
                // Skip if already added as admin or moderator
                if (! $group->hasMember($user)) {
                    $canSend = in_array($user->user_type, [UserType::ADMIN->value, UserType::SUPERVISOR->value]);
                    $this->addMember($group, $user, ChatGroup::ROLE_MEMBER, $canSend);
                }
            }

            return $group;
        });
    }

    /**
     * Add a member to a chat group
     */
    public function addMember(ChatGroup $group, User $user, string $role = ChatGroup::ROLE_MEMBER, bool $canSendMessages = true): ChatGroupMember
    {
        // Check if already a member
        $existingMember = $group->memberships()
            ->where('user_id', $user->id)
            ->first();
        if ($existingMember) {
            return $existingMember;
        }

        return ChatGroupMember::create([
            'group_id' => $group->id,
            'user_id' => $user->id,
            'role' => $role,
            'can_send_messages' => $canSendMessages,
            'joined_at' => now(),
        ]);
    }

    /**
     * Remove a member from a chat group
     */
    public function removeMember(ChatGroup $group, User $user): bool
    {
        return $group->memberships()
            ->where('user_id', $user->id)
            ->delete();
    }

    /**
     * Update member role in a chat group
     */
    public function updateMemberRole(ChatGroup $group, User $user, string $newRole): bool
    {
        return $group->memberships()
            ->where('user_id', $user->id)
            ->update(['role' => $newRole]);
    }

    /**
     * Sync group members based on entity enrollment
     */
    public function syncGroupMembers(ChatGroup $group): void
    {
        switch ($group->type) {
            case ChatGroup::TYPE_QURAN_CIRCLE:
                if ($group->quranCircle) {
                    $this->syncQuranCircleMembers($group);
                }
                break;
            case ChatGroup::TYPE_INTERACTIVE_COURSE:
                if ($group->interactiveCourse) {
                    $this->syncInteractiveCourseMembers($group);
                }
                break;
            case ChatGroup::TYPE_RECORDED_COURSE:
                if ($group->recordedCourse) {
                    $this->syncRecordedCourseMembers($group);
                }
                break;
        }
    }

    /**
     * Sync Quran Circle members
     */
    private function syncQuranCircleMembers(ChatGroup $group): void
    {
        $circle = $group->quranCircle;
        if (! $circle) {
            return;
        }

        // Get current member IDs
        $currentMemberIds = $group->memberships()->pluck('user_id')->toArray();

        // Get expected member IDs
        $expectedMemberIds = [];

        // Teacher
        if ($circle->teacher && $circle->teacher->user) {
            $expectedMemberIds[] = $circle->teacher->user_id;
        }

        // Students - eager load to prevent N+1
        $circle->load('students.user');
        foreach ($circle->students as $student) {
            if ($student->user) {
                $expectedMemberIds[] = $student->user_id;
            }
        }

        // Add new members
        $toAdd = array_diff($expectedMemberIds, $currentMemberIds);
        foreach ($toAdd as $userId) {
            $user = User::find($userId);
            if ($user) {
                $role = ($circle->teacher && $circle->teacher->user_id == $userId)
                        ? ChatGroup::ROLE_ADMIN
                        : ChatGroup::ROLE_MEMBER;
                $this->addMember($group, $user, $role);
            }
        }

        // Remove old members
        $toRemove = array_diff($currentMemberIds, $expectedMemberIds);
        foreach ($toRemove as $userId) {
            $user = User::find($userId);
            if ($user) {
                $this->removeMember($group, $user);
            }
        }
    }

    /**
     * Sync Interactive Course members
     */
    private function syncInteractiveCourseMembers(ChatGroup $group): void
    {
        $course = $group->interactiveCourse;
        if (! $course) {
            return;
        }

        // TODO: Implement similar logic to syncQuranCircleMembers
        // Get current members, expected members, add new, remove old
        logger()->debug('syncInteractiveCourseMembers not yet implemented', ['group_id' => $group->id]);
    }

    /**
     * Sync Recorded Course members
     */
    private function syncRecordedCourseMembers(ChatGroup $group): void
    {
        $course = $group->recordedCourse;
        if (! $course) {
            return;
        }

        // TODO: Implement similar logic to syncQuranCircleMembers
        // Get current members, expected members, add new, remove old
        logger()->debug('syncRecordedCourseMembers not yet implemented', ['group_id' => $group->id]);
    }
}
