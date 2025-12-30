<?php

use App\Models\Academy;
use App\Models\User;
use App\Services\AcademyContextService;

beforeEach(function () {
    clearTenantContext();
});

afterEach(function () {
    clearTenantContext();
});

describe('AcademyContextService', function () {
    it('returns null when no context is set', function () {
        // No user authenticated, no context set
        expect(AcademyContextService::getCurrentAcademyId())->toBeNull();
        expect(AcademyContextService::hasApiContext())->toBeFalse();
    });

    it('returns user academy for regular users', function () {
        $academy = createAcademy();
        $user = createUser('student', $academy);

        $this->actingAs($user);

        $currentAcademy = AcademyContextService::getCurrentAcademy();

        expect($currentAcademy)->not->toBeNull();
        expect($currentAcademy->id)->toBe($academy->id);
        expect(AcademyContextService::getCurrentAcademyId())->toBe($academy->id);
    });

    it('allows super admin to set context', function () {
        $superAdmin = createSuperAdmin();
        $academy = createAcademy();

        $this->actingAs($superAdmin);

        $result = AcademyContextService::setAcademyContext($academy->id);

        expect($result)->toBeTrue();
        expect(AcademyContextService::getCurrentAcademyId())->toBe($academy->id);
    });

    it('prevents non-super-admin from setting context', function () {
        $academy1 = createAcademy();
        $academy2 = createAcademy();
        $user = createUser('admin', $academy1);

        $this->actingAs($user);

        // Try to switch to another academy
        $result = AcademyContextService::setAcademyContext($academy2->id);

        expect($result)->toBeFalse();
        // User should still be in their original academy context
        expect(AcademyContextService::getCurrentAcademyId())->toBe($academy1->id);
    });

    it('sets API context correctly', function () {
        $academy = createAcademy();

        // Set API context directly (simulating middleware)
        AcademyContextService::setApiContext($academy);

        expect(AcademyContextService::hasApiContext())->toBeTrue();
        expect(AcademyContextService::getApiContextAcademyId())->toBe($academy->id);
        expect(AcademyContextService::getCurrentAcademyId())->toBe($academy->id);
    });

    it('API context takes priority over session', function () {
        $sessionAcademy = createAcademy(['name' => 'Session Academy']);
        $apiAcademy = createAcademy(['name' => 'API Academy']);

        $superAdmin = createSuperAdmin();
        $this->actingAs($superAdmin);

        // Set session context
        setTenantContext($sessionAcademy);

        // Now set API context (should override)
        AcademyContextService::setApiContext($apiAcademy);

        // API context should win
        expect(AcademyContextService::getCurrentAcademyId())->toBe($apiAcademy->id);

        // Clear API context
        AcademyContextService::clearApiContext();

        // Session context should now be used again
        expect(AcademyContextService::getCurrentAcademyId())->toBe($sessionAcademy->id);
    });
});
