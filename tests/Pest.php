<?php

/*
|--------------------------------------------------------------------------
| Test Case
|--------------------------------------------------------------------------
|
| The closure you provide to your test functions is always bound to a specific PHPUnit test
| case class. By default, that class is "PHPUnit\Framework\TestCase". Of course, you may
| need to change it using the "pest()" function to bind a different classes or traits.
|
*/

use Illuminate\Foundation\Testing\LazilyRefreshDatabase;

pest()->extend(Tests\TestCase::class)
    ->use(LazilyRefreshDatabase::class)
    ->in('Feature', 'Unit', 'Browser');

/*
|--------------------------------------------------------------------------
| Expectations
|--------------------------------------------------------------------------
|
| When you're writing tests, you often need to check that values meet certain conditions. The
| "expect()" function gives you access to a set of "expectations" methods that you can use
| to assert different things. Of course, you may extend the Expectation API at any time.
|
*/

expect()->extend('toBeOne', function () {
    return $this->toBe(1);
});

expect()->extend('toBeActive', function () {
    return $this->toHaveProperty('is_active', true)
        ->or($this->toHaveProperty('active_status', true));
});

expect()->extend('toBeValidModel', function () {
    return $this->toBeInstanceOf(Illuminate\Database\Eloquent\Model::class)
        ->and($this->value->exists)->toBeTrue();
});

expect()->extend('toHaveRelationship', function (string $relationship) {
    expect(method_exists($this->value, $relationship))->toBeTrue(
        "Method {$relationship} does not exist"
    );
    return $this;
});

/*
|--------------------------------------------------------------------------
| Functions
|--------------------------------------------------------------------------
|
| While Pest is very powerful out-of-the-box, you may have some testing code specific to your
| project that you don't want to repeat in every file. Here you can also expose helpers as
| global functions to help you to reduce the number of lines of code in your test files.
|
*/

/**
 * Create a super admin user and authenticate
 */
function asSuperAdmin(): Tests\TestCase
{
    $user = \App\Models\User::factory()->create([
        'user_type' => 'super_admin',
        'active_status' => true,
    ]);

    return test()->actingAs($user);
}

/**
 * Create an admin user and authenticate
 */
function asAdmin(?\App\Models\Academy $academy = null): Tests\TestCase
{
    $academy = $academy ?? createAcademy();

    $user = \App\Models\User::factory()->create([
        'user_type' => 'academy_admin',
        'academy_id' => $academy->id,
        'active_status' => true,
    ]);

    return test()->actingAs($user);
}

/**
 * Create a regular user and authenticate
 */
function asUser(string $type = 'student', ?\App\Models\Academy $academy = null): Tests\TestCase
{
    $academy = $academy ?? createAcademy();

    $user = \App\Models\User::factory()->create([
        'user_type' => $type,
        'academy_id' => $academy->id,
        'active_status' => true,
    ]);

    return test()->actingAs($user);
}

/**
 * Create a student user and authenticate
 */
function asStudent(?\App\Models\Academy $academy = null): Tests\TestCase
{
    return asUser('student', $academy);
}

/**
 * Create a parent user and authenticate
 */
function asParent(?\App\Models\Academy $academy = null): Tests\TestCase
{
    return asUser('parent', $academy);
}

/**
 * Create a quran teacher user and authenticate
 */
function asQuranTeacher(?\App\Models\Academy $academy = null): Tests\TestCase
{
    return asUser('quran_teacher', $academy);
}

/**
 * Create an academic teacher user and authenticate
 */
function asAcademicTeacher(?\App\Models\Academy $academy = null): Tests\TestCase
{
    return asUser('academic_teacher', $academy);
}

/**
 * Create an academy for testing
 */
function createAcademy(array $attributes = []): \App\Models\Academy
{
    return \App\Models\Academy::factory()->create(array_merge([
        'name' => 'Test Academy ' . uniqid(),
        'subdomain' => 'test-academy-' . uniqid(),
        'is_active' => true,
    ], $attributes));
}

/**
 * Create a user with specific role
 */
function createUser(string $type = 'student', ?\App\Models\Academy $academy = null, array $attributes = []): \App\Models\User
{
    $academy = $academy ?? createAcademy();

    return \App\Models\User::factory()->create(array_merge([
        'user_type' => $type,
        'academy_id' => $academy->id,
        'active_status' => true,
    ], $attributes));
}

/**
 * Generate a unique test identifier
 */
function testId(): string
{
    return 'test_' . uniqid();
}
