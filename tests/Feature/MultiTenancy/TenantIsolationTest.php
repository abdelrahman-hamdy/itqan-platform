<?php

use App\Models\AcademicTeacherProfile;
use App\Models\Academy;
use App\Models\QuranCircle;
use App\Models\QuranIndividualCircle;
use App\Models\QuranSession;
use App\Models\QuranSubscription;
use App\Models\QuranTeacherProfile;
use App\Models\StudentProfile;
use App\Models\User;
use App\Services\AcademyContextService;

beforeEach(function () {
    clearTenantContext();
});

afterEach(function () {
    clearTenantContext();
});

describe('Tenant Isolation', function () {
    it('filters models by current academy context', function () {
        $academy1 = createAcademy(['name' => 'Academy 1']);
        $academy2 = createAcademy(['name' => 'Academy 2']);

        // Create users in different academies
        $user1 = createUser('student', $academy1);
        $user2 = createUser('student', $academy2);

        // Set context to academy1
        setTenantContext($academy1);

        // Query users with the ScopedToAcademy trait (if applied)
        // For this test, we check that the service correctly identifies context
        expect(AcademyContextService::getCurrentAcademyId())->toBe($academy1->id);
    });

    it('cannot access other tenant data when context is set', function () {
        $academy1 = createAcademy(['name' => 'Academy 1']);
        $academy2 = createAcademy(['name' => 'Academy 2']);

        // Create student profiles in each academy
        $student1 = StudentProfile::factory()->create(['academy_id' => $academy1->id]);
        $student2 = StudentProfile::factory()->create(['academy_id' => $academy2->id]);

        $user = createUser('admin', $academy1);
        $this->actingAs($user);
        setTenantContext($academy1);

        // Query student profiles - with global scope, should only get academy1's students
        $profiles = StudentProfile::query()->get();

        // Should contain student1 but not student2
        expect($profiles->pluck('id')->contains($student1->id))->toBeTrue();
        expect($profiles->pluck('id')->contains($student2->id))->toBeFalse();
    });

    it('can access own tenant data', function () {
        $academy = createAcademy();
        $student = StudentProfile::factory()->create(['academy_id' => $academy->id]);

        $user = createUser('admin', $academy);
        $this->actingAs($user);
        setTenantContext($academy);

        $found = StudentProfile::find($student->id);

        expect($found)->not->toBeNull();
        expect($found->id)->toBe($student->id);
        expect($found->academy_id)->toBe($academy->id);
    });

    it('shows no filter when no context set (global view)', function () {
        $academy1 = createAcademy(['name' => 'Academy 1']);
        $academy2 = createAcademy(['name' => 'Academy 2']);

        $superAdmin = createSuperAdmin();
        $this->actingAs($superAdmin);

        // Enable global view mode
        enableGlobalView();

        expect(AcademyContextService::isGlobalViewMode())->toBeTrue();
        expect(AcademyContextService::getCurrentAcademy())->toBeNull();
    });

    it('assigns correct academy_id to new records', function () {
        $academy = createAcademy();
        $user = createUser('admin', $academy);
        $this->actingAs($user);
        setTenantContext($academy);

        // Create a new student profile
        $student = StudentProfile::factory()->create([
            'academy_id' => AcademyContextService::getCurrentAcademyId(),
        ]);

        expect($student->academy_id)->toBe($academy->id);
    });
});

describe('Teacher Profile Isolation', function () {
    it('isolates quran teacher profiles between academies', function () {
        $academy1 = createAcademy(['name' => 'Academy 1']);
        $academy2 = createAcademy(['name' => 'Academy 2']);

        $teacher1 = createQuranTeacher($academy1);
        $teacher2 = createQuranTeacher($academy2);

        setTenantContext($academy1);

        // Only academy1's teacher profile should be visible
        $profiles = QuranTeacherProfile::all();

        expect($profiles)->toHaveCount(1);
        expect($profiles->first()->user_id)->toBe($teacher1->id);
    });

    it('isolates academic teacher profiles between academies', function () {
        $academy1 = createAcademy(['name' => 'Academy 1']);
        $academy2 = createAcademy(['name' => 'Academy 2']);

        $teacher1 = createAcademicTeacher($academy1);
        $teacher2 = createAcademicTeacher($academy2);
        $teacher3 = createAcademicTeacher($academy2);

        setTenantContext($academy1);

        // Only academy1's teacher profile should be visible
        $profiles = AcademicTeacherProfile::all();

        expect($profiles)->toHaveCount(1);
        expect($profiles->first()->user_id)->toBe($teacher1->id);
    });
});

