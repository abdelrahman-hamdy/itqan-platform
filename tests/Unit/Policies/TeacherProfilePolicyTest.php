<?php

use App\Models\Academy;
use App\Models\AcademicSubscription;
use App\Models\AcademicTeacherProfile;
use App\Models\ParentProfile;
use App\Models\QuranSubscription;
use App\Models\QuranTeacherProfile;
use App\Models\StudentProfile;
use App\Models\User;
use App\Policies\TeacherProfilePolicy;

describe('TeacherProfilePolicy', function () {
    beforeEach(function () {
        $this->policy = new TeacherProfilePolicy();
        $this->academy = Academy::factory()->create();
    });

    describe('viewAny', function () {
        it('allows super admin to view any teacher profiles', function () {
            $user = User::factory()->superAdmin()->create();
            expect($this->policy->viewAny($user))->toBeTrue();
        });

        it('allows admin to view any teacher profiles', function () {
            $user = User::factory()->admin()->forAcademy($this->academy)->create();
            expect($this->policy->viewAny($user))->toBeTrue();
        });

        it('allows supervisor to view any teacher profiles', function () {
            $user = User::factory()->supervisor()->forAcademy($this->academy)->create();
            expect($this->policy->viewAny($user))->toBeTrue();
        });

        it('allows student to view any teacher profiles', function () {
            $user = User::factory()->student()->forAcademy($this->academy)->create();
            expect($this->policy->viewAny($user))->toBeTrue();
        });

        it('denies quran teacher from viewing any profiles', function () {
            $user = User::factory()->quranTeacher()->forAcademy($this->academy)->create();
            expect($this->policy->viewAny($user))->toBeFalse();
        });
    });

    describe('view for QuranTeacherProfile', function () {
        it('allows admin to view profile in same academy', function () {
            $admin = User::factory()->admin()->forAcademy($this->academy)->create();
            $teacher = User::factory()->quranTeacher()->forAcademy($this->academy)->create();
            $profile = QuranTeacherProfile::factory()->create([
                'user_id' => $teacher->id,
                'academy_id' => $this->academy->id,
            ]);

            expect($this->policy->view($admin, $profile))->toBeTrue();
        });

        it('allows teacher to view own profile', function () {
            $teacher = User::factory()->quranTeacher()->forAcademy($this->academy)->create();
            $profile = QuranTeacherProfile::factory()->create([
                'user_id' => $teacher->id,
                'academy_id' => $this->academy->id,
            ]);

            expect($this->policy->view($teacher, $profile))->toBeTrue();
        });

        it('allows student to view their teacher profile', function () {
            $teacher = User::factory()->quranTeacher()->forAcademy($this->academy)->create();
            $profile = QuranTeacherProfile::factory()->create([
                'user_id' => $teacher->id,
                'academy_id' => $this->academy->id,
            ]);
            $student = User::factory()->student()->forAcademy($this->academy)->create();

            // Create subscription linking student to teacher
            QuranSubscription::create([
                'academy_id' => $this->academy->id,
                'student_profile_id' => $student->studentProfileUnscoped->id,
                'quran_teacher_profile_id' => $profile->id,
                'subscription_code' => 'QS-' . uniqid(),
                'status' => 'active',
            ]);

            expect($this->policy->view($student, $profile))->toBeTrue();
        });

        it('denies student from viewing non-assigned teacher profile', function () {
            $teacher = User::factory()->quranTeacher()->forAcademy($this->academy)->create();
            $profile = QuranTeacherProfile::factory()->create([
                'user_id' => $teacher->id,
                'academy_id' => $this->academy->id,
            ]);
            $student = User::factory()->student()->forAcademy($this->academy)->create();

            // No subscription - student should not see this teacher
            expect($this->policy->view($student, $profile))->toBeFalse();
        });

        it('allows parent to view their children teacher profile', function () {
            $teacher = User::factory()->quranTeacher()->forAcademy($this->academy)->create();
            $profile = QuranTeacherProfile::factory()->create([
                'user_id' => $teacher->id,
                'academy_id' => $this->academy->id,
            ]);
            $parent = User::factory()->parent()->forAcademy($this->academy)->create();
            $parentProfile = $parent->parentProfile;
            $student = User::factory()->student()->forAcademy($this->academy)->create();

            // Link parent to student
            $parentProfile->students()->attach($student->studentProfileUnscoped->id);

            // Create subscription linking student to teacher
            QuranSubscription::create([
                'academy_id' => $this->academy->id,
                'student_profile_id' => $student->studentProfileUnscoped->id,
                'quran_teacher_profile_id' => $profile->id,
                'subscription_code' => 'QS-' . uniqid(),
                'status' => 'active',
            ]);

            expect($this->policy->view($parent, $profile))->toBeTrue();
        });
    });

    describe('view for AcademicTeacherProfile', function () {
        it('allows admin to view profile', function () {
            $admin = User::factory()->admin()->forAcademy($this->academy)->create();
            $teacher = User::factory()->academicTeacher()->forAcademy($this->academy)->create();
            $profile = AcademicTeacherProfile::factory()->create([
                'user_id' => $teacher->id,
                'academy_id' => $this->academy->id,
            ]);

            expect($this->policy->view($admin, $profile))->toBeTrue();
        });

        it('allows academic teacher to view own profile', function () {
            $teacher = User::factory()->academicTeacher()->forAcademy($this->academy)->create();
            $profile = AcademicTeacherProfile::factory()->create([
                'user_id' => $teacher->id,
                'academy_id' => $this->academy->id,
            ]);

            expect($this->policy->view($teacher, $profile))->toBeTrue();
        });

        it('allows student to view their academic teacher profile', function () {
            $teacher = User::factory()->academicTeacher()->forAcademy($this->academy)->create();
            $profile = AcademicTeacherProfile::factory()->create([
                'user_id' => $teacher->id,
                'academy_id' => $this->academy->id,
            ]);
            $student = User::factory()->student()->forAcademy($this->academy)->create();

            // Create subscription linking student to teacher
            AcademicSubscription::create([
                'academy_id' => $this->academy->id,
                'student_profile_id' => $student->studentProfileUnscoped->id,
                'academic_teacher_profile_id' => $profile->id,
                'subscription_code' => 'AS-' . uniqid(),
                'status' => 'active',
            ]);

            expect($this->policy->view($student, $profile))->toBeTrue();
        });
    });

    describe('create', function () {
        it('allows super admin to create teacher profiles', function () {
            $user = User::factory()->superAdmin()->create();
            expect($this->policy->create($user))->toBeTrue();
        });

        it('allows admin to create teacher profiles', function () {
            $user = User::factory()->admin()->forAcademy($this->academy)->create();
            expect($this->policy->create($user))->toBeTrue();
        });

        it('denies supervisor from creating teacher profiles', function () {
            $user = User::factory()->supervisor()->forAcademy($this->academy)->create();
            expect($this->policy->create($user))->toBeFalse();
        });

        it('denies teacher from creating profiles', function () {
            $user = User::factory()->quranTeacher()->forAcademy($this->academy)->create();
            expect($this->policy->create($user))->toBeFalse();
        });

        it('denies student from creating profiles', function () {
            $user = User::factory()->student()->forAcademy($this->academy)->create();
            expect($this->policy->create($user))->toBeFalse();
        });
    });

    describe('update', function () {
        it('allows admin to update any profile in same academy', function () {
            $admin = User::factory()->admin()->forAcademy($this->academy)->create();
            $teacher = User::factory()->quranTeacher()->forAcademy($this->academy)->create();
            $profile = QuranTeacherProfile::factory()->create([
                'user_id' => $teacher->id,
                'academy_id' => $this->academy->id,
            ]);

            expect($this->policy->update($admin, $profile))->toBeTrue();
        });

        it('allows teacher to update own profile', function () {
            $teacher = User::factory()->quranTeacher()->forAcademy($this->academy)->create();
            $profile = QuranTeacherProfile::factory()->create([
                'user_id' => $teacher->id,
                'academy_id' => $this->academy->id,
            ]);

            expect($this->policy->update($teacher, $profile))->toBeTrue();
        });

        it('denies teacher from updating other teacher profile', function () {
            $teacher1 = User::factory()->quranTeacher()->forAcademy($this->academy)->create();
            $teacher2 = User::factory()->quranTeacher()->forAcademy($this->academy)->create();
            $profile = QuranTeacherProfile::factory()->create([
                'user_id' => $teacher1->id,
                'academy_id' => $this->academy->id,
            ]);

            expect($this->policy->update($teacher2, $profile))->toBeFalse();
        });

        it('denies student from updating teacher profile', function () {
            $teacher = User::factory()->quranTeacher()->forAcademy($this->academy)->create();
            $profile = QuranTeacherProfile::factory()->create([
                'user_id' => $teacher->id,
                'academy_id' => $this->academy->id,
            ]);
            $student = User::factory()->student()->forAcademy($this->academy)->create();

            expect($this->policy->update($student, $profile))->toBeFalse();
        });
    });

    describe('delete', function () {
        it('allows super admin to delete teacher profile', function () {
            $superAdmin = User::factory()->superAdmin()->create();
            $teacher = User::factory()->quranTeacher()->forAcademy($this->academy)->create();
            $profile = QuranTeacherProfile::factory()->create([
                'user_id' => $teacher->id,
                'academy_id' => $this->academy->id,
            ]);

            expect($this->policy->delete($superAdmin, $profile))->toBeTrue();
        });

        it('denies admin from deleting teacher profile', function () {
            $admin = User::factory()->admin()->forAcademy($this->academy)->create();
            $teacher = User::factory()->quranTeacher()->forAcademy($this->academy)->create();
            $profile = QuranTeacherProfile::factory()->create([
                'user_id' => $teacher->id,
                'academy_id' => $this->academy->id,
            ]);

            expect($this->policy->delete($admin, $profile))->toBeFalse();
        });

        it('denies teacher from deleting own profile', function () {
            $teacher = User::factory()->quranTeacher()->forAcademy($this->academy)->create();
            $profile = QuranTeacherProfile::factory()->create([
                'user_id' => $teacher->id,
                'academy_id' => $this->academy->id,
            ]);

            expect($this->policy->delete($teacher, $profile))->toBeFalse();
        });
    });

    describe('viewEarnings', function () {
        it('allows admin to view teacher earnings', function () {
            $admin = User::factory()->admin()->forAcademy($this->academy)->create();
            $teacher = User::factory()->quranTeacher()->forAcademy($this->academy)->create();
            $profile = QuranTeacherProfile::factory()->create([
                'user_id' => $teacher->id,
                'academy_id' => $this->academy->id,
            ]);

            expect($this->policy->viewEarnings($admin, $profile))->toBeTrue();
        });

        it('allows teacher to view own earnings', function () {
            $teacher = User::factory()->quranTeacher()->forAcademy($this->academy)->create();
            $profile = QuranTeacherProfile::factory()->create([
                'user_id' => $teacher->id,
                'academy_id' => $this->academy->id,
            ]);

            expect($this->policy->viewEarnings($teacher, $profile))->toBeTrue();
        });

        it('denies teacher from viewing other teacher earnings', function () {
            $teacher1 = User::factory()->quranTeacher()->forAcademy($this->academy)->create();
            $teacher2 = User::factory()->quranTeacher()->forAcademy($this->academy)->create();
            $profile = QuranTeacherProfile::factory()->create([
                'user_id' => $teacher1->id,
                'academy_id' => $this->academy->id,
            ]);

            expect($this->policy->viewEarnings($teacher2, $profile))->toBeFalse();
        });

        it('denies student from viewing teacher earnings', function () {
            $teacher = User::factory()->quranTeacher()->forAcademy($this->academy)->create();
            $profile = QuranTeacherProfile::factory()->create([
                'user_id' => $teacher->id,
                'academy_id' => $this->academy->id,
            ]);
            $student = User::factory()->student()->forAcademy($this->academy)->create();

            expect($this->policy->viewEarnings($student, $profile))->toBeFalse();
        });
    });

    describe('viewSchedule', function () {
        it('delegates to view permission', function () {
            $teacher = User::factory()->quranTeacher()->forAcademy($this->academy)->create();
            $profile = QuranTeacherProfile::factory()->create([
                'user_id' => $teacher->id,
                'academy_id' => $this->academy->id,
            ]);

            expect($this->policy->viewSchedule($teacher, $profile))->toBeTrue();
        });

        it('allows student to view their teacher schedule', function () {
            $teacher = User::factory()->quranTeacher()->forAcademy($this->academy)->create();
            $profile = QuranTeacherProfile::factory()->create([
                'user_id' => $teacher->id,
                'academy_id' => $this->academy->id,
            ]);
            $student = User::factory()->student()->forAcademy($this->academy)->create();

            // Create subscription linking student to teacher
            QuranSubscription::create([
                'academy_id' => $this->academy->id,
                'student_profile_id' => $student->studentProfileUnscoped->id,
                'quran_teacher_profile_id' => $profile->id,
                'subscription_code' => 'QS-' . uniqid(),
                'status' => 'active',
            ]);

            expect($this->policy->viewSchedule($student, $profile))->toBeTrue();
        });
    });
});
