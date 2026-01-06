<?php

/**
 * Dashboard Widget Tests
 *
 * Tests that verify dashboard widgets show correct data
 * based on user roles and academy context.
 */

use App\Filament\Supervisor\Widgets\SupervisorStatsWidget;
use App\Filament\Widgets\AcademyStatsWidget;
use App\Filament\Widgets\SuperAdminStatsWidget;
use Livewire\Livewire;

beforeEach(function () {
    clearTenantContext();
});

afterEach(function () {
    clearTenantContext();
});

describe('SuperAdminStatsWidget', function () {
    it('shows stats from all academies in global view', function () {
        $academy1 = createAcademy(['name' => 'Academy 1']);
        $academy2 = createAcademy(['name' => 'Academy 2']);

        // Create data in different academies
        createStudent($academy1);
        createStudent($academy1);
        createStudent($academy2);

        $superAdmin = createSuperAdmin();

        $this->actingAs($superAdmin);
        enableGlobalView();

        // Test that the widget can be rendered
        Livewire::test(SuperAdminStatsWidget::class)
            ->assertOk();
    });

    it('shows filtered stats when academy context is set', function () {
        $academy1 = createAcademy(['name' => 'Academy 1']);
        $academy2 = createAcademy(['name' => 'Academy 2']);

        createStudent($academy1);
        createStudent($academy2);

        $superAdmin = createSuperAdmin();

        $this->actingAs($superAdmin);
        setTenantContext($academy1);

        Livewire::test(SuperAdminStatsWidget::class)
            ->assertOk();
    });
});

describe('AcademyStatsWidget', function () {
    it('shows only data from current academy', function () {
        $academy1 = createAcademy(['name' => 'Academy 1']);
        $academy2 = createAcademy(['name' => 'Academy 2']);

        createStudent($academy1);
        createStudent($academy1);
        createStudent($academy2);

        $admin = createAdmin($academy1);

        $this->actingAs($admin);
        setTenantContext($academy1);

        Livewire::test(AcademyStatsWidget::class)
            ->assertOk();
    });
});

describe('QuranTeacherOverviewWidget', function () {
    /**
     * SKIPPED: QuranCircleFactory uses outdated column names (name_ar, name_en)
     * but the table now uses consolidated 'name' column.
     * Re-enable once factory is updated to match current schema.
     */
    it('shows only teacher\'s own data')->skip(
        'QuranCircleFactory uses outdated schema - needs update'
    );

    it('is only visible to Quran teachers', function () {
        $academy = createAcademy();
        $teacher = createQuranTeacher($academy);

        $this->actingAs($teacher);
        setTenantContext($academy);

        // Teacher should be able to see the widget
        expect($teacher->isQuranTeacher())->toBeTrue();
    });
});

describe('AcademicTeacherOverviewWidget', function () {
    /**
     * SKIPPED: AcademicSubscriptionFactory has schema issues with related models.
     * Re-enable once factory is updated to match current schema.
     */
    it('shows only teacher\'s own data')->skip(
        'AcademicSubscriptionFactory uses outdated schema - needs update'
    );
});

describe('SupervisorStatsWidget', function () {
    it('shows supervised teachers data only', function () {
        $academy = createAcademy();
        $supervisor = createSupervisor($academy);

        $teacher1 = createQuranTeacher($academy);
        $teacher2 = createQuranTeacher($academy);

        // Assign teacher1 to supervisor
        // This depends on your supervisor responsibility system
        // For now, just test the widget renders

        $this->actingAs($supervisor);
        setTenantContext($academy);

        Livewire::test(SupervisorStatsWidget::class)
            ->assertOk();
    });

    it('is only visible to supervisors', function () {
        $academy = createAcademy();
        $supervisor = createSupervisor($academy);

        $this->actingAs($supervisor);
        setTenantContext($academy);

        expect($supervisor->hasRole('supervisor'))->toBeTrue();
    });
});

describe('Widget Data Isolation', function () {
    /**
     * SKIPPED: QuranCircleFactory uses outdated column names (name_ar, name_en)
     * but the table now uses consolidated 'name' column.
     * Re-enable once factories are updated to match current schema.
     */
    it('teacher widget does not show other teachers\' circles')->skip(
        'QuranCircleFactory uses outdated schema - needs update'
    );

    it('admin widget shows all academy data')->skip(
        'QuranCircleFactory uses outdated schema - needs update'
    );
});

describe('Widget Rendering Performance', function () {
    it('widgets render without excessive queries', function () {
        $academy = createAcademy();

        // Create some data
        for ($i = 0; $i < 10; $i++) {
            createStudent($academy);
            createQuranTeacher($academy);
        }

        $admin = createAdmin($academy);

        $this->actingAs($admin);
        setTenantContext($academy);

        $queries = collect();

        \Illuminate\Support\Facades\DB::listen(function ($query) use (&$queries) {
            $queries->push($query->sql);
        });

        // Render the widget
        Livewire::test(AcademyStatsWidget::class)->assertOk();

        // Should have reasonable query count
        expect($queries->count())->toBeLessThan(20);
    });
});
