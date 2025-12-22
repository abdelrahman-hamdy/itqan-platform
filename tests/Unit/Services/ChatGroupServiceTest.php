<?php

use App\Models\Academy;
use App\Models\AcademicSession;
use App\Models\AcademicTeacherProfile;
use App\Models\ChatGroup;
use App\Models\ChatGroupMember;
use App\Models\InteractiveCourse;
use App\Models\QuranCircle;
use App\Models\QuranSession;
use App\Models\QuranTeacherProfile;
use App\Models\RecordedCourse;
use App\Models\StudentProfile;
use App\Models\User;
use App\Services\ChatGroupService;

describe('ChatGroupService', function () {
    beforeEach(function () {
        $this->service = new ChatGroupService();
        $this->academy = Academy::factory()->create();
    });

    describe('createForQuranCircle', function () {
        it('creates chat group for quran circle', function () {
            $teacher = User::factory()->quranTeacher()->forAcademy($this->academy)->create();
            $teacherProfile = QuranTeacherProfile::factory()->create([
                'user_id' => $teacher->id,
                'academy_id' => $this->academy->id,
            ]);

            $circle = QuranCircle::factory()->create([
                'academy_id' => $this->academy->id,
                'quran_teacher_profile_id' => $teacherProfile->id,
            ]);

            $group = $this->service->createForQuranCircle($circle);

            expect($group)->toBeInstanceOf(ChatGroup::class)
                ->and($group->quran_circle_id)->toBe($circle->id)
                ->and($group->type)->toBe(ChatGroup::TYPE_QURAN_CIRCLE)
                ->and($group->is_active)->toBeTrue();
        });

        it('returns existing group if already exists', function () {
            $teacher = User::factory()->quranTeacher()->forAcademy($this->academy)->create();
            $teacherProfile = QuranTeacherProfile::factory()->create([
                'user_id' => $teacher->id,
                'academy_id' => $this->academy->id,
            ]);

            $circle = QuranCircle::factory()->create([
                'academy_id' => $this->academy->id,
                'quran_teacher_profile_id' => $teacherProfile->id,
            ]);

            $group1 = $this->service->createForQuranCircle($circle);
            $group2 = $this->service->createForQuranCircle($circle);

            expect($group1->id)->toBe($group2->id);
        });

        it('adds teacher as admin member', function () {
            $teacher = User::factory()->quranTeacher()->forAcademy($this->academy)->create();
            $teacherProfile = QuranTeacherProfile::factory()->create([
                'user_id' => $teacher->id,
                'academy_id' => $this->academy->id,
            ]);

            $circle = QuranCircle::factory()->create([
                'academy_id' => $this->academy->id,
                'quran_teacher_profile_id' => $teacherProfile->id,
            ]);

            $group = $this->service->createForQuranCircle($circle);

            $membership = $group->memberships()
                ->where('user_id', $teacher->id)
                ->first();

            expect($membership)->not->toBeNull()
                ->and($membership->role)->toBe(ChatGroup::ROLE_ADMIN);
        });

        it('sets group name with circle name', function () {
            $teacher = User::factory()->quranTeacher()->forAcademy($this->academy)->create();
            $teacherProfile = QuranTeacherProfile::factory()->create([
                'user_id' => $teacher->id,
                'academy_id' => $this->academy->id,
            ]);

            $circle = QuranCircle::factory()->create([
                'academy_id' => $this->academy->id,
                'quran_teacher_profile_id' => $teacherProfile->id,
                'name' => 'حلقة الفجر',
            ]);

            $group = $this->service->createForQuranCircle($circle);

            expect($group->name)->toContain('حلقة الفجر');
        });
    });

    describe('createForQuranSession', function () {
        it('creates chat group for individual quran session', function () {
            $teacher = User::factory()->quranTeacher()->forAcademy($this->academy)->create();
            $teacherProfile = QuranTeacherProfile::factory()->create([
                'user_id' => $teacher->id,
                'academy_id' => $this->academy->id,
            ]);

            $student = User::factory()->student()->forAcademy($this->academy)->create();

            $session = QuranSession::factory()->create([
                'academy_id' => $this->academy->id,
                'quran_teacher_id' => $teacherProfile->id,
                'student_id' => $student->id,
                'session_type' => 'individual',
            ]);

            $group = $this->service->createForQuranSession($session);

            expect($group)->toBeInstanceOf(ChatGroup::class)
                ->and($group->quran_session_id)->toBe($session->id)
                ->and($group->type)->toBe(ChatGroup::TYPE_INDIVIDUAL_SESSION)
                ->and($group->is_active)->toBeTrue();
        });

        it('returns existing group if already exists', function () {
            $teacher = User::factory()->quranTeacher()->forAcademy($this->academy)->create();
            $teacherProfile = QuranTeacherProfile::factory()->create([
                'user_id' => $teacher->id,
                'academy_id' => $this->academy->id,
            ]);

            $student = User::factory()->student()->forAcademy($this->academy)->create();

            $session = QuranSession::factory()->create([
                'academy_id' => $this->academy->id,
                'quran_teacher_id' => $teacherProfile->id,
                'student_id' => $student->id,
            ]);

            $group1 = $this->service->createForQuranSession($session);
            $group2 = $this->service->createForQuranSession($session);

            expect($group1->id)->toBe($group2->id);
        });

        it('adds both teacher and student as members', function () {
            $teacher = User::factory()->quranTeacher()->forAcademy($this->academy)->create();
            $teacherProfile = QuranTeacherProfile::factory()->create([
                'user_id' => $teacher->id,
                'academy_id' => $this->academy->id,
            ]);

            $student = User::factory()->student()->forAcademy($this->academy)->create();

            $session = QuranSession::factory()->create([
                'academy_id' => $this->academy->id,
                'quran_teacher_id' => $teacherProfile->id,
                'student_id' => $student->id,
            ]);

            $group = $this->service->createForQuranSession($session);

            expect($group->memberships()->count())->toBeGreaterThanOrEqual(2);
        });
    });

    describe('createForAcademicSession', function () {
        it('creates chat group for academic session', function () {
            $teacher = User::factory()->academicTeacher()->forAcademy($this->academy)->create();
            $teacherProfile = AcademicTeacherProfile::factory()->create([
                'user_id' => $teacher->id,
                'academy_id' => $this->academy->id,
            ]);

            $student = User::factory()->student()->forAcademy($this->academy)->create();

            $session = AcademicSession::factory()->create([
                'academy_id' => $this->academy->id,
                'academic_teacher_id' => $teacherProfile->id,
                'student_id' => $student->id,
            ]);

            $group = $this->service->createForAcademicSession($session);

            expect($group)->toBeInstanceOf(ChatGroup::class)
                ->and($group->academic_session_id)->toBe($session->id)
                ->and($group->type)->toBe(ChatGroup::TYPE_ACADEMIC_SESSION)
                ->and($group->is_active)->toBeTrue();
        });

        it('returns existing group if already exists', function () {
            $teacher = User::factory()->academicTeacher()->forAcademy($this->academy)->create();
            $teacherProfile = AcademicTeacherProfile::factory()->create([
                'user_id' => $teacher->id,
                'academy_id' => $this->academy->id,
            ]);

            $student = User::factory()->student()->forAcademy($this->academy)->create();

            $session = AcademicSession::factory()->create([
                'academy_id' => $this->academy->id,
                'academic_teacher_id' => $teacherProfile->id,
                'student_id' => $student->id,
            ]);

            $group1 = $this->service->createForAcademicSession($session);
            $group2 = $this->service->createForAcademicSession($session);

            expect($group1->id)->toBe($group2->id);
        });
    });

    describe('createForInteractiveCourse', function () {
        it('creates chat group for interactive course', function () {
            $teacher = User::factory()->academicTeacher()->forAcademy($this->academy)->create();
            $teacherProfile = AcademicTeacherProfile::factory()->create([
                'user_id' => $teacher->id,
                'academy_id' => $this->academy->id,
            ]);

            $course = InteractiveCourse::factory()->create([
                'academy_id' => $this->academy->id,
                'academic_teacher_profile_id' => $teacherProfile->id,
            ]);

            $group = $this->service->createForInteractiveCourse($course);

            expect($group)->toBeInstanceOf(ChatGroup::class)
                ->and($group->interactive_course_id)->toBe($course->id)
                ->and($group->type)->toBe(ChatGroup::TYPE_INTERACTIVE_COURSE)
                ->and($group->is_active)->toBeTrue();
        });

        it('returns existing group if already exists', function () {
            $teacher = User::factory()->academicTeacher()->forAcademy($this->academy)->create();
            $teacherProfile = AcademicTeacherProfile::factory()->create([
                'user_id' => $teacher->id,
                'academy_id' => $this->academy->id,
            ]);

            $course = InteractiveCourse::factory()->create([
                'academy_id' => $this->academy->id,
                'academic_teacher_profile_id' => $teacherProfile->id,
            ]);

            $group1 = $this->service->createForInteractiveCourse($course);
            $group2 = $this->service->createForInteractiveCourse($course);

            expect($group1->id)->toBe($group2->id);
        });

        it('sets course title in group name', function () {
            $teacher = User::factory()->academicTeacher()->forAcademy($this->academy)->create();
            $teacherProfile = AcademicTeacherProfile::factory()->create([
                'user_id' => $teacher->id,
                'academy_id' => $this->academy->id,
            ]);

            $course = InteractiveCourse::factory()->create([
                'academy_id' => $this->academy->id,
                'academic_teacher_profile_id' => $teacherProfile->id,
                'title' => 'دورة الرياضيات',
            ]);

            $group = $this->service->createForInteractiveCourse($course);

            expect($group->name)->toContain('دورة الرياضيات');
        });
    });

    describe('createForRecordedCourse', function () {
        it('creates chat group for recorded course', function () {
            $teacher = User::factory()->academicTeacher()->forAcademy($this->academy)->create();
            $teacherProfile = AcademicTeacherProfile::factory()->create([
                'user_id' => $teacher->id,
                'academy_id' => $this->academy->id,
            ]);

            $course = RecordedCourse::factory()->create([
                'academy_id' => $this->academy->id,
                'academic_teacher_profile_id' => $teacherProfile->id,
            ]);

            $group = $this->service->createForRecordedCourse($course);

            expect($group)->toBeInstanceOf(ChatGroup::class)
                ->and($group->recorded_course_id)->toBe($course->id)
                ->and($group->type)->toBe(ChatGroup::TYPE_RECORDED_COURSE)
                ->and($group->is_active)->toBeTrue();
        });

        it('returns existing group if already exists', function () {
            $teacher = User::factory()->academicTeacher()->forAcademy($this->academy)->create();
            $teacherProfile = AcademicTeacherProfile::factory()->create([
                'user_id' => $teacher->id,
                'academy_id' => $this->academy->id,
            ]);

            $course = RecordedCourse::factory()->create([
                'academy_id' => $this->academy->id,
                'academic_teacher_profile_id' => $teacherProfile->id,
            ]);

            $group1 = $this->service->createForRecordedCourse($course);
            $group2 = $this->service->createForRecordedCourse($course);

            expect($group1->id)->toBe($group2->id);
        });
    });

    describe('createAnnouncementGroup', function () {
        it('creates announcement group for academy', function () {
            $admin = User::factory()->admin()->forAcademy($this->academy)->create();
            $this->academy->update(['admin_id' => $admin->id]);

            $group = $this->service->createAnnouncementGroup($this->academy);

            expect($group)->toBeInstanceOf(ChatGroup::class)
                ->and($group->type)->toBe(ChatGroup::TYPE_ANNOUNCEMENT)
                ->and($group->academy_id)->toBe($this->academy->id)
                ->and($group->is_active)->toBeTrue();
        });

        it('returns existing announcement group if already exists', function () {
            $admin = User::factory()->admin()->forAcademy($this->academy)->create();
            $this->academy->update(['admin_id' => $admin->id]);

            $group1 = $this->service->createAnnouncementGroup($this->academy);
            $group2 = $this->service->createAnnouncementGroup($this->academy);

            expect($group1->id)->toBe($group2->id);
        });

        it('adds admin as group admin', function () {
            $admin = User::factory()->admin()->forAcademy($this->academy)->create();
            $this->academy->update(['admin_id' => $admin->id]);

            $group = $this->service->createAnnouncementGroup($this->academy);

            $membership = $group->memberships()
                ->where('user_id', $admin->id)
                ->first();

            expect($membership)->not->toBeNull()
                ->and($membership->role)->toBe(ChatGroup::ROLE_ADMIN);
        });
    });

    describe('addMember', function () {
        it('adds user as member to group', function () {
            $admin = User::factory()->admin()->forAcademy($this->academy)->create();
            $group = ChatGroup::factory()->create([
                'academy_id' => $this->academy->id,
                'owner_id' => $admin->id,
            ]);

            $user = User::factory()->student()->forAcademy($this->academy)->create();

            $member = $this->service->addMember($group, $user);

            expect($member)->toBeInstanceOf(ChatGroupMember::class)
                ->and($member->user_id)->toBe($user->id)
                ->and($member->group_id)->toBe($group->id)
                ->and($member->role)->toBe(ChatGroup::ROLE_MEMBER);
        });

        it('adds user with specific role', function () {
            $admin = User::factory()->admin()->forAcademy($this->academy)->create();
            $group = ChatGroup::factory()->create([
                'academy_id' => $this->academy->id,
                'owner_id' => $admin->id,
            ]);

            $user = User::factory()->supervisor()->forAcademy($this->academy)->create();

            $member = $this->service->addMember($group, $user, ChatGroup::ROLE_MODERATOR);

            expect($member->role)->toBe(ChatGroup::ROLE_MODERATOR);
        });

        it('returns existing membership if user already member', function () {
            $admin = User::factory()->admin()->forAcademy($this->academy)->create();
            $group = ChatGroup::factory()->create([
                'academy_id' => $this->academy->id,
                'owner_id' => $admin->id,
            ]);

            $user = User::factory()->student()->forAcademy($this->academy)->create();

            $member1 = $this->service->addMember($group, $user);
            $member2 = $this->service->addMember($group, $user);

            expect($member1->id)->toBe($member2->id);
        });

        it('respects can_send_messages parameter', function () {
            $admin = User::factory()->admin()->forAcademy($this->academy)->create();
            $group = ChatGroup::factory()->create([
                'academy_id' => $this->academy->id,
                'owner_id' => $admin->id,
            ]);

            $user = User::factory()->student()->forAcademy($this->academy)->create();

            $member = $this->service->addMember($group, $user, ChatGroup::ROLE_MEMBER, false);

            expect($member->can_send_messages)->toBeFalse();
        });
    });

    describe('removeMember', function () {
        it('removes member from group', function () {
            $admin = User::factory()->admin()->forAcademy($this->academy)->create();
            $group = ChatGroup::factory()->create([
                'academy_id' => $this->academy->id,
                'owner_id' => $admin->id,
            ]);

            $user = User::factory()->student()->forAcademy($this->academy)->create();
            $this->service->addMember($group, $user);

            $result = $this->service->removeMember($group, $user);

            expect($result)->toBeTrue()
                ->and($group->memberships()->where('user_id', $user->id)->exists())->toBeFalse();
        });

        it('returns false if member does not exist', function () {
            $admin = User::factory()->admin()->forAcademy($this->academy)->create();
            $group = ChatGroup::factory()->create([
                'academy_id' => $this->academy->id,
                'owner_id' => $admin->id,
            ]);

            $user = User::factory()->student()->forAcademy($this->academy)->create();

            $result = $this->service->removeMember($group, $user);

            expect($result)->toBeFalse();
        });
    });

    describe('updateMemberRole', function () {
        it('updates member role', function () {
            $admin = User::factory()->admin()->forAcademy($this->academy)->create();
            $group = ChatGroup::factory()->create([
                'academy_id' => $this->academy->id,
                'owner_id' => $admin->id,
            ]);

            $user = User::factory()->student()->forAcademy($this->academy)->create();
            $this->service->addMember($group, $user, ChatGroup::ROLE_MEMBER);

            $result = $this->service->updateMemberRole($group, $user, ChatGroup::ROLE_MODERATOR);

            expect($result)->toBeTrue();

            $membership = $group->memberships()->where('user_id', $user->id)->first();
            expect($membership->role)->toBe(ChatGroup::ROLE_MODERATOR);
        });

        it('returns false if member does not exist', function () {
            $admin = User::factory()->admin()->forAcademy($this->academy)->create();
            $group = ChatGroup::factory()->create([
                'academy_id' => $this->academy->id,
                'owner_id' => $admin->id,
            ]);

            $user = User::factory()->student()->forAcademy($this->academy)->create();

            $result = $this->service->updateMemberRole($group, $user, ChatGroup::ROLE_ADMIN);

            expect($result)->toBeFalse();
        });
    });

    describe('syncGroupMembers', function () {
        it('syncs quran circle members', function () {
            $teacher = User::factory()->quranTeacher()->forAcademy($this->academy)->create();
            $teacherProfile = QuranTeacherProfile::factory()->create([
                'user_id' => $teacher->id,
                'academy_id' => $this->academy->id,
            ]);

            $circle = QuranCircle::factory()->create([
                'academy_id' => $this->academy->id,
                'quran_teacher_profile_id' => $teacherProfile->id,
            ]);

            $group = $this->service->createForQuranCircle($circle);

            // Sync should not throw errors
            $this->service->syncGroupMembers($group);

            expect($group->is_active)->toBeTrue();
        });

        it('handles non-existent related entity', function () {
            $admin = User::factory()->admin()->forAcademy($this->academy)->create();
            $group = ChatGroup::factory()->create([
                'academy_id' => $this->academy->id,
                'owner_id' => $admin->id,
                'type' => ChatGroup::TYPE_QURAN_CIRCLE,
                'quran_circle_id' => null, // No circle attached
            ]);

            // Should not throw error
            $this->service->syncGroupMembers($group);

            expect(true)->toBeTrue();
        });
    });
});
