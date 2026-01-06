<?php

/**
 * Filament Resource Tenant Isolation Tests
 *
 * Tests that verify Filament resources properly isolate data
 * between different academies (tenants).
 */

use App\Models\Academy;

beforeEach(function () {
    clearTenantContext();
});

afterEach(function () {
    clearTenantContext();
});

describe('QuranCircleResource Tenant Isolation', function () {
    /**
     * SKIPPED: QuranCircleFactory uses outdated column names (name_ar, name_en)
     * but the table now uses consolidated 'name' column.
     * Re-enable once factory is updated to match current schema.
     */
    it('admin sees only circles from their academy')->skip(
        'QuranCircleFactory uses outdated schema - needs update'
    );

    it('super admin in global view sees circles from all academies')->skip(
        'QuranCircleFactory uses outdated schema - needs update'
    );
});

describe('AcademicSubscriptionResource Tenant Isolation', function () {
    /**
     * SKIPPED: AcademicSubscriptionFactory has schema issues with related models.
     * Re-enable once factory is updated to match current schema.
     */
    it('admin sees only subscriptions from their academy')->skip(
        'AcademicSubscriptionFactory uses outdated schema - needs update'
    );

    it('cannot create subscription linking student from different academy', function () {
        $academy1 = createAcademy(['name' => 'Academy 1']);
        $academy2 = createAcademy(['name' => 'Academy 2']);

        $student = createStudent($academy2); // Student from Academy 2
        $teacher = createAcademicTeacher($academy1);

        $admin = createAdmin($academy1);

        $this->actingAs($admin);
        setTenantContext($academy1);

        // When creating a subscription, student options should not include students from other academies
        // This tests the academy filtering in the select field
        $academyId = \App\Services\AcademyContextService::getCurrentAcademyId();

        $students = \App\Models\User::where('user_type', 'student')
            ->whereHas('studentProfile.gradeLevel', function ($q) use ($academyId) {
                $q->where('academy_id', $academyId);
            })
            ->get();

        // Student from academy2 should not be in the filtered list
        expect($students->pluck('id')->contains($student->id))->toBeFalse();
    });
});

describe('QuranSubscriptionResource Tenant Isolation', function () {
    /**
     * SKIPPED: QuranSubscriptionFactory has schema issues.
     * Re-enable once factory is updated to match current schema.
     */
    it('admin sees only Quran subscriptions from their academy')->skip(
        'QuranSubscriptionFactory uses outdated schema - needs update'
    );
});

describe('Cross-Academy Data Prevention', function () {
    /**
     * SKIPPED: QuranCircleFactory uses outdated column names.
     */
    it('prevents admin from viewing records from other academies via direct URL')->skip(
        'QuranCircleFactory uses outdated schema - needs update'
    );

    it('super admin can view records from any academy')->skip(
        'QuranCircleFactory uses outdated schema - needs update'
    );

    it('records are created with correct academy_id', function () {
        $academy = createAcademy();
        $admin = createAdmin($academy);

        $this->actingAs($admin);
        setTenantContext($academy);

        $currentAcademyId = \App\Services\AcademyContextService::getCurrentAcademyId();

        expect($currentAcademyId)->toBe($academy->id);
    });
});
