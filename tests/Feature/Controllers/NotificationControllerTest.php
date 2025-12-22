<?php

use App\Models\Academy;
use App\Models\User;
use Illuminate\Notifications\DatabaseNotification;

describe('NotificationController', function () {
    beforeEach(function () {
        $this->academy = Academy::factory()->create();
    });

    describe('index', function () {
        it('returns notifications for authenticated user', function () {
            $user = User::factory()->student()->forAcademy($this->academy)->create();

            $response = $this->actingAs($user)->get(route('notifications.index', [
                'subdomain' => $this->academy->subdomain,
            ]));

            $response->assertStatus(200);
        });

        it('requires authentication', function () {
            $response = $this->get(route('notifications.index', [
                'subdomain' => $this->academy->subdomain,
            ]));

            $response->assertRedirect();
        });
    });

    describe('markAsRead', function () {
        it('marks notification as read', function () {
            $user = User::factory()->student()->forAcademy($this->academy)->create();

            // Create a notification
            $notification = $user->notifications()->create([
                'id' => \Illuminate\Support\Str::uuid(),
                'type' => 'App\Notifications\TestNotification',
                'data' => ['message' => 'Test notification'],
            ]);

            $response = $this->actingAs($user)->post(route('notifications.markAsRead', [
                'subdomain' => $this->academy->subdomain,
                'id' => $notification->id,
            ]));

            $response->assertStatus(200);
            expect($notification->fresh()->read_at)->not->toBeNull();
        });
    });

    describe('markAllAsRead', function () {
        it('marks all notifications as read', function () {
            $user = User::factory()->student()->forAcademy($this->academy)->create();

            // Create multiple notifications
            $user->notifications()->create([
                'id' => \Illuminate\Support\Str::uuid(),
                'type' => 'App\Notifications\TestNotification',
                'data' => ['message' => 'Test 1'],
            ]);
            $user->notifications()->create([
                'id' => \Illuminate\Support\Str::uuid(),
                'type' => 'App\Notifications\TestNotification',
                'data' => ['message' => 'Test 2'],
            ]);

            $response = $this->actingAs($user)->post(route('notifications.markAllAsRead', [
                'subdomain' => $this->academy->subdomain,
            ]));

            $response->assertStatus(200);
            expect($user->unreadNotifications()->count())->toBe(0);
        });
    });
});
