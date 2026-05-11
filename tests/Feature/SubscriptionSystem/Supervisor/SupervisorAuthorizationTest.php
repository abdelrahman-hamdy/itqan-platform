<?php

declare(strict_types=1);

use App\Models\QuranSubscription;
use App\Models\SupervisorResponsibility;
use App\Models\User;

/**
 * Asserts the role-gating + supervisor-scope rules for the
 * `/manage/subscriptions/*` controller surface.
 *
 *   - super_admin / academy admin / supervisor with permission can access.
 *   - supervisors are limited to assigned teachers' subscriptions
 *     (`ensureSubscriptionInScope`).
 *   - non-allowed roles (student, parent, teacher) are blocked at the route
 *     middleware (`role:supervisor,super_admin,admin`).
 *   - cross-tenant access is blocked even for super_admins (subdomain
 *     resolution via `ResolveTenantFromSubdomain` + scoped queries).
 *
 * See `app/Http/Controllers/Supervisor/BaseSupervisorWebController.php` and
 * `routes/web/supervisor-education.php` for the surface under test.
 */
beforeEach(function () {
    $this->academy = createAcademy(['subdomain' => 'auth-test-'.uniqid()]);
});

/** Helper: enable subscription permissions on a supervisor's profile. */
function grantSubscriptionPermissions(User $supervisor, bool $manage = true, bool $view = true): void
{
    $supervisor->supervisorProfile->update([
        'can_manage_subscriptions' => $manage,
        'can_view_subscriptions' => $view,
    ]);
    $supervisor->load('supervisorProfile');
}

/** Helper: assign a Quran or Academic teacher to a supervisor via pivot. */
function assignTeacherToSupervisor(User $supervisor, User $teacher): void
{
    SupervisorResponsibility::create([
        'supervisor_profile_id' => $supervisor->supervisorProfile->id,
        'responsable_type' => User::class,
        'responsable_id' => $teacher->id,
    ]);
}

describe('role middleware gating on the index page', function () {
    it('A1 — super_admin can load the subscriptions index', function () {
        $superAdmin = createSuperAdmin();

        $response = $this->actingAs($superAdmin)
            ->get(route('manage.subscriptions.index', ['subdomain' => $this->academy->subdomain]));

        $response->assertOk();
    });

    it('A2 — academy admin can load the subscriptions index', function () {
        $admin = createAdmin($this->academy);

        $response = $this->actingAs($admin)
            ->get(route('manage.subscriptions.index', ['subdomain' => $this->academy->subdomain]));

        $response->assertOk();
    });

    it('A3 — supervisor with can_view_subscriptions can load the index', function () {
        $supervisor = createSupervisor($this->academy);
        grantSubscriptionPermissions($supervisor, manage: false, view: true);

        $response = $this->actingAs($supervisor)
            ->get(route('manage.subscriptions.index', ['subdomain' => $this->academy->subdomain]));

        $response->assertOk();
    });

    it('A4 — supervisor without subscription permissions gets 403 on index', function () {
        $supervisor = createSupervisor($this->academy);
        // Default factory grants no subscription permissions.

        $response = $this->actingAs($supervisor)
            ->get(route('manage.subscriptions.index', ['subdomain' => $this->academy->subdomain]));

        $response->assertForbidden();
    });

    it('A7 — student/parent/teacher are blocked at role middleware on a mutation', function () {
        $student = createStudent($this->academy);
        $teacher = createQuranTeacher($this->academy);

        $sub = QuranSubscription::factory()
            ->forStudent($student)
            ->forTeacher($teacher)
            ->active()
            ->create();

        // Student attempting to pause their own subscription.
        $studentResp = $this->actingAs($student)->post(
            route('manage.subscriptions.pause', [
                'subdomain' => $this->academy->subdomain,
                'type' => 'quran',
                'subscription' => $sub->id,
            ])
        );
        // Role middleware aborts with 403, not a redirect.
        $studentResp->assertForbidden();

        // Teacher attempting to extend.
        $teacherResp = $this->actingAs($teacher)->post(
            route('manage.subscriptions.extend', [
                'subdomain' => $this->academy->subdomain,
                'type' => 'quran',
                'subscription' => $sub->id,
            ]),
            ['extend_days' => 5]
        );
        $teacherResp->assertForbidden();
    });
});

describe('supervisor scope on resource access', function () {
    it('A5 — supervisor sees their assigned teacher\'s subscription on the show page', function () {
        $supervisor = createSupervisor($this->academy);
        grantSubscriptionPermissions($supervisor);
        $assignedTeacher = createQuranTeacher($this->academy);
        assignTeacherToSupervisor($supervisor, $assignedTeacher);

        $student = createStudent($this->academy);
        $sub = QuranSubscription::factory()
            ->forStudent($student)
            ->forTeacher($assignedTeacher)
            ->active()
            ->create();

        $response = $this->actingAs($supervisor)->get(
            route('manage.subscriptions.show', [
                'subdomain' => $this->academy->subdomain,
                'type' => 'quran',
                'subscription' => $sub->id,
            ])
        );

        $response->assertOk();
    });

    it('A6 — supervisor accessing a non-assigned teacher\'s subscription gets 403', function () {
        $supervisor = createSupervisor($this->academy);
        grantSubscriptionPermissions($supervisor);

        // No assignment: the supervisor has zero teachers in their pivot.
        $unassignedTeacher = createQuranTeacher($this->academy);
        $student = createStudent($this->academy);
        $sub = QuranSubscription::factory()
            ->forStudent($student)
            ->forTeacher($unassignedTeacher)
            ->active()
            ->create();

        $response = $this->actingAs($supervisor)->get(
            route('manage.subscriptions.show', [
                'subdomain' => $this->academy->subdomain,
                'type' => 'quran',
                'subscription' => $sub->id,
            ])
        );

        $response->assertForbidden();
    });

    it('A8 — supervisor cannot mutate a subscription belonging to a different academy', function () {
        // Two academies, two supervisors.
        $otherAcademy = createAcademy(['subdomain' => 'auth-test-other-'.uniqid()]);
        $supervisorA = createSupervisor($this->academy);
        grantSubscriptionPermissions($supervisorA);

        // Subscription lives in academy B; supervisor A has no assignment.
        $teacherB = createQuranTeacher($otherAcademy);
        $studentB = createStudent($otherAcademy);
        $subB = QuranSubscription::factory()
            ->forStudent($studentB)
            ->forTeacher($teacherB)
            ->active()
            ->create();

        // Even when hitting academy A's subdomain with a sub_id from academy B,
        // resolveSubscription succeeds (no academy guard there) but
        // ensureSubscriptionInScope rejects because supervisorA has no assignment.
        $response = $this->actingAs($supervisorA)->post(
            route('manage.subscriptions.pause', [
                'subdomain' => $this->academy->subdomain,
                'type' => 'quran',
                'subscription' => $subB->id,
            ])
        );

        $response->assertForbidden();
    });
});
