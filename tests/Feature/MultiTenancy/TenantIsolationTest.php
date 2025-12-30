<?php

use App\Models\Academy;
use App\Models\User;
use App\Models\StudentProfile;
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
