<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    /**
     * Setup the test environment.
     */
    protected function setUp(): void
    {
        parent::setUp();

        // Clear any cached academy context
        // Wrap in try-catch to handle cases where database doesn't exist yet
        if (class_exists(\App\Services\AcademyContextService::class)) {
            try {
                \Illuminate\Support\Facades\Session::flush();
            } catch (\Exception $e) {
                // Silently ignore database errors during test setup
                // This allows unit tests to run without database access
            }
        }
    }
}