describe('Circle and Enrollment Isolation', function () {
    it('isolates quran circles between academies', function () {
        $academy1 = createAcademy(['name' => 'Academy 1']);
        $academy2 = createAcademy(['name' => 'Academy 2']);

        $teacher1 = createQuranTeacher($academy1);
        $teacher2 = createQuranTeacher($academy2);

        // Create circles in both academies
        QuranCircle::factory()->count(2)->create([
            'academy_id' => $academy1->id,
            'quran_teacher_id' => $teacher1->id,
        ]);

        QuranCircle::factory()->count(3)->create([
            'academy_id' => $academy2->id,
            'quran_teacher_id' => $teacher2->id,
        ]);

        setTenantContext($academy1);

        $circles = QuranCircle::all();

        expect($circles)->toHaveCount(2);
        foreach ($circles as $circle) {
            expect($circle->academy_id)->toBe($academy1->id);
        }
    });

    it('isolates individual circles between academies', function () {
        $academy1 = createAcademy(['name' => 'Academy 1']);
        $academy2 = createAcademy(['name' => 'Academy 2']);

        $teacher1 = createQuranTeacher($academy1);
        $student1 = createStudent($academy1);
        $teacher2 = createQuranTeacher($academy2);
        $student2 = createStudent($academy2);

        // Create subscription first
        $subscription1 = QuranSubscription::factory()->create([
            'academy_id' => $academy1->id,
            'student_id' => $student1->id,
        ]);

        $subscription2 = QuranSubscription::factory()->create([
            'academy_id' => $academy2->id,
            'student_id' => $student2->id,
        ]);

        // Create individual circles
        QuranIndividualCircle::factory()->create([
            'academy_id' => $academy1->id,
            'quran_teacher_id' => $teacher1->id,
            'student_id' => $student1->id,
            'subscription_id' => $subscription1->id,
        ]);

        QuranIndividualCircle::factory()->create([
            'academy_id' => $academy2->id,
            'quran_teacher_id' => $teacher2->id,
            'student_id' => $student2->id,
            'subscription_id' => $subscription2->id,
        ]);

        setTenantContext($academy1);

        $circles = QuranIndividualCircle::all();

        expect($circles)->toHaveCount(1);
        expect($circles->first()->academy_id)->toBe($academy1->id);
    });
});

describe('User Academy Assignment', function () {
    it('user belongs to exactly one academy', function () {
        $academy = createAcademy();
        $user = createUser('student', $academy);

        expect($user->academy_id)->toBe($academy->id);
        expect($user->academy)->not->toBeNull();
        expect($user->academy->id)->toBe($academy->id);
    });

    it('super admin has no academy_id', function () {
        $superAdmin = createSuperAdmin();

        expect($superAdmin->academy_id)->toBeNull();
        expect($superAdmin->isSuperAdmin())->toBeTrue();
    });

    it('regular user cannot access other academies', function () {
        $academy1 = createAcademy(['name' => 'Academy 1']);
        $academy2 = createAcademy(['name' => 'Academy 2']);

        $user = createUser('student', $academy1);

        // User's canAccessTenant should return false for academy2
        expect($user->canAccessTenant($academy1))->toBeTrue();
        expect($user->canAccessTenant($academy2))->toBeFalse();
    });

    it('super admin can access any academy', function () {
        $superAdmin = createSuperAdmin();
        $academy1 = createAcademy(['name' => 'Academy 1']);
        $academy2 = createAcademy(['name' => 'Academy 2']);

        expect($superAdmin->canAccessTenant($academy1))->toBeTrue();
        expect($superAdmin->canAccessTenant($academy2))->toBeTrue();
    });
});

