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
        if (class_exists(\App\Services\AcademyContextService::class)) {
            \Illuminate\Support\Facades\Session::flush();
        }
    }
}
