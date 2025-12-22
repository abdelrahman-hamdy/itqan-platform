<?php

use App\Http\Middleware\TenantMiddleware;
use App\Models\Academy;
use Illuminate\Http\Request;

describe('TenantMiddleware', function () {
    beforeEach(function () {
        $this->middleware = new TenantMiddleware();
    });

    describe('handle', function () {
        it('skips tenant resolution for admin routes', function () {
            $request = Request::create('/admin/dashboard');

            $response = $this->middleware->handle($request, fn () => response('OK'));

            expect($response->getContent())->toBe('OK');
        });

        it('skips tenant resolution for admin sub-routes', function () {
            $request = Request::create('/admin/users/create');

            $response = $this->middleware->handle($request, fn () => response('OK'));

            expect($response->getContent())->toBe('OK');
        });

        it('continues when no subdomain present', function () {
            config(['app.domain' => 'example.com']);
            $request = Request::create('http://example.com/test');

            $response = $this->middleware->handle($request, fn () => response('OK'));

            expect($response->getContent())->toBe('OK');
        });

        it('returns 404 when academy not found', function () {
            config(['app.domain' => 'itqan-platform.test']);
            $request = Request::create('http://nonexistent.itqan-platform.test/test');

            expect(fn () => $this->middleware->handle($request, fn () => response('OK')))
                ->toThrow(\Symfony\Component\HttpKernel\Exception\HttpException::class);
        });

        it('returns 503 when academy is inactive', function () {
            $academy = Academy::factory()->create([
                'subdomain' => 'inactive-academy',
                'is_active' => false,
            ]);

            config(['app.domain' => 'itqan-platform.test']);
            $request = Request::create('http://inactive-academy.itqan-platform.test/test');

            expect(fn () => $this->middleware->handle($request, fn () => response('OK')))
                ->toThrow(\Symfony\Component\HttpKernel\Exception\HttpException::class);
        });

        it('returns 503 when academy is in maintenance mode', function () {
            $academy = Academy::factory()->create([
                'subdomain' => 'maintenance-academy',
                'is_active' => true,
                'maintenance_mode' => true,
            ]);

            config(['app.domain' => 'itqan-platform.test']);
            $request = Request::create('http://maintenance-academy.itqan-platform.test/test');

            expect(fn () => $this->middleware->handle($request, fn () => response('OK')))
                ->toThrow(\Symfony\Component\HttpKernel\Exception\HttpException::class);
        });

        it('sets current academy in app container for valid subdomain', function () {
            $academy = Academy::factory()->create([
                'subdomain' => 'test-academy',
                'is_active' => true,
                'maintenance_mode' => false,
            ]);

            config(['app.domain' => 'itqan-platform.test']);
            $request = Request::create('http://test-academy.itqan-platform.test/dashboard');

            $response = $this->middleware->handle($request, fn () => response('OK'));

            expect(app('current_academy')->id)->toBe($academy->id);
        });

        it('uses default academy when on main domain', function () {
            $defaultAcademy = Academy::factory()->create([
                'subdomain' => 'itqan-academy',
                'is_active' => true,
            ]);

            config(['app.domain' => 'itqan-platform.test']);
            $request = Request::create('http://itqan-platform.test/test');

            $this->middleware->handle($request, fn () => response('OK'));

            expect(app('current_academy')->id)->toBe($defaultAcademy->id);
        });
    });
});
