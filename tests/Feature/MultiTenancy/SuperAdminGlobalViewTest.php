<?php

use App\Models\Academy;
use App\Models\StudentProfile;
use App\Services\AcademyContextService;

beforeEach(function () {
    clearTenantContext();
});

afterEach(function () {
    clearTenantContext();
});

describe('Super Admin Global View', function () {
    it('allows super admin to enable global view', function () {
        $superAdmin = createSuperAdmin();
        $this->actingAs($superAdmin);

        $result = AcademyContextService::enableGlobalView();

        expect($result)->toBeTrue();
        expect(AcademyContextService::isGlobalViewMode())->toBeTrue();
        expect(AcademyContextService::getCurrentAcademy())->toBeNull();
    });

    it('prevents non-super-admin from enabling global view', function () {
        $academy = createAcademy();
        $user = createUser('admin', $academy);
        $this->actingAs($user);

        $result = AcademyContextService::enableGlobalView();

        expect($result)->toBeFalse();
        expect(AcademyContextService::isGlobalViewMode())->toBeFalse();
    });

    it('global view shows data from all academies', function () {
        $academy1 = createAcademy(['name' => 'Academy 1']);
        $academy2 = createAcademy(['name' => 'Academy 2']);

        $student1 = StudentProfile::factory()->create(['academy_id' => $academy1->id]);
        $student2 = StudentProfile::factory()->create(['academy_id' => $academy2->id]);

        $superAdmin = createSuperAdmin();
        $this->actingAs($superAdmin);

        // Enable global view
        AcademyContextService::enableGlobalView();

        expect(AcademyContextService::isGlobalViewMode())->toBeTrue();
        expect(AcademyContextService::getCurrentAcademyId())->toBeNull();

        // In global view mode, queries should not be filtered
        // The global scope should skip when isGlobalViewMode() is true
        $profiles = StudentProfile::withoutGlobalScopes()->get();

        expect($profiles->pluck('id'))->toContain($student1->id);
        expect($profiles->pluck('id'))->toContain($student2->id);
    });

    it('selecting academy disables global view', function () {
        $academy = createAcademy();
        $superAdmin = createSuperAdmin();
        $this->actingAs($superAdmin);

        // First enable global view
        AcademyContextService::enableGlobalView();
        expect(AcademyContextService::isGlobalViewMode())->toBeTrue();

        // Now select a specific academy
        AcademyContextService::setAcademyContext($academy->id);

        // Global view should be disabled
        expect(AcademyContextService::isGlobalViewMode())->toBeFalse();
        expect(AcademyContextService::getCurrentAcademyId())->toBe($academy->id);
    });
});
