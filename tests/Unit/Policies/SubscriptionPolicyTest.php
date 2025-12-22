<?php

use App\Models\Academy;
use App\Models\AcademicSubscription;
use App\Models\AcademicTeacherProfile;
use App\Models\CourseSubscription;
use App\Models\ParentProfile;
use App\Models\QuranSubscription;
use App\Models\QuranTeacherProfile;
use App\Models\RecordedCourse;
use App\Models\StudentProfile;
use App\Models\User;
use App\Policies\SubscriptionPolicy;

describe('SubscriptionPolicy', function () {
    beforeEach(function () {
        $this->policy = new SubscriptionPolicy();
        $this->academy = Academy::factory()->create();
    });

    describe('viewAny', function () {
        it('allows super admin to view any subscriptions', function () {
            $user = User::factory()->superAdmin()->create();
            expect($this->policy->viewAny($user))->toBeTrue();
        });

        it('allows admin to view any subscriptions', function () {
            $user = User::factory()->admin()->forAcademy($this->academy)->create();
            expect($this->policy->viewAny($user))->toBeTrue();
        });

        it('allows supervisor to view any subscriptions', function () {
            $user = User::factory()->supervisor()->forAcademy($this->academy)->create();
            expect($this->policy->viewAny($user))->toBeTrue();
        });

        it('allows teacher to view any subscriptions', function () {
            $user = User::factory()->quranTeacher()->forAcademy($this->academy)->create();
            expect($this->policy->viewAny($user))->toBeTrue();
        });

        it('allows student to view any subscriptions', function () {
            $user = User::factory()->student()->forAcademy($this->academy)->create();
            expect($this->policy->viewAny($user))->toBeTrue();
        });
    });

    describe('view for QuranSubscription', function () {
        it('allows admin to view subscription in same academy', function () {
            $admin = User::factory()->admin()->forAcademy($this->academy)->create();
            $student = User::factory()->student()->forAcademy($this->academy)->create();
            $teacher = User::factory()->quranTeacher()->forAcademy($this->academy)->create();
            $teacherProfile = QuranTeacherProfile::factory()->create([
                'user_id' => $teacher->id,
                'academy_id' => $this->academy->id,
            ]);

            $subscription = QuranSubscription::create([
                'academy_id' => $this->academy->id,
                'student_profile_id' => $student->studentProfileUnscoped->id,
                'quran_teacher_profile_id' => $teacherProfile->id,
                'subscription_code' => 'QS-' . uniqid(),
                'status' => 'active',
            ]);

            expect($this->policy->view($admin, $subscription))->toBeTrue();
        });

        it('allows student to view own subscription', function () {
            $student = User::factory()->student()->forAcademy($this->academy)->create();
            $teacher = User::factory()->quranTeacher()->forAcademy($this->academy)->create();
            $teacherProfile = QuranTeacherProfile::factory()->create([
                'user_id' => $teacher->id,
                'academy_id' => $this->academy->id,
            ]);

            $subscription = QuranSubscription::create([
                'academy_id' => $this->academy->id,
                'student_profile_id' => $student->studentProfileUnscoped->id,
                'quran_teacher_profile_id' => $teacherProfile->id,
                'subscription_code' => 'QS-' . uniqid(),
                'status' => 'active',
            ]);

            expect($this->policy->view($student, $subscription))->toBeTrue();
        });

        it('allows teacher to view subscription for their student', function () {
            $student = User::factory()->student()->forAcademy($this->academy)->create();
            $teacher = User::factory()->quranTeacher()->forAcademy($this->academy)->create();
            $teacherProfile = QuranTeacherProfile::factory()->create([
                'user_id' => $teacher->id,
                'academy_id' => $this->academy->id,
            ]);

            $subscription = QuranSubscription::create([
                'academy_id' => $this->academy->id,
                'student_profile_id' => $student->studentProfileUnscoped->id,
                'quran_teacher_profile_id' => $teacherProfile->id,
                'subscription_code' => 'QS-' . uniqid(),
                'status' => 'active',
            ]);

            expect($this->policy->view($teacher, $subscription))->toBeTrue();
        });

        it('denies other student from viewing subscription', function () {
            $student1 = User::factory()->student()->forAcademy($this->academy)->create();
            $student2 = User::factory()->student()->forAcademy($this->academy)->create();
            $teacher = User::factory()->quranTeacher()->forAcademy($this->academy)->create();
            $teacherProfile = QuranTeacherProfile::factory()->create([
                'user_id' => $teacher->id,
                'academy_id' => $this->academy->id,
            ]);

            $subscription = QuranSubscription::create([
                'academy_id' => $this->academy->id,
                'student_profile_id' => $student1->studentProfileUnscoped->id,
                'quran_teacher_profile_id' => $teacherProfile->id,
                'subscription_code' => 'QS-' . uniqid(),
                'status' => 'active',
            ]);

            expect($this->policy->view($student2, $subscription))->toBeFalse();
        });
    });

    describe('create', function () {
        it('allows admin to create subscriptions', function () {
            $user = User::factory()->admin()->forAcademy($this->academy)->create();
            expect($this->policy->create($user))->toBeTrue();
        });

        it('allows supervisor to create subscriptions', function () {
            $user = User::factory()->supervisor()->forAcademy($this->academy)->create();
            expect($this->policy->create($user))->toBeTrue();
        });

        it('denies teacher from creating subscriptions', function () {
            $user = User::factory()->quranTeacher()->forAcademy($this->academy)->create();
            expect($this->policy->create($user))->toBeFalse();
        });

        it('denies student from creating subscriptions', function () {
            $user = User::factory()->student()->forAcademy($this->academy)->create();
            expect($this->policy->create($user))->toBeFalse();
        });
    });

    describe('update', function () {
        it('allows admin to update subscription', function () {
            $admin = User::factory()->admin()->forAcademy($this->academy)->create();
            $student = User::factory()->student()->forAcademy($this->academy)->create();
            $teacher = User::factory()->quranTeacher()->forAcademy($this->academy)->create();
            $teacherProfile = QuranTeacherProfile::factory()->create([
                'user_id' => $teacher->id,
                'academy_id' => $this->academy->id,
            ]);

            $subscription = QuranSubscription::create([
                'academy_id' => $this->academy->id,
                'student_profile_id' => $student->studentProfileUnscoped->id,
                'quran_teacher_profile_id' => $teacherProfile->id,
                'subscription_code' => 'QS-' . uniqid(),
                'status' => 'active',
            ]);

            expect($this->policy->update($admin, $subscription))->toBeTrue();
        });

        it('denies supervisor from updating subscription', function () {
            $supervisor = User::factory()->supervisor()->forAcademy($this->academy)->create();
            $student = User::factory()->student()->forAcademy($this->academy)->create();
            $teacher = User::factory()->quranTeacher()->forAcademy($this->academy)->create();
            $teacherProfile = QuranTeacherProfile::factory()->create([
                'user_id' => $teacher->id,
                'academy_id' => $this->academy->id,
            ]);

            $subscription = QuranSubscription::create([
                'academy_id' => $this->academy->id,
                'student_profile_id' => $student->studentProfileUnscoped->id,
                'quran_teacher_profile_id' => $teacherProfile->id,
                'subscription_code' => 'QS-' . uniqid(),
                'status' => 'active',
            ]);

            expect($this->policy->update($supervisor, $subscription))->toBeFalse();
        });

        it('denies student from updating subscription', function () {
            $student = User::factory()->student()->forAcademy($this->academy)->create();
            $teacher = User::factory()->quranTeacher()->forAcademy($this->academy)->create();
            $teacherProfile = QuranTeacherProfile::factory()->create([
                'user_id' => $teacher->id,
                'academy_id' => $this->academy->id,
            ]);

            $subscription = QuranSubscription::create([
                'academy_id' => $this->academy->id,
                'student_profile_id' => $student->studentProfileUnscoped->id,
                'quran_teacher_profile_id' => $teacherProfile->id,
                'subscription_code' => 'QS-' . uniqid(),
                'status' => 'active',
            ]);

            expect($this->policy->update($student, $subscription))->toBeFalse();
        });
    });

    describe('delete', function () {
        it('allows super admin to delete subscription', function () {
            $superAdmin = User::factory()->superAdmin()->create();
            $student = User::factory()->student()->forAcademy($this->academy)->create();
            $teacher = User::factory()->quranTeacher()->forAcademy($this->academy)->create();
            $teacherProfile = QuranTeacherProfile::factory()->create([
                'user_id' => $teacher->id,
                'academy_id' => $this->academy->id,
            ]);

            $subscription = QuranSubscription::create([
                'academy_id' => $this->academy->id,
                'student_profile_id' => $student->studentProfileUnscoped->id,
                'quran_teacher_profile_id' => $teacherProfile->id,
                'subscription_code' => 'QS-' . uniqid(),
                'status' => 'active',
            ]);

            expect($this->policy->delete($superAdmin, $subscription))->toBeTrue();
        });

        it('denies admin from deleting subscription', function () {
            $admin = User::factory()->admin()->forAcademy($this->academy)->create();
            $student = User::factory()->student()->forAcademy($this->academy)->create();
            $teacher = User::factory()->quranTeacher()->forAcademy($this->academy)->create();
            $teacherProfile = QuranTeacherProfile::factory()->create([
                'user_id' => $teacher->id,
                'academy_id' => $this->academy->id,
            ]);

            $subscription = QuranSubscription::create([
                'academy_id' => $this->academy->id,
                'student_profile_id' => $student->studentProfileUnscoped->id,
                'quran_teacher_profile_id' => $teacherProfile->id,
                'subscription_code' => 'QS-' . uniqid(),
                'status' => 'active',
            ]);

            expect($this->policy->delete($admin, $subscription))->toBeFalse();
        });
    });

    describe('pause', function () {
        it('delegates to update permission', function () {
            $admin = User::factory()->admin()->forAcademy($this->academy)->create();
            $student = User::factory()->student()->forAcademy($this->academy)->create();
            $teacher = User::factory()->quranTeacher()->forAcademy($this->academy)->create();
            $teacherProfile = QuranTeacherProfile::factory()->create([
                'user_id' => $teacher->id,
                'academy_id' => $this->academy->id,
            ]);

            $subscription = QuranSubscription::create([
                'academy_id' => $this->academy->id,
                'student_profile_id' => $student->studentProfileUnscoped->id,
                'quran_teacher_profile_id' => $teacherProfile->id,
                'subscription_code' => 'QS-' . uniqid(),
                'status' => 'active',
            ]);

            expect($this->policy->pause($admin, $subscription))->toBeTrue();
            expect($this->policy->pause($student, $subscription))->toBeFalse();
        });
    });

    describe('resume', function () {
        it('delegates to update permission', function () {
            $admin = User::factory()->admin()->forAcademy($this->academy)->create();
            $student = User::factory()->student()->forAcademy($this->academy)->create();
            $teacher = User::factory()->quranTeacher()->forAcademy($this->academy)->create();
            $teacherProfile = QuranTeacherProfile::factory()->create([
                'user_id' => $teacher->id,
                'academy_id' => $this->academy->id,
            ]);

            $subscription = QuranSubscription::create([
                'academy_id' => $this->academy->id,
                'student_profile_id' => $student->studentProfileUnscoped->id,
                'quran_teacher_profile_id' => $teacherProfile->id,
                'subscription_code' => 'QS-' . uniqid(),
                'status' => 'active',
            ]);

            expect($this->policy->resume($admin, $subscription))->toBeTrue();
            expect($this->policy->resume($student, $subscription))->toBeFalse();
        });
    });

    describe('cancel', function () {
        it('delegates to update permission', function () {
            $admin = User::factory()->admin()->forAcademy($this->academy)->create();
            $student = User::factory()->student()->forAcademy($this->academy)->create();
            $teacher = User::factory()->quranTeacher()->forAcademy($this->academy)->create();
            $teacherProfile = QuranTeacherProfile::factory()->create([
                'user_id' => $teacher->id,
                'academy_id' => $this->academy->id,
            ]);

            $subscription = QuranSubscription::create([
                'academy_id' => $this->academy->id,
                'student_profile_id' => $student->studentProfileUnscoped->id,
                'quran_teacher_profile_id' => $teacherProfile->id,
                'subscription_code' => 'QS-' . uniqid(),
                'status' => 'active',
            ]);

            expect($this->policy->cancel($admin, $subscription))->toBeTrue();
            expect($this->policy->cancel($student, $subscription))->toBeFalse();
        });
    });

    describe('renew', function () {
        it('allows owner to renew subscription', function () {
            $student = User::factory()->student()->forAcademy($this->academy)->create();
            $teacher = User::factory()->quranTeacher()->forAcademy($this->academy)->create();
            $teacherProfile = QuranTeacherProfile::factory()->create([
                'user_id' => $teacher->id,
                'academy_id' => $this->academy->id,
            ]);

            $subscription = QuranSubscription::create([
                'academy_id' => $this->academy->id,
                'student_profile_id' => $student->studentProfileUnscoped->id,
                'quran_teacher_profile_id' => $teacherProfile->id,
                'subscription_code' => 'QS-' . uniqid(),
                'status' => 'active',
            ]);

            expect($this->policy->renew($student, $subscription))->toBeTrue();
        });

        it('allows admin to renew any subscription', function () {
            $admin = User::factory()->admin()->forAcademy($this->academy)->create();
            $student = User::factory()->student()->forAcademy($this->academy)->create();
            $teacher = User::factory()->quranTeacher()->forAcademy($this->academy)->create();
            $teacherProfile = QuranTeacherProfile::factory()->create([
                'user_id' => $teacher->id,
                'academy_id' => $this->academy->id,
            ]);

            $subscription = QuranSubscription::create([
                'academy_id' => $this->academy->id,
                'student_profile_id' => $student->studentProfileUnscoped->id,
                'quran_teacher_profile_id' => $teacherProfile->id,
                'subscription_code' => 'QS-' . uniqid(),
                'status' => 'active',
            ]);

            expect($this->policy->renew($admin, $subscription))->toBeTrue();
        });

        it('denies other student from renewing subscription', function () {
            $student1 = User::factory()->student()->forAcademy($this->academy)->create();
            $student2 = User::factory()->student()->forAcademy($this->academy)->create();
            $teacher = User::factory()->quranTeacher()->forAcademy($this->academy)->create();
            $teacherProfile = QuranTeacherProfile::factory()->create([
                'user_id' => $teacher->id,
                'academy_id' => $this->academy->id,
            ]);

            $subscription = QuranSubscription::create([
                'academy_id' => $this->academy->id,
                'student_profile_id' => $student1->studentProfileUnscoped->id,
                'quran_teacher_profile_id' => $teacherProfile->id,
                'subscription_code' => 'QS-' . uniqid(),
                'status' => 'active',
            ]);

            expect($this->policy->renew($student2, $subscription))->toBeFalse();
        });
    });
});
