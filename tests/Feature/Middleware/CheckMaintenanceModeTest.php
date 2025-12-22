<?php

use App\Http\Middleware\CheckMaintenanceMode;
use App\Models\Academy;
use App\Models\User;
use Illuminate\Http\Request;

describe('CheckMaintenanceMode', function () {
    beforeEach(function () {
        $this->middleware = new CheckMaintenanceMode();
        $this->academy = Academy::factory()->create([
            'maintenance_mode' => false,
        ]);
    });

    describe('handle', function () {
        it('passes through when no academy is found', function () {
            $request = Request::create('/test');

            $response = $this->middleware->handle($request, fn () => response('OK'));

            expect($response->getContent())->toBe('OK');
        });

        it('passes through when academy is not in maintenance mode', function () {
            app()->instance('current_academy', $this->academy);
            $request = Request::create('/test');

            $response = $this->middleware->handle($request, fn () => response('OK'));

            expect($response->getContent())->toBe('OK');
        });

        it('returns 503 when academy is in maintenance mode', function () {
            $this->academy->update(['maintenance_mode' => true]);
            app()->instance('current_academy', $this->academy);

            $request = Request::create('/dashboard');

            $response = $this->middleware->handle($request, fn () => response('OK'));

            expect($response->getStatusCode())->toBe(503);
        });

        it('allows super admin to bypass maintenance mode', function () {
            $this->academy->update(['maintenance_mode' => true]);
            app()->instance('current_academy', $this->academy);

            $superAdmin = User::factory()->superAdmin()->create();
            $this->actingAs($superAdmin);

            $request = Request::create('/dashboard');
            $request->setUserResolver(fn () => $superAdmin);

            $response = $this->middleware->handle($request, fn () => response('OK'));

            expect($response->getContent())->toBe('OK');
        });

        it('allows admin to bypass maintenance mode', function () {
            $this->academy->update(['maintenance_mode' => true]);
            app()->instance('current_academy', $this->academy);

            $admin = User::factory()->admin()->forAcademy($this->academy)->create();
            $this->actingAs($admin);

            $request = Request::create('/dashboard');
            $request->setUserResolver(fn () => $admin);

            $response = $this->middleware->handle($request, fn () => response('OK'));

            expect($response->getContent())->toBe('OK');
        });

        it('allows supervisor to bypass maintenance mode', function () {
            $this->academy->update(['maintenance_mode' => true]);
            app()->instance('current_academy', $this->academy);

            $supervisor = User::factory()->supervisor()->forAcademy($this->academy)->create();
            $this->actingAs($supervisor);

            $request = Request::create('/dashboard');
            $request->setUserResolver(fn () => $supervisor);

            $response = $this->middleware->handle($request, fn () => response('OK'));

            expect($response->getContent())->toBe('OK');
        });

        it('blocks student during maintenance mode', function () {
            $this->academy->update(['maintenance_mode' => true]);
            app()->instance('current_academy', $this->academy);

            $student = User::factory()->student()->forAcademy($this->academy)->create();
            $this->actingAs($student);

            $request = Request::create('/dashboard');
            $request->setUserResolver(fn () => $student);

            $response = $this->middleware->handle($request, fn () => response('OK'));

            expect($response->getStatusCode())->toBe(503);
        });

        it('allows access to excepted paths during maintenance', function () {
            $this->academy->update(['maintenance_mode' => true]);
            app()->instance('current_academy', $this->academy);

            $request = Request::create('/admin/dashboard');

            $response = $this->middleware->handle($request, fn () => response('OK'));

            expect($response->getContent())->toBe('OK');
        });

        it('allows access to login during maintenance', function () {
            $this->academy->update(['maintenance_mode' => true]);
            app()->instance('current_academy', $this->academy);

            $request = Request::create('/login');

            $response = $this->middleware->handle($request, fn () => response('OK'));

            expect($response->getContent())->toBe('OK');
        });

        it('allows access to maintenance page itself', function () {
            $this->academy->update(['maintenance_mode' => true]);
            app()->instance('current_academy', $this->academy);

            $request = Request::create('/maintenance');

            $response = $this->middleware->handle($request, fn () => response('OK'));

            expect($response->getContent())->toBe('OK');
        });

        it('returns json response for ajax requests during maintenance', function () {
            $this->academy->update(['maintenance_mode' => true]);
            app()->instance('current_academy', $this->academy);

            $request = Request::create('/api/data');
            $request->headers->set('Accept', 'application/json');
            $request->headers->set('X-Requested-With', 'XMLHttpRequest');

            $response = $this->middleware->handle($request, fn () => response('OK'));

            expect($response->getStatusCode())->toBe(503);
            expect($response->headers->get('Content-Type'))->toContain('application/json');
        });

        it('allows access to webhooks during maintenance', function () {
            $this->academy->update(['maintenance_mode' => true]);
            app()->instance('current_academy', $this->academy);

            $request = Request::create('/api/webhooks/payment');

            $response = $this->middleware->handle($request, fn () => response('OK'));

            expect($response->getContent())->toBe('OK');
        });

        it('allows access to static assets during maintenance', function () {
            $this->academy->update(['maintenance_mode' => true]);
            app()->instance('current_academy', $this->academy);

            $request = Request::create('/css/app.css');

            $response = $this->middleware->handle($request, fn () => response('OK'));

            expect($response->getContent())->toBe('OK');
        });
    });
});
