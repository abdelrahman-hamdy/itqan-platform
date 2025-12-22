<?php

use App\Models\Academy;
use App\Models\AcademicGradeLevel;
use App\Models\AcademicSubscription;
use App\Models\AcademicTeacherProfile;
use App\Models\ParentProfile;
use App\Models\QuranSubscription;
use App\Models\QuranTeacherProfile;
use App\Models\StudentProfile;
use App\Models\User;
use App\Policies\StudentProfilePolicy;

describe('StudentProfilePolicy', function () {
    beforeEach(function () {
        $this->policy = new StudentProfilePolicy();
        $this->academy = Academy::factory()->create();
        $this->gradeLevel = AcademicGradeLevel::factory()->create([
            'academy_id' => $this->academy->id,
        ]);
    });

    describe('viewAny', function () {
        it('allows super admin to view any student profiles', function () {
            $user = User::factory()->superAdmin()->create();
            expect($this->policy->viewAny($user))->toBeTrue();
        });

        it('allows admin to view any student profiles', function () {
            $user = User::factory()->admin()->forAcademy($this->academy)->create();
            expect($this->policy->viewAny($user))->toBeTrue();
        });

        it('allows supervisor to view any student profiles', function () {
            $user = User::factory()->supervisor()->forAcademy($this->academy)->create();
            expect($this->policy->viewAny($user))->toBeTrue();
        });

        it('allows teacher to view any student profiles', function () {
            $user = User::factory()->quranTeacher()->forAcademy($this->academy)->create();
            expect($this->policy->viewAny($user))->toBeTrue();
        });

        it('denies student from viewing any student profiles', function () {
            $user = User::factory()->student()->forAcademy($this->academy)->create();
            expect($this->policy->viewAny($user))->toBeFalse();
        });
    });

    describe('view', function () {
        it('allows admin to view any profile in same academy', function () {
            $admin = User::factory()->admin()->forAcademy($this->academy)->create();
            $student = User::factory()->student()->forAcademy($this->academy)->create();
            $profile = $student->studentProfileUnscoped;

            expect($this->policy->view($admin, $profile))->toBeTrue();
        });

        it('allows student to view own profile', function () {
            $student = User::factory()->student()->forAcademy($this->academy)->create();
            $profile = $student->studentProfileUnscoped;

            expect($this->policy->view($student, $profile))->toBeTrue();
        });

        it('denies student from viewing other student profile', function () {
            $student1 = User::factory()->student()->forAcademy($this->academy)->create();
            $student2 = User::factory()->student()->forAcademy($this->academy)->create();
            $profile = $student1->studentProfileUnscoped;

            expect($this->policy->view($student2, $profile))->toBeFalse();
        });

        it('allows quran teacher to view their student profile', function () {
            $teacher = User::factory()->quranTeacher()->forAcademy($this->academy)->create();
            $teacherProfile = QuranTeacherProfile::factory()->create([
                'user_id' => $teacher->id,
                'academy_id' => $this->academy->id,
            ]);
            $student = User::factory()->student()->forAcademy($this->academy)->create();
            $profile = $student->studentProfileUnscoped;

            // Create subscription linking student to teacher
            QuranSubscription::create([
                'academy_id' => $this->academy->id,
                'student_profile_id' => $profile->id,
                'quran_teacher_profile_id' => $teacherProfile->id,
                'subscription_code' => 'QS-' . uniqid(),
                'status' => 'active',
            ]);

            expect($this->policy->view($teacher, $profile))->toBeTrue();
        });

        it('allows academic teacher to view their student profile', function () {
            $teacher = User::factory()->academicTeacher()->forAcademy($this->academy)->create();
            $teacherProfile = AcademicTeacherProfile::factory()->create([
                'user_id' => $teacher->id,
                'academy_id' => $this->academy->id,
            ]);
            $student = User::factory()->student()->forAcademy($this->academy)->create();
            $profile = $student->studentProfileUnscoped;

            // Create subscription linking student to teacher
            AcademicSubscription::create([
                'academy_id' => $this->academy->id,
                'student_profile_id' => $profile->id,
                'academic_teacher_profile_id' => $teacherProfile->id,
                'subscription_code' => 'AS-' . uniqid(),
                'status' => 'active',
            ]);

            expect($this->policy->view($teacher, $profile))->toBeTrue();
        });

        it('denies teacher from viewing non-student profile', function () {
            $teacher = User::factory()->quranTeacher()->forAcademy($this->academy)->create();
            $teacherProfile = QuranTeacherProfile::factory()->create([
                'user_id' => $teacher->id,
                'academy_id' => $this->academy->id,
            ]);
            $student = User::factory()->student()->forAcademy($this->academy)->create();
            $profile = $student->studentProfileUnscoped;

            // No subscription - teacher should not see this student
            expect($this->policy->view($teacher, $profile))->toBeFalse();
        });

        it('allows parent to view their child profile', function () {
            $parent = User::factory()->parent()->forAcademy($this->academy)->create();
            $parentProfile = $parent->parentProfile;
            $student = User::factory()->student()->forAcademy($this->academy)->create();
            $profile = $student->studentProfileUnscoped;

            // Link parent to student
            $parentProfile->students()->attach($profile->id);

            expect($this->policy->view($parent, $profile))->toBeTrue();
        });

        it('denies parent from viewing non-child profile', function () {
            $parent = User::factory()->parent()->forAcademy($this->academy)->create();
            $student = User::factory()->student()->forAcademy($this->academy)->create();
            $profile = $student->studentProfileUnscoped;

            // No link - parent should not see this student
            expect($this->policy->view($parent, $profile))->toBeFalse();
        });
    });

    describe('create', function () {
        it('allows admin to create student profiles', function () {
            $user = User::factory()->admin()->forAcademy($this->academy)->create();
            expect($this->policy->create($user))->toBeTrue();
        });

        it('allows supervisor to create student profiles', function () {
            $user = User::factory()->supervisor()->forAcademy($this->academy)->create();
            expect($this->policy->create($user))->toBeTrue();
        });

        it('denies teacher from creating student profiles', function () {
            $user = User::factory()->quranTeacher()->forAcademy($this->academy)->create();
            expect($this->policy->create($user))->toBeFalse();
        });

        it('denies student from creating student profiles', function () {
            $user = User::factory()->student()->forAcademy($this->academy)->create();
            expect($this->policy->create($user))->toBeFalse();
        });
    });

    describe('update', function () {
        it('allows admin to update any profile in same academy', function () {
            $admin = User::factory()->admin()->forAcademy($this->academy)->create();
            $student = User::factory()->student()->forAcademy($this->academy)->create();
            $profile = $student->studentProfileUnscoped;

            expect($this->policy->update($admin, $profile))->toBeTrue();
        });

        it('allows student to update own profile', function () {
            $student = User::factory()->student()->forAcademy($this->academy)->create();
            $profile = $student->studentProfileUnscoped;

            expect($this->policy->update($student, $profile))->toBeTrue();
        });

        it('denies supervisor from updating profile', function () {
            $supervisor = User::factory()->supervisor()->forAcademy($this->academy)->create();
            $student = User::factory()->student()->forAcademy($this->academy)->create();
            $profile = $student->studentProfileUnscoped;

            expect($this->policy->update($supervisor, $profile))->toBeFalse();
        });

        it('denies parent from updating child profile', function () {
            $parent = User::factory()->parent()->forAcademy($this->academy)->create();
            $parentProfile = $parent->parentProfile;
            $student = User::factory()->student()->forAcademy($this->academy)->create();
            $profile = $student->studentProfileUnscoped;

            // Link parent to student
            $parentProfile->students()->attach($profile->id);

            expect($this->policy->update($parent, $profile))->toBeFalse();
        });
    });

    describe('delete', function () {
        it('allows super admin to delete student profile', function () {
            $superAdmin = User::factory()->superAdmin()->create();
            $student = User::factory()->student()->forAcademy($this->academy)->create();
            $profile = $student->studentProfileUnscoped;

            expect($this->policy->delete($superAdmin, $profile))->toBeTrue();
        });

        it('denies admin from deleting student profile', function () {
            $admin = User::factory()->admin()->forAcademy($this->academy)->create();
            $student = User::factory()->student()->forAcademy($this->academy)->create();
            $profile = $student->studentProfileUnscoped;

            expect($this->policy->delete($admin, $profile))->toBeFalse();
        });

        it('denies student from deleting own profile', function () {
            $student = User::factory()->student()->forAcademy($this->academy)->create();
            $profile = $student->studentProfileUnscoped;

            expect($this->policy->delete($student, $profile))->toBeFalse();
        });
    });

    describe('viewProgress', function () {
        it('delegates to view permission', function () {
            $admin = User::factory()->admin()->forAcademy($this->academy)->create();
            $student = User::factory()->student()->forAcademy($this->academy)->create();
            $profile = $student->studentProfileUnscoped;

            expect($this->policy->viewProgress($admin, $profile))->toBeTrue();
        });

        it('allows student to view own progress', function () {
            $student = User::factory()->student()->forAcademy($this->academy)->create();
            $profile = $student->studentProfileUnscoped;

            expect($this->policy->viewProgress($student, $profile))->toBeTrue();
        });
    });

    describe('viewCertificates', function () {
        it('delegates to view permission', function () {
            $admin = User::factory()->admin()->forAcademy($this->academy)->create();
            $student = User::factory()->student()->forAcademy($this->academy)->create();
            $profile = $student->studentProfileUnscoped;

            expect($this->policy->viewCertificates($admin, $profile))->toBeTrue();
        });

        it('allows student to view own certificates', function () {
            $student = User::factory()->student()->forAcademy($this->academy)->create();
            $profile = $student->studentProfileUnscoped;

            expect($this->policy->viewCertificates($student, $profile))->toBeTrue();
        });
    });

    describe('viewPayments', function () {
        it('allows admin to view student payments', function () {
            $admin = User::factory()->admin()->forAcademy($this->academy)->create();
            $student = User::factory()->student()->forAcademy($this->academy)->create();
            $profile = $student->studentProfileUnscoped;

            expect($this->policy->viewPayments($admin, $profile))->toBeTrue();
        });

        it('allows student to view own payments', function () {
            $student = User::factory()->student()->forAcademy($this->academy)->create();
            $profile = $student->studentProfileUnscoped;

            expect($this->policy->viewPayments($student, $profile))->toBeTrue();
        });

        it('allows parent to view child payments', function () {
            $parent = User::factory()->parent()->forAcademy($this->academy)->create();
            $parentProfile = $parent->parentProfile;
            $student = User::factory()->student()->forAcademy($this->academy)->create();
            $profile = $student->studentProfileUnscoped;

            // Link parent to student
            $parentProfile->students()->attach($profile->id);

            expect($this->policy->viewPayments($parent, $profile))->toBeTrue();
        });

        it('denies teacher from viewing student payments', function () {
            $teacher = User::factory()->quranTeacher()->forAcademy($this->academy)->create();
            $teacherProfile = QuranTeacherProfile::factory()->create([
                'user_id' => $teacher->id,
                'academy_id' => $this->academy->id,
            ]);
            $student = User::factory()->student()->forAcademy($this->academy)->create();
            $profile = $student->studentProfileUnscoped;

            // Create subscription linking student to teacher
            QuranSubscription::create([
                'academy_id' => $this->academy->id,
                'student_profile_id' => $profile->id,
                'quran_teacher_profile_id' => $teacherProfile->id,
                'subscription_code' => 'QS-' . uniqid(),
                'status' => 'active',
            ]);

            expect($this->policy->viewPayments($teacher, $profile))->toBeFalse();
        });
    });
});
