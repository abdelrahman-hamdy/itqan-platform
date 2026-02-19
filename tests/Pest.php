<?php

pest()->extend(Tests\DuskTestCase::class)
//  ->use(Illuminate\Foundation\Testing\DatabaseMigrations::class)
    ->in('Browser');

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
        'name' => 'Test Academy '.uniqid(),
        'subdomain' => 'test-'.uniqid(),
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

/*
|--------------------------------------------------------------------------
| Filament Testing Helpers
|--------------------------------------------------------------------------
*/

/**
 * Create a supervisor user with profile
 * Note: Create as 'admin' first to bypass UserFactory's afterCreating profile creation
 */
function createSupervisor(?\App\Models\Academy $academy = null): \App\Models\User
{
    $academy = $academy ?? createAcademy();

    // Create as admin first to bypass UserFactory's profile creation
    $user = \App\Models\User::factory()->create([
        'user_type' => 'admin', // Temporary type
        'academy_id' => $academy->id,
        'active_status' => true,
    ]);

    // Update to supervisor type
    $user->update(['user_type' => 'supervisor']);

    // Create supervisor profile directly with correct columns
    // Note: supervisor_profiles table has: email (required), first_name, last_name, phone, avatar, gender, supervisor_code (auto), performance_rating, notes, can_manage_teachers
    // Note: is_active column does NOT exist on this table
    \App\Models\SupervisorProfile::create([
        'user_id' => $user->id,
        'academy_id' => $academy->id,
        'email' => fake()->unique()->safeEmail(),
        'first_name' => $user->name,
        'last_name' => 'Test',
        'phone' => fake()->phoneNumber(),
        'gender' => 'male',
    ]);

    // Fresh and load the supervisorProfile relationship
    return $user->fresh()->load('supervisorProfile');
}

/**
 * Create an admin user
 */
function createAdmin(?\App\Models\Academy $academy = null): \App\Models\User
{
    $academy = $academy ?? createAcademy();

    return \App\Models\User::factory()->create([
        'user_type' => 'admin',
        'academy_id' => $academy->id,
        'active_status' => true,
    ]);
}

/**
 * Create a Quran teacher with profile
 * Note: Create as 'admin' first to bypass UserFactory's afterCreating profile creation
 */
function createQuranTeacher(?\App\Models\Academy $academy = null): \App\Models\User
{
    $academy = $academy ?? createAcademy();

    // Create as admin first to bypass UserFactory's profile creation
    $user = \App\Models\User::factory()->create([
        'user_type' => 'admin', // Temporary type
        'academy_id' => $academy->id,
        'active_status' => true,
    ]);

    // Update to quran_teacher type
    $user->update(['user_type' => 'quran_teacher']);

    // Create profile directly with correct columns
    \App\Models\QuranTeacherProfile::create([
        'user_id' => $user->id,
        'academy_id' => $academy->id,
        'is_active' => true,
        'teacher_code' => 'QT-'.str_pad(rand(1, 9999), 4, '0', STR_PAD_LEFT),
        'gender' => 'male',
        'approval_status' => 'approved',
        'offers_trial_sessions' => true,
        'languages' => ['arabic'],
        'available_days' => ['sunday', 'monday', 'tuesday'],
    ]);

    return $user->fresh();
}

/**
 * Create an academic teacher with profile
 * Note: Create as 'admin' first to bypass UserFactory's afterCreating profile creation
 */
function createAcademicTeacher(?\App\Models\Academy $academy = null): \App\Models\User
{
    $academy = $academy ?? createAcademy();

    // Create as admin first to bypass UserFactory's profile creation
    $user = \App\Models\User::factory()->create([
        'user_type' => 'admin', // Temporary type
        'academy_id' => $academy->id,
        'active_status' => true,
    ]);

    // Update to academic_teacher type
    $user->update(['user_type' => 'academic_teacher']);

    // Create profile directly with correct columns
    \App\Models\AcademicTeacherProfile::create([
        'user_id' => $user->id,
        'academy_id' => $academy->id,
        'is_active' => true,
        'teacher_code' => 'AT-'.str_pad(rand(1, 9999), 4, '0', STR_PAD_LEFT),
        'gender' => 'male',
        'approval_status' => 'approved',
        'languages' => ['arabic'],
        'available_days' => ['sunday', 'monday', 'tuesday'],
    ]);

    return $user->fresh();
}

