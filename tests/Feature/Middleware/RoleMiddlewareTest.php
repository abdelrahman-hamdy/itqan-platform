<?php

use App\Http\Middleware\RoleMiddleware;
use App\Models\Academy;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

describe('RoleMiddleware', function () {
    beforeEach(function () {
        $this->middleware = new RoleMiddleware();
        $this->academy = Academy::factory()->create();
    });

    describe('handle', function () {
        it('redirects unauthenticated users to login', function () {
            $request = Request::create('/test');
            $request->setRouteResolver(fn () => null);

            $response = $this->middleware->handle($request, fn () => response('OK'), 'admin');

            expect($response->isRedirect())->toBeTrue();
        });

        it('redirects inactive users to login with error', function () {
            $user = User::factory()->admin()->forAcademy($this->academy)->create([
                'is_active' => false,
            ]);

            $this->actingAs($user);
            $request = Request::create('/test');
            $request->setRouteResolver(fn () => null);

            $response = $this->middleware->handle($request, fn () => response('OK'), 'admin');

            expect($response->isRedirect())->toBeTrue();
        });

        it('allows super admin to access super_admin routes', function () {
            $user = User::factory()->superAdmin()->create();
            $this->actingAs($user);

            $request = Request::create('/test');

            $response = $this->middleware->handle($request, fn () => response('OK'), 'super_admin');

            expect($response->getContent())->toBe('OK');
        });

        it('allows admin to access admin routes', function () {
            $user = User::factory()->admin()->forAcademy($this->academy)->create();
            $this->actingAs($user);

            $request = Request::create('/test');

            $response = $this->middleware->handle($request, fn () => response('OK'), 'admin');

            expect($response->getContent())->toBe('OK');
        });

        it('allows supervisor to access supervisor routes', function () {
            $user = User::factory()->supervisor()->forAcademy($this->academy)->create();
            $this->actingAs($user);

            $request = Request::create('/test');

            $response = $this->middleware->handle($request, fn () => response('OK'), 'supervisor');

            expect($response->getContent())->toBe('OK');
        });

        it('allows quran teacher to access quran_teacher routes', function () {
            $user = User::factory()->quranTeacher()->forAcademy($this->academy)->create();
            $this->actingAs($user);

            $request = Request::create('/test');

            $response = $this->middleware->handle($request, fn () => response('OK'), 'quran_teacher');

            expect($response->getContent())->toBe('OK');
        });

        it('allows academic teacher to access academic_teacher routes', function () {
            $user = User::factory()->academicTeacher()->forAcademy($this->academy)->create();
            $this->actingAs($user);

            $request = Request::create('/test');

            $response = $this->middleware->handle($request, fn () => response('OK'), 'academic_teacher');

            expect($response->getContent())->toBe('OK');
        });

        it('allows student to access student routes', function () {
            $user = User::factory()->student()->forAcademy($this->academy)->create();
            $this->actingAs($user);

            $request = Request::create('/test');

            $response = $this->middleware->handle($request, fn () => response('OK'), 'student');

            expect($response->getContent())->toBe('OK');
        });

        it('allows parent to access parent routes', function () {
            $user = User::factory()->parent()->forAcademy($this->academy)->create();
            $this->actingAs($user);

            $request = Request::create('/test');

            $response = $this->middleware->handle($request, fn () => response('OK'), 'parent');

            expect($response->getContent())->toBe('OK');
        });

        it('allows user with any of multiple roles', function () {
            $user = User::factory()->student()->forAcademy($this->academy)->create();
            $this->actingAs($user);

            $request = Request::create('/test');

            $response = $this->middleware->handle($request, fn () => response('OK'), 'admin,student,parent');

            expect($response->getContent())->toBe('OK');
        });

        it('denies user without required role', function () {
            $user = User::factory()->student()->forAcademy($this->academy)->create();
            $this->actingAs($user);

            $request = Request::create('/test');

            expect(fn () => $this->middleware->handle($request, fn () => response('OK'), 'admin'))
                ->toThrow(\Symfony\Component\HttpKernel\Exception\HttpException::class);
        });

        it('returns json response for ajax requests when denied', function () {
            $user = User::factory()->student()->forAcademy($this->academy)->create();
            $this->actingAs($user);

            $request = Request::create('/test');
            $request->headers->set('Accept', 'application/json');
            $request->headers->set('X-Requested-With', 'XMLHttpRequest');

            $response = $this->middleware->handle($request, fn () => response('OK'), 'admin');

            expect($response->getStatusCode())->toBe(403);
        });

        it('allows staff access to staff routes', function () {
            $user = User::factory()->admin()->forAcademy($this->academy)->create();
            $this->actingAs($user);

            $request = Request::create('/test');

            $response = $this->middleware->handle($request, fn () => response('OK'), 'staff');

            expect($response->getContent())->toBe('OK');
        });

        it('allows end_user access to end_user routes', function () {
            $user = User::factory()->student()->forAcademy($this->academy)->create();
            $this->actingAs($user);

            $request = Request::create('/test');

            $response = $this->middleware->handle($request, fn () => response('OK'), 'end_user');

            expect($response->getContent())->toBe('OK');
        });
    });
});
