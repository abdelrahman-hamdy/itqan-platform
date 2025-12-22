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
