<?php

use App\Http\Middleware\AcademyContext;
use App\Models\Academy;
use App\Models\User;
use App\Services\AcademyContextService;
use Illuminate\Http\Request;

describe('AcademyContext Middleware', function () {
    beforeEach(function () {
        $this->middleware = new AcademyContext();
        $this->academy = Academy::factory()->create();
    });

    describe('handle', function () {
        it('passes through for non-admin routes', function () {
            $request = Request::create('/dashboard');

            $response = $this->middleware->handle($request, fn () => response('OK'));

            expect($response->getContent())->toBe('OK');
        });

        it('passes through for unauthenticated users', function () {
            $request = Request::create('/admin/dashboard');

            $response = $this->middleware->handle($request, fn () => response('OK'));

            expect($response->getContent())->toBe('OK');
        });

        it('passes through for non-super admin users on admin routes', function () {
            $admin = User::factory()->admin()->forAcademy($this->academy)->create();
            $this->actingAs($admin);

            $request = Request::create('/admin/dashboard');
            $request->setUserResolver(fn () => $admin);

            $response = $this->middleware->handle($request, fn () => response('OK'));

            expect($response->getContent())->toBe('OK');
        });

        it('handles academy parameter in URL for super admin', function () {
            $superAdmin = User::factory()->superAdmin()->create();
            $this->actingAs($superAdmin);

            $request = Request::create('/admin/dashboard', 'GET', ['academy' => $this->academy->id]);
            $request->setUserResolver(fn () => $superAdmin);

            $response = $this->middleware->handle($request, fn () => response('OK'));

            // Should redirect to clean URL
            expect($response->isRedirect())->toBeTrue();
        });

        it('sets current academy in app container for super admin', function () {
            $superAdmin = User::factory()->superAdmin()->create();
            $this->actingAs($superAdmin);

            // Pre-set academy context
            AcademyContextService::setAcademyContext($this->academy->id);

            $request = Request::create('/admin/dashboard');
            $request->setUserResolver(fn () => $superAdmin);

            $this->middleware->handle($request, fn () => response('OK'));

            expect(app()->has('current_academy'))->toBeTrue();
        });
    });
});
