<?php

declare(strict_types=1);

use Illuminate\Database\Eloquent\Model;

/**
 * Smoke test for the supervisor "Create subscription" wizard route.
 *
 * The wizard itself is a Livewire component
 * (`App\Livewire\Supervisor\CreateFullSubscription`); we don't assert
 * step-by-step interaction here — that's a job for a Livewire-specific test
 * suite. This smoke pass just confirms:
 *
 *   - the route exists and renders the create page (Livewire mounts inline)
 *   - role gating works (super_admin/admin OK; supervisor without permission
 *     would get 403 — but the route currently has no fine-grained permission
 *     check beyond the role middleware; a lifecycle gap also documented)
 *
 * See `routes/web/supervisor-education.php` line 144 (`subscriptions.create`).
 */
beforeEach(function () {
    Model::preventLazyLoading(false);

    $this->academy = createAcademy(['subdomain' => 'wizard-test-'.uniqid()]);
    $this->admin = createAdmin($this->academy);
});

afterEach(function () {
    Model::preventLazyLoading(true);
});

describe('GET /manage/subscriptions/create', function () {
    it('W1 — admin can load the create wizard page', function () {
        $response = $this->actingAs($this->admin)->get(
            route('manage.subscriptions.create', ['subdomain' => $this->academy->subdomain])
        );

        $response->assertOk();
        // Livewire's wire:id attribute is emitted on the root component div
        // when CreateFullSubscription mounts.
        $response->assertSee('wire:snapshot', false);
    });

    it('W2 — super_admin can load the create wizard page', function () {
        $superAdmin = createSuperAdmin();

        $response = $this->actingAs($superAdmin)->get(
            route('manage.subscriptions.create', ['subdomain' => $this->academy->subdomain])
        );

        $response->assertOk();
    });

    it('W3 — student / non-allowed role gets 403', function () {
        $student = createStudent($this->academy);

        $response = $this->actingAs($student)->get(
            route('manage.subscriptions.create', ['subdomain' => $this->academy->subdomain])
        );

        $response->assertForbidden();
    });

    it('W4 — supervisor with can_manage_subscriptions can mount the wizard, otherwise 403', function () {
        // CreateFullSubscription::mount() aborts 403 when the user is not
        // super_admin/admin and the supervisor profile lacks
        // can_manage_subscriptions. Tests both branches.
        $supervisorWithoutPerm = createSupervisor($this->academy);
        $denied = $this->actingAs($supervisorWithoutPerm)->get(
            route('manage.subscriptions.create', ['subdomain' => $this->academy->subdomain])
        );
        $denied->assertForbidden();

        // Same supervisor with can_manage_subscriptions = true → reaches the page.
        $supervisorWithoutPerm->supervisorProfile->update([
            'can_manage_subscriptions' => true,
        ]);
        $allowed = $this->actingAs($supervisorWithoutPerm->fresh())->get(
            route('manage.subscriptions.create', ['subdomain' => $this->academy->subdomain])
        );
        $allowed->assertOk();
    });
});
