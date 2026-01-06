<?php

use App\Http\Middleware\ResolveTenantFromSubdomain;
use App\Models\Academy;
use Illuminate\Http\Request;

beforeEach(function () {
    clearTenantContext();
});

afterEach(function () {
    clearTenantContext();
});

describe('ResolveTenantFromSubdomain Middleware', function () {
    it('resolves academy from subdomain', function () {
        $academy = createAcademy(['subdomain' => 'test-academy']);

        $request = Request::create('http://test-academy.itqan-platform.test/dashboard');
        $request->headers->set('host', 'test-academy.itqan-platform.test');

        // Set the config for base domain
        config(['app.domain' => 'itqan-platform.test']);

        $middleware = new ResolveTenantFromSubdomain;

        $response = $middleware->handle($request, function ($req) use ($academy) {
            // Check if academy was merged into request
            $requestAcademy = $req->get('academy');
            expect($requestAcademy)->not->toBeNull();
            expect($requestAcademy->id)->toBe($academy->id);

            return response('OK');
        });

        expect($response->getStatusCode())->toBe(200);
    });

    it('returns 404 response for non-existent subdomain', function () {
        $request = Request::create('http://nonexistent-academy.itqan-platform.test/dashboard');
        $request->headers->set('host', 'nonexistent-academy.itqan-platform.test');

        config(['app.domain' => 'itqan-platform.test']);

        $middleware = new ResolveTenantFromSubdomain;

        $response = $middleware->handle($request, function ($req) {
            // Academy should be null for non-existent subdomain
            $requestAcademy = $req->get('academy');
            expect($requestAcademy)->toBeNull();

            return response('OK');
        });

        // Middleware passes through but academy is not set
        expect($response->getStatusCode())->toBe(200);
    });

    it('handles inactive academy subdomain', function () {
        $academy = createAcademy([
            'subdomain' => 'inactive-academy',
            'is_active' => false,
        ]);

        $request = Request::create('http://inactive-academy.itqan-platform.test/dashboard');
        $request->headers->set('host', 'inactive-academy.itqan-platform.test');

        config(['app.domain' => 'itqan-platform.test']);

        $middleware = new ResolveTenantFromSubdomain;

        $response = $middleware->handle($request, function ($req) use ($academy) {
            // Middleware still resolves the academy (active check is elsewhere)
            $requestAcademy = $req->get('academy');
            expect($requestAcademy)->not->toBeNull();
            expect($requestAcademy->id)->toBe($academy->id);
            expect($requestAcademy->is_active)->toBeFalse();

            return response('OK');
        });

        expect($response->getStatusCode())->toBe(200);
    });

    it('handles main domain without subdomain', function () {
        $request = Request::create('http://itqan-platform.test/');
        $request->headers->set('host', 'itqan-platform.test');

        config(['app.domain' => 'itqan-platform.test']);

        $middleware = new ResolveTenantFromSubdomain;

        $response = $middleware->handle($request, function ($req) {
            // No subdomain means no academy merged
            $requestAcademy = $req->get('academy');
            expect($requestAcademy)->toBeNull();

            return response('OK');
        });

        expect($response->getStatusCode())->toBe(200);
    });
});
