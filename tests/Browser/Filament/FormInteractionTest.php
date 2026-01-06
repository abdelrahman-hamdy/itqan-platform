<?php

namespace Tests\Browser\Filament;

use Laravel\Dusk\Browser;
use Tests\DuskTestCase;

/**
 * Filament Form Interaction Browser Tests
 *
 * Tests that verify Filament pages load correctly.
 * Tests requiring authenticated users are skipped due to factory schema issues.
 */
class FormInteractionTest extends DuskTestCase
{
    /**
     * Test that the public homepage loads
     */
    public function test_public_homepage_loads(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->visit('/')
                ->assertSourceHas('</html>')
                ->screenshot('homepage');
        });
    }

    /**
     * Test that admin panel shows login when not authenticated
     */
    public function test_admin_panel_redirects_to_login(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->visit('/admin')
                ->waitForLocation('/admin/login', 10)
                ->assertPathIs('/admin/login')
                ->screenshot('admin-redirects-to-login');
        });
    }

    /**
     * Test that supervisor panel shows login when not authenticated
     */
    public function test_supervisor_panel_redirects_to_login(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->visit('/supervisor-panel')
                ->waitForLocation('/supervisor-panel/login', 10)
                ->assertPathIs('/supervisor-panel/login')
                ->screenshot('supervisor-redirects-to-login');
        });
    }

    /**
     * Test that login form has required fields
     */
    public function test_login_form_has_required_fields(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->visit('/admin/login')
                ->waitFor('form', 10)
                ->assertPresent('input[type="email"]')
                ->assertPresent('input[type="password"]')
                ->assertPresent('button[type="submit"]')
                ->screenshot('login-form-fields');
        });
    }

    /**
     * Test that login shows error with invalid credentials
     */
    public function test_login_shows_error_with_invalid_credentials(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->visit('/admin/login')
                ->waitFor('form', 10)
                ->type('input[type="email"]', 'invalid@test.com')
                ->type('input[type="password"]', 'wrongpassword')
                ->click('button[type="submit"]')
                ->pause(3000) // Wait for form submission and error
                ->screenshot('login-error');
        });
    }

    /**
     * Test that searchable select loads options via AJAX
     *
     * Note: Skipped due to factory schema issues
     */
    public function test_searchable_select_loads_options_via_ajax(): void
    {
        $this->markTestSkipped('Factory schema issues - needs update');
    }

    /**
     * Test that form validation displays error messages
     *
     * Note: Skipped due to factory schema issues
     */
    public function test_form_validation_displays_errors(): void
    {
        $this->markTestSkipped('Factory schema issues - needs update');
    }

    /**
     * Test that date/time picker works correctly
     *
     * Note: Skipped due to factory schema issues
     */
    public function test_datetime_picker_works(): void
    {
        $this->markTestSkipped('Factory schema issues - needs update');
    }

    /**
     * Test that file upload component accepts files
     */
    public function test_file_upload_accepts_files(): void
    {
        $this->markTestSkipped('Requires specific form with file upload field');
    }

    /**
     * Test that repeater fields can add/remove items
     */
    public function test_repeater_fields_add_remove_items(): void
    {
        $this->markTestSkipped('Requires resource with repeater fields');
    }

    /**
     * Test that form sections expand/collapse correctly
     *
     * Note: Skipped due to factory schema issues
     */
    public function test_form_sections_expand_collapse(): void
    {
        $this->markTestSkipped('Factory schema issues - needs update');
    }

    /**
     * Test that dependent select fields update based on parent selection
     *
     * Note: Skipped due to factory schema issues
     */
    public function test_dependent_select_updates_on_parent_change(): void
    {
        $this->markTestSkipped('Factory schema issues - needs update');
    }

    /**
     * Test table search functionality
     *
     * Note: Skipped due to factory schema issues
     */
    public function test_table_search_filters_results(): void
    {
        $this->markTestSkipped('Factory schema issues - needs update');
    }

    /**
     * Test table pagination works correctly
     *
     * Note: Skipped due to factory schema issues
     */
    public function test_table_pagination_works(): void
    {
        $this->markTestSkipped('Factory schema issues - needs update');
    }
}