/**
 * Create a student with profile
 */
function createStudent(?\App\Models\Academy $academy = null): \App\Models\User
{
    $academy = $academy ?? createAcademy();

    $user = \App\Models\User::factory()->create([
        'user_type' => 'student',
        'academy_id' => $academy->id,
        'active_status' => true,
    ]);

    // Get or create a grade level for this academy
    // Use withoutGlobalScopes to bypass academy filtering during test setup
    $gradeLevel = \App\Models\AcademicGradeLevel::withoutGlobalScopes()
        ->where('academy_id', $academy->id)
        ->where('name', 'Default Grade Level')
        ->first();

    if (! $gradeLevel) {
        $gradeLevel = new \App\Models\AcademicGradeLevel;
        $gradeLevel->academy_id = $academy->id;
        $gradeLevel->name = 'Default Grade Level';
        $gradeLevel->is_active = true;
        $gradeLevel->saveQuietly();
    }

    // Create profile directly - use insert to bypass all model events and scopes
    $profileData = [
        'user_id' => $user->id,
        'academy_id' => $academy->id,
        'grade_level_id' => $gradeLevel->id,
        'student_code' => 'ST-'.uniqid(),
        'email' => fake()->unique()->safeEmail(),
        'first_name' => $user->name,
        'last_name' => '',
        'gender' => 'male',
        'enrollment_date' => now(),
        'created_at' => now(),
        'updated_at' => now(),
    ];

    \Illuminate\Support\Facades\DB::table('student_profiles')->insert($profileData);

    return $user->fresh();
}

/**
 * Test that a Filament resource page returns 200 for an authorized user
 */
function testFilamentResourceAccess(string $resourceClass, string $page = 'index'): void
{
    $slug = $resourceClass::getSlug();
    $url = match ($page) {
        'index' => "/admin/{$slug}",
        'create' => "/admin/{$slug}/create",
        default => "/admin/{$slug}",
    };

    test()->get($url)->assertOk();
}

/**
 * Test that a Filament panel login page is accessible
 */
function testFilamentPanelLogin(string $panelPath): void
{
    test()->get("/{$panelPath}/login")->assertOk();
}

/**
 * Get the count of queries executed during a callback
 */
function countQueries(callable $callback): int
{
    $count = 0;

    \Illuminate\Support\Facades\DB::listen(function () use (&$count) {
        $count++;
    });

    $callback();

    return $count;
}

/**
 * Assert that the query count is less than a threshold
 */
function assertQueryCountLessThan(int $threshold, callable $callback, string $message = ''): void
{
    $count = countQueries($callback);

    expect($count)->toBeLessThan($threshold, $message ?: "Query count ({$count}) exceeded threshold ({$threshold})");
}

/*
|--------------------------------------------------------------------------
| Session & Meeting Testing Helpers
|--------------------------------------------------------------------------
*/

/**
 * Create a session with meeting data already set up
 *
 * @param  string  $type  'quran', 'academic', or 'interactive'
 * @param  string  $status  Session status
 * @param  array  $overrides  Additional attributes to override
 */
function createSessionWithMeeting(
    string $type = 'quran',
    string $status = 'scheduled',
    array $overrides = []
): \App\Models\BaseSession {
    return \Tests\Helpers\TestHelpers::createSessionWithMeeting($type, $status, $overrides);
}

/**
 * Simulate a user joining a meeting via webhook
 */
function simulateMeetingJoin(\App\Models\BaseSession $session, \App\Models\User $user): \App\Models\MeetingAttendance
{
    return \Tests\Helpers\TestHelpers::simulateMeetingJoin($session, $user);
}

