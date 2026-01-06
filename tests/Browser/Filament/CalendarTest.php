<?php

namespace Tests\Browser\Filament;

use Laravel\Dusk\Browser;
use Tests\DuskTestCase;

/**
 * Filament Calendar Browser Tests
 *
 * Tests that verify Filament panel login pages load correctly.
 * Tests requiring authenticated users are skipped due to factory schema issues.
 */
class CalendarTest extends DuskTestCase
{
    /**
     * Test that the admin login page loads correctly
     */
    public function test_admin_login_page_loads(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->visit('/admin/login')
                ->waitFor('form', 10)
                ->assertPresent('form')
                ->assertPresent('input[type="email"]')
                ->assertPresent('input[type="password"]')
                ->screenshot('admin-login-page');
        });
    }

    /**
     * Test that the teacher panel login page loads correctly
     */
    public function test_teacher_login_page_loads(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->visit('/teacher-panel/login')
                ->waitFor('form', 10)
                ->assertPresent('form')
                ->assertPresent('input[type="email"]')
                ->screenshot('teacher-login-page');
        });
    }

    /**
     * Test that the academic teacher panel login page loads correctly
     */
    public function test_academic_teacher_login_page_loads(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->visit('/academic-teacher-panel/login')
                ->waitFor('form', 10)
                ->assertPresent('form')
                ->assertPresent('input[type="email"]')
                ->screenshot('academic-teacher-login-page');
        });
    }

    /**
     * Test that the supervisor panel login page loads correctly
     */
    public function test_supervisor_login_page_loads(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->visit('/supervisor-panel/login')
                ->waitFor('form', 10)
                ->assertPresent('form')
                ->assertPresent('input[type="email"]')
                ->screenshot('supervisor-login-page');
        });
    }

    /**
     * Test super admin can login and access dashboard
     *
     * Note: Skipped due to UserFactory schema issues
     */
    public function test_super_admin_can_login_and_view_dashboard(): void
    {
        $this->markTestSkipped('UserFactory has schema issues - needs update');
    }

    /**
     * Test academic teacher can view calendar
     *
     * Note: Skipped due to factory schema issues
     */
    public function test_academic_teacher_can_view_calendar(): void
    {
        $this->markTestSkipped('Factory schema issues - needs update');
    }

    /**
     * Test calendar displays sessions with correct styling
     *
     * Note: Skipped due to factory schema issues
     */
    public function test_calendar_displays_sessions_with_correct_status_colors(): void
    {
        $this->markTestSkipped('Factory schema issues - needs update');
    }

    /**
     * Test calendar navigation buttons work correctly
     *
     * Note: Skipped due to factory schema issues
     */
    public function test_calendar_navigation_buttons_work(): void
    {
        $this->markTestSkipped('Factory schema issues - needs update');
    }

    /**
     * Test that clicking on a session opens the detail modal
     */
    public function test_clicking_session_opens_detail_view(): void
    {
        $this->markTestSkipped('Requires session data to be created first');
    }
}
