<?php

/**
 * Panel Access Tests
 *
 * Only two Filament panels remain after the teacher/academic-teacher/supervisor
 * panels were removed:
 *   - Admin Panel (/admin): Path-based, super_admin only
 *   - Academy Panel (/panel/{tenant}): Tenant in URL, admin + super_admin
 *
 * Teachers and supervisors use the frontend routes under /teacher and /manage.
 */

beforeEach(function () {
    clearTenantContext();
});

afterEach(function () {
    clearTenantContext();
});

describe('Admin Panel Access (Path-based)', function () {
    it('redirects unauthenticated users to frontend login', function () {
        $response = $this->get('/admin');
        $response->assertRedirect();
        expect($response->headers->get('Location'))->toContain('/login');
        expect($response->headers->get('Location'))->not->toContain('/admin/login');
    });

    it('allows super admin to access admin panel', function () {
        $superAdmin = createSuperAdmin();

        $this->actingAs($superAdmin)
            ->get('/admin')
            ->assertOk();
    });

    it('denies regular admin access to admin panel (super admin only)', function () {
        $academy = createAcademy();
        $admin = createAdmin($academy);

        // Admin panel is reserved for super admins only
        // Regular admins should use academy panel
        $this->actingAs($admin)
            ->get('/admin')
            ->assertForbidden();
    });

    it('denies student access to admin panel', function () {
        $academy = createAcademy();
        $student = createStudent($academy);

        $this->actingAs($student)
            ->get('/admin')
            ->assertForbidden();
    });

    it('denies regular teacher access to admin panel', function () {
        $academy = createAcademy();
        $teacher = createQuranTeacher($academy);

        $this->actingAs($teacher)
            ->get('/admin')
            ->assertForbidden();
    });
});

describe('Academy Panel Access (Tenant in URL)', function () {
    it('redirects unauthenticated users to frontend login', function () {
        $response = $this->get('/panel');
        $response->assertRedirect();
        expect($response->headers->get('Location'))->toContain('/login');
        expect($response->headers->get('Location'))->not->toContain('/panel/login');
    });

    /**
     * Academy Panel uses tenant routing (/panel/{tenant}). Full HTTP integration
     * testing requires proper tenant binding setup; we verify canAccessPanel here.
     */
    it('admin can access academy panel (via canAccessPanel check)', function () {
        $academy = createAcademy();
        $admin = createAdmin($academy);

        expect($admin->canAccessPanel(\Filament\Facades\Filament::getPanel('academy')))->toBeTrue();
    });

    it('super admin can access academy panel (via canAccessPanel check)', function () {
        $superAdmin = createSuperAdmin();

        expect($superAdmin->canAccessPanel(\Filament\Facades\Filament::getPanel('academy')))->toBeTrue();
    });
});

describe('Cross-Panel Access Logic', function () {
    it('super admin can access all remaining panels via canAccessPanel', function () {
        $superAdmin = createSuperAdmin();

        foreach (['admin', 'academy'] as $panelId) {
            $panel = \Filament\Facades\Filament::getPanel($panelId);
            expect($superAdmin->canAccessPanel($panel))
                ->toBeTrue("SuperAdmin should access {$panelId} panel");
        }
    });

    it('regular admin can only access academy panel (not admin panel)', function () {
        $academy = createAcademy();
        $admin = createAdmin($academy);

        // Can access academy panel
        expect($admin->canAccessPanel(\Filament\Facades\Filament::getPanel('academy')))->toBeTrue();

        // Cannot access admin panel (super admin only)
        expect($admin->canAccessPanel(\Filament\Facades\Filament::getPanel('admin')))->toBeFalse();
    });

    it('quran teacher cannot access any Filament panel', function () {
        $academy = createAcademy();
        $teacher = createQuranTeacher($academy);

        expect($teacher->canAccessPanel(\Filament\Facades\Filament::getPanel('academy')))->toBeFalse();
        expect($teacher->canAccessPanel(\Filament\Facades\Filament::getPanel('admin')))->toBeFalse();
    });

    it('academic teacher cannot access any Filament panel', function () {
        $academy = createAcademy();
        $teacher = createAcademicTeacher($academy);

        expect($teacher->canAccessPanel(\Filament\Facades\Filament::getPanel('academy')))->toBeFalse();
        expect($teacher->canAccessPanel(\Filament\Facades\Filament::getPanel('admin')))->toBeFalse();
    });

    it('supervisor cannot access any Filament panel', function () {
        $academy = createAcademy();
        $supervisor = createSupervisor($academy);

        expect($supervisor->canAccessPanel(\Filament\Facades\Filament::getPanel('academy')))->toBeFalse();
        expect($supervisor->canAccessPanel(\Filament\Facades\Filament::getPanel('admin')))->toBeFalse();
    });
});

describe('User Role Helper Methods', function () {
    it('correctly identifies super admin', function () {
        $superAdmin = createSuperAdmin();

        expect($superAdmin->isSuperAdmin())->toBeTrue();
        // Note: isAdmin() returns true for both 'admin' and 'super_admin' types
        // This is by design - super admins ARE admins
        expect($superAdmin->isAdmin())->toBeTrue();
        expect($superAdmin->isQuranTeacher())->toBeFalse();
    });

    it('correctly identifies regular admin', function () {
        $academy = createAcademy();
        $admin = createAdmin($academy);

        expect($admin->isAdmin())->toBeTrue();
        expect($admin->isSuperAdmin())->toBeFalse();
        expect($admin->isQuranTeacher())->toBeFalse();
    });

    it('correctly identifies quran teacher', function () {
        $academy = createAcademy();
        $teacher = createQuranTeacher($academy);

        expect($teacher->isQuranTeacher())->toBeTrue();
        expect($teacher->isAcademicTeacher())->toBeFalse();
        expect($teacher->isAdmin())->toBeFalse();
    });

    it('correctly identifies academic teacher', function () {
        $academy = createAcademy();
        $teacher = createAcademicTeacher($academy);

        expect($teacher->isAcademicTeacher())->toBeTrue();
        expect($teacher->isQuranTeacher())->toBeFalse();
        expect($teacher->isAdmin())->toBeFalse();
    });

    it('correctly identifies supervisor', function () {
        $academy = createAcademy();
        $supervisor = createSupervisor($academy);

        expect($supervisor->hasRole('supervisor'))->toBeTrue();
        expect($supervisor->isSuperAdmin())->toBeFalse();
    });

    it('correctly identifies student', function () {
        $academy = createAcademy();
        $student = createStudent($academy);

        expect($student->isStudent())->toBeTrue();
        expect($student->isAdmin())->toBeFalse();
        expect($student->isQuranTeacher())->toBeFalse();
    });
});