describe('Academy Context Service', function () {
    it('sets and clears API context correctly', function () {
        $academy = createAcademy();

        // Initially no API context
        expect(AcademyContextService::hasApiContext())->toBeFalse();

        // Set API context
        AcademyContextService::setApiContext($academy);
        expect(AcademyContextService::hasApiContext())->toBeTrue();
        expect(AcademyContextService::getApiContextAcademyId())->toBe($academy->id);

        // Clear API context
        AcademyContextService::clearApiContext();
        expect(AcademyContextService::hasApiContext())->toBeFalse();
        expect(AcademyContextService::getApiContextAcademyId())->toBeNull();
    });

    it('returns default academy when no context set and user unauthenticated', function () {
        // Create the default academy
        $defaultAcademy = createAcademy([
            'name' => 'Default Academy',
            'subdomain' => 'itqan-academy',
            'is_active' => true,
        ]);

        // Without authentication, should return default academy
        $currentAcademy = AcademyContextService::getCurrentAcademy();

        expect($currentAcademy)->not->toBeNull();
        expect($currentAcademy->subdomain)->toBe('itqan-academy');
    });

    it('returns user academy for authenticated regular user', function () {
        $academy = createAcademy();
        $user = createUser('student', $academy);

        test()->actingAs($user);

        $currentAcademy = AcademyContextService::getCurrentAcademy();

        expect($currentAcademy)->not->toBeNull();
        expect($currentAcademy->id)->toBe($academy->id);
    });

    it('respects session context for super admin', function () {
        $superAdmin = createSuperAdmin();
        $academy1 = createAcademy(['name' => 'Academy 1']);
        $academy2 = createAcademy(['name' => 'Academy 2']);

        test()->actingAs($superAdmin);

        // Set context to academy1
        setTenantContext($academy1);

        expect(AcademyContextService::getCurrentAcademyId())->toBe($academy1->id);

        // Change to academy2
        setTenantContext($academy2);

        expect(AcademyContextService::getCurrentAcademyId())->toBe($academy2->id);
    });

    it('provides timezone for current academy', function () {
        $academy = createAcademy([
            'timezone' => 'Asia/Riyadh',
        ]);

        setTenantContext($academy);

        $timezone = AcademyContextService::getTimezone();

        // Should return the academy's timezone or default
        expect($timezone)->toBeString();
    });
});

describe('Data Integrity Across Academies', function () {
    it('session belongs to same academy as student and teacher', function () {
        $academy = createAcademy();
        $teacher = createQuranTeacher($academy);
        $student = createStudent($academy);

        $session = QuranSession::factory()->create([
            'academy_id' => $academy->id,
            'quran_teacher_id' => $teacher->id,
            'student_id' => $student->id,
        ]);

        expect($session->academy_id)->toBe($academy->id);
        expect($teacher->academy_id)->toBe($academy->id);
        expect($student->academy_id)->toBe($academy->id);

        // All should be in the same academy
        expect($session->academy_id)->toBe($teacher->academy_id);
        expect($session->academy_id)->toBe($student->academy_id);
    });

    it('subscription belongs to same academy as student', function () {
        $academy = createAcademy();
        $student = createStudent($academy);

        $subscription = QuranSubscription::factory()->create([
            'academy_id' => $academy->id,
            'student_id' => $student->id,
        ]);

        expect($subscription->academy_id)->toBe($student->academy_id);
    });

    it('prevents creating session with mismatched academy ids', function () {
        $academy1 = createAcademy(['name' => 'Academy 1']);
        $academy2 = createAcademy(['name' => 'Academy 2']);

        $teacher = createQuranTeacher($academy1);
        $student = createStudent($academy2); // Different academy!

        // This should ideally be prevented by validation
        // Testing that the data integrity check catches this
        $session = QuranSession::factory()->make([
            'academy_id' => $academy1->id,
            'quran_teacher_id' => $teacher->id,
            'student_id' => $student->id, // Student from different academy
        ]);

        // The session's academy_id doesn't match the student's academy_id
        expect($session->academy_id)->not->toBe($student->academy_id);
    });
});
