<?php

use App\Http\Middleware\ContentSecurityPolicy;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

describe('ContentSecurityPolicy', function () {
    beforeEach(function () {
        $this->middleware = new ContentSecurityPolicy();
    });

    describe('handle', function () {
        it('adds CSP headers to HTML responses', function () {
            $request = Request::create('/test');

            $response = $this->middleware->handle($request, function () {
                return response('<html></html>', 200, ['Content-Type' => 'text/html']);
            });

            expect($response->headers->has('Content-Security-Policy'))->toBeTrue();
        });

        it('adds X-Content-Type-Options header', function () {
            $request = Request::create('/test');

            $response = $this->middleware->handle($request, function () {
                return response('<html></html>', 200, ['Content-Type' => 'text/html']);
            });

            expect($response->headers->get('X-Content-Type-Options'))->toBe('nosniff');
        });

        it('adds X-Frame-Options header', function () {
            $request = Request::create('/test');

            $response = $this->middleware->handle($request, function () {
                return response('<html></html>', 200, ['Content-Type' => 'text/html']);
            });

            expect($response->headers->get('X-Frame-Options'))->toBe('DENY');
        });

        it('adds X-XSS-Protection header', function () {
            $request = Request::create('/test');

            $response = $this->middleware->handle($request, function () {
                return response('<html></html>', 200, ['Content-Type' => 'text/html']);
            });

            expect($response->headers->get('X-XSS-Protection'))->toBe('1; mode=block');
        });

        it('adds Referrer-Policy header', function () {
            $request = Request::create('/test');

            $response = $this->middleware->handle($request, function () {
                return response('<html></html>', 200, ['Content-Type' => 'text/html']);
            });

            expect($response->headers->get('Referrer-Policy'))->toBe('strict-origin-when-cross-origin');
        });

        it('does not add CSP to non-HTML responses', function () {
            $request = Request::create('/api/data');

            $response = $this->middleware->handle($request, function () {
                return response()->json(['data' => 'test']);
            });

            expect($response->headers->has('Content-Security-Policy'))->toBeFalse();
        });

        it('includes self in default-src', function () {
            $request = Request::create('/test');

            $response = $this->middleware->handle($request, function () {
                return response('<html></html>', 200, ['Content-Type' => 'text/html']);
            });

            $csp = $response->headers->get('Content-Security-Policy');
            expect($csp)->toContain("default-src 'self'");
        });

        it('includes unsafe-inline in script-src for inline scripts', function () {
            $request = Request::create('/test');

            $response = $this->middleware->handle($request, function () {
                return response('<html></html>', 200, ['Content-Type' => 'text/html']);
            });

            $csp = $response->headers->get('Content-Security-Policy');
            expect($csp)->toContain("'unsafe-inline'");
        });

        it('blocks object-src', function () {
            $request = Request::create('/test');

            $response = $this->middleware->handle($request, function () {
                return response('<html></html>', 200, ['Content-Type' => 'text/html']);
            });

            $csp = $response->headers->get('Content-Security-Policy');
            expect($csp)->toContain("object-src 'none'");
        });

        it('allows websocket connections', function () {
            $request = Request::create('/test');

            $response = $this->middleware->handle($request, function () {
                return response('<html></html>', 200, ['Content-Type' => 'text/html']);
            });

            $csp = $response->headers->get('Content-Security-Policy');
            expect($csp)->toContain('ws:');
            expect($csp)->toContain('wss:');
        });

        it('allows Google fonts', function () {
            $request = Request::create('/test');

            $response = $this->middleware->handle($request, function () {
                return response('<html></html>', 200, ['Content-Type' => 'text/html']);
            });

            $csp = $response->headers->get('Content-Security-Policy');
            expect($csp)->toContain('https://fonts.googleapis.com');
            expect($csp)->toContain('https://fonts.gstatic.com');
        });
    });
});
