<?php

/*
|--------------------------------------------------------------------------
| Test Case
|--------------------------------------------------------------------------
*/

use Illuminate\Foundation\Testing\RefreshDatabase;

pest()->extend(Tests\TestCase::class)
    ->use(RefreshDatabase::class)
    ->in('Feature', 'Unit');

/*
|--------------------------------------------------------------------------
| Expectations
|--------------------------------------------------------------------------
*/

expect()->extend('toBeOne', function () {
    return $this->toBe(1);
});

/*
|--------------------------------------------------------------------------
| Helper Functions
|--------------------------------------------------------------------------
*/

/**
 * Create an academy for testing
 */
function createAcademy(array $attributes = []): \App\Models\Academy
{
    return \App\Models\Academy::factory()->create(array_merge([
        'name' => 'Test Academy ' . uniqid(),
        'subdomain' => 'test-' . uniqid(),
        'is_active' => true,
    ], $attributes));
}

/**
 * Create a user with specific role
 */
function createUser(string $type = 'student', ?\App\Models\Academy $academy = null): \App\Models\User
{
    $academy = $academy ?? createAcademy();

    return \App\Models\User::factory()->create([
        'user_type' => $type,
        'academy_id' => $academy->id,
        'active_status' => true,
    ]);
}

/**
 * Create a super admin user (no academy_id)
 */
function createSuperAdmin(): \App\Models\User
{
    return \App\Models\User::factory()->superAdmin()->create();
}

/**
 * Set the tenant context for testing
 */
function setTenantContext(\App\Models\Academy $academy): void
{
    // Set session context
    session()->put(\App\Services\AcademyContextService::SELECTED_ACADEMY_SESSION_KEY, $academy->id);
    session()->put(\App\Services\AcademyContextService::ACADEMY_OBJECT_SESSION_KEY, $academy);
    session()->forget(\App\Services\AcademyContextService::GLOBAL_VIEW_SESSION_KEY);

    // Set API context
    \App\Services\AcademyContextService::setApiContext($academy);
}

/**
 * Clear the tenant context for testing
 */
function clearTenantContext(): void
{
    session()->forget(\App\Services\AcademyContextService::SELECTED_ACADEMY_SESSION_KEY);
    session()->forget(\App\Services\AcademyContextService::ACADEMY_OBJECT_SESSION_KEY);
    session()->forget(\App\Services\AcademyContextService::GLOBAL_VIEW_SESSION_KEY);

    \App\Services\AcademyContextService::clearApiContext();
}

/**
 * Enable global view mode for super admin
 */
function enableGlobalView(): void
{
    session()->put(\App\Services\AcademyContextService::GLOBAL_VIEW_SESSION_KEY, true);
    session()->forget(\App\Services\AcademyContextService::SELECTED_ACADEMY_SESSION_KEY);
    session()->forget(\App\Services\AcademyContextService::ACADEMY_OBJECT_SESSION_KEY);

    \App\Services\AcademyContextService::clearApiContext();
}