/**
 * Simulate a user leaving a meeting via webhook
 */
function simulateMeetingLeave(\App\Models\BaseSession $session, \App\Models\User $user): ?\App\Models\MeetingAttendance
{
    return \Tests\Helpers\TestHelpers::simulateMeetingLeave($session, $user);
}

/**
 * Advance a session to a specific status with appropriate timestamps
 */
function advanceSessionToStatus(\App\Models\BaseSession $session, string $targetStatus): \App\Models\BaseSession
{
    return \Tests\Helpers\TestHelpers::advanceSessionToStatus($session, $targetStatus);
}

/**
 * Create a student with an active subscription
 *
 * @return array{student: \App\Models\User, subscription: mixed, academy: \App\Models\Academy}
 */
function createStudentWithSubscription(
    string $type = 'quran',
    ?\App\Models\Academy $academy = null,
    int $sessionsRemaining = 10
): array {
    return \Tests\Helpers\TestHelpers::createStudentWithSubscription($type, $academy, $sessionsRemaining);
}

/**
 * Bind the LiveKit mock to the container
 */
function useLiveKitMock(): \Tests\Mocks\LiveKitMock
{
    return \Tests\Helpers\TestHelpers::useLiveKitMock();
}

/**
 * Freeze time to a specific datetime
 */
function freezeTime(\Carbon\Carbon|string $datetime): void
{
    \Tests\Helpers\TestHelpers::freezeTime($datetime);
}

/**
 * Unfreeze time
 */
function unfreezeTime(): void
{
    \Tests\Helpers\TestHelpers::unfreezeTime();
}

/*
|--------------------------------------------------------------------------
| Parent Testing Helpers
|--------------------------------------------------------------------------
*/

/**
 * Create a parent user with profile
 */
function createParent(?\App\Models\Academy $academy = null): \App\Models\User
{
    $academy = $academy ?? createAcademy();

    // Create as admin first to bypass UserFactory's profile creation
    $user = \App\Models\User::factory()->create([
        'user_type' => 'admin', // Temporary type
        'academy_id' => $academy->id,
        'active_status' => true,
    ]);

    // Update to parent type
    $user->update(['user_type' => 'parent']);

    // Create parent profile
    \App\Models\ParentProfile::create([
        'user_id' => $user->id,
        'academy_id' => $academy->id,
        'is_active' => true,
    ]);

    return $user->fresh();
}

/**
 * Link a child to a parent
 */
function linkChildToParent(\App\Models\User $parent, \App\Models\User $child): void
{
    $parentProfile = $parent->parentProfile;
    if ($parentProfile) {
        $parentProfile->children()->syncWithoutDetaching([$child->id]);
    }
}

/*
|--------------------------------------------------------------------------
| API Testing Helpers
|--------------------------------------------------------------------------
*/

/**
 * Create API headers with authentication token
 */
function apiHeaders(\App\Models\User $user): array
{
    $token = $user->createToken('test-token')->plainTextToken;

    return [
        'Authorization' => "Bearer {$token}",
        'Accept' => 'application/json',
        'Content-Type' => 'application/json',
    ];
}

/**
 * Acting as a user with API authentication
 */
function actingAsApi(\App\Models\User $user): \Illuminate\Testing\PendingCommand|\Illuminate\Foundation\Testing\TestCase
{
    return test()->actingAs($user, 'sanctum');
}

/*
|--------------------------------------------------------------------------
| Custom Expectations
|--------------------------------------------------------------------------
*/

expect()->extend('toHaveStatus', function (string $status) {
    return $this->status->value ?? $this->status === $status;
});

expect()->extend('toBeActiveSubscription', function () {
    return $this->status === 'active'
        && $this->remaining_sessions > 0
        && ($this->end_date === null || $this->end_date->isFuture());
});

expect()->extend('toHaveValidMeeting', function () {
    return $this->meeting_room_name !== null
        && $this->meeting_link !== null
        && ($this->meeting_expires_at === null || $this->meeting_expires_at->isFuture());
});
