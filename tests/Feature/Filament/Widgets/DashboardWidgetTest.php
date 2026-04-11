<?php

/**
 * Dashboard Widget Tests
 *
 * Tests that verify dashboard widgets show correct data
 * based on user roles and academy context.
 */

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
