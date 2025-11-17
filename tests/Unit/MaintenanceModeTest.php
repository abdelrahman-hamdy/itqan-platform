<?php

namespace Tests\Unit;

use App\Http\Middleware\CheckMaintenanceMode;
use App\Models\Academy;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

class MaintenanceModeTest extends TestCase
{
    use RefreshDatabase;

    protected CheckMaintenanceMode $middleware;

    protected function setUp(): void
    {
        parent::setUp();
        $this->middleware = new CheckMaintenanceMode();
    }

    /**
     * Test that maintenance mode is bypassed when no academy is present.
     */
    public function test_maintenance_mode_bypassed_without_academy()
    {
        $request = Request::create('/test');
        $response = new Response('OK');

        $result = $this->middleware->handle($request, function ($req) use ($response) {
            return $response;
        });

        $this->assertEquals($response, $result);
    }

    /**
     * Test that maintenance mode shows maintenance page when enabled.
     */
    public function test_maintenance_mode_shows_maintenance_page_when_enabled()
    {
        $academy = Academy::factory()->create([
            'maintenance_mode' => true,
            'academic_settings' => ['maintenance_message' => 'Under maintenance']
        ]);

        $request = Request::create('/test');
        $request->merge(['academy' => $academy]);

        $result = $this->middleware->handle($request, function ($req) {
            return new Response('Should not reach here');
        });

        $this->assertEquals(503, $result->getStatusCode());
    }

    /**
     * Test that admin users can bypass maintenance mode.
     */
    public function test_admin_users_can_bypass_maintenance_mode()
    {
        $admin = User::factory()->create(['user_type' => 'admin']);
        $academy = Academy::factory()->create([
            'maintenance_mode' => true
        ]);

        $request = Request::create('/test');
        $request->merge(['academy' => $academy]);
        $request->setUserResolver(function () use ($admin) {
            return $admin;
        });

        $response = new Response('Admin can access');

        $result = $this->middleware->handle($request, function ($req) use ($response) {
            return $response;
        });

        $this->assertEquals($response, $result);
    }

    /**
     * Test that regular users cannot bypass maintenance mode.
     */
    public function test_regular_users_cannot_bypass_maintenance_mode()
    {
        $user = User::factory()->create(['user_type' => 'student']);
        $academy = Academy::factory()->create([
            'maintenance_mode' => true
        ]);

        $request = Request::create('/test');
        $request->merge(['academy' => $academy]);
        $request->setUserResolver(function () use ($user) {
            return $user;
        });

        $result = $this->middleware->handle($request, function ($req) {
            return new Response('Should not reach here');
        });

        $this->assertEquals(503, $result->getStatusCode());
    }

    /**
     * Test that academy admin can bypass their own academy's maintenance mode.
     */
    public function test_academy_admin_can_bypass_own_academy_maintenance()
    {
        $admin = User::factory()->create(['user_type' => 'teacher']);
        $academy = Academy::factory()->create([
            'maintenance_mode' => true,
            'admin_id' => $admin->id
        ]);

        $request = Request::create('/test');
        $request->merge(['academy' => $academy]);
        $request->setUserResolver(function () use ($admin) {
            return $admin;
        });

        $response = new Response('Academy admin can access');

        $result = $this->middleware->handle($request, function ($req) use ($response) {
            return $response;
        });

        $this->assertEquals($response, $result);
    }

    /**
     * Test that maintenance mode is not active when disabled.
     */
    public function test_maintenance_mode_inactive_when_disabled()
    {
        $academy = Academy::factory()->create([
            'maintenance_mode' => false
        ]);

        $request = Request::create('/test');
        $request->merge(['academy' => $academy]);

        $response = new Response('Normal access');

        $result = $this->middleware->handle($request, function ($req) use ($response) {
            return $response;
        });

        $this->assertEquals($response, $result);
    }

    /**
     * Test that AJAX requests get JSON response during maintenance.
     */
    public function test_ajax_requests_get_json_response_during_maintenance()
    {
        $academy = Academy::factory()->create([
            'maintenance_mode' => true
        ]);

        $request = Request::create('/test', 'GET', [], [], [], ['HTTP_X-Requested-With' => 'XMLHttpRequest']);
        $request->merge(['academy' => $academy]);

        $result = $this->middleware->handle($request, function ($req) {
            return new Response('Should not reach here');
        });

        $this->assertEquals(503, $result->getStatusCode());
        $this->assertJson($result->getContent());

        $json = json_decode($result->getContent(), true);
        $this->assertEquals('maintenance', $json['status']);
    }
}