<?php

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Laravel\Sanctum\Sanctum;

// Helper function to create notifications
function createNotification($user, $data = [])
{
    $id = (string) \Illuminate\Support\Str::uuid();

    DB::table('notifications')->insert([
        'id' => $id,
        'type' => $data['type'] ?? 'App\Notifications\TestNotification',
        'notifiable_type' => User::class,
        'notifiable_id' => $user->id,
        'data' => json_encode(array_merge([
            'title' => 'Test Notification',
            'message' => 'This is a test notification',
            'action_url' => '/test',
        ], $data['data'] ?? [])),
        'read_at' => $data['read_at'] ?? null,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    return $id;
}

describe('Notification API', function () {
    beforeEach(function () {
        $this->academy = createAcademy();
        $this->user = createUser('student', $this->academy);
        Sanctum::actingAs($this->user);
    });

    describe('GET /api/v1/common/notifications', function () {
        it('retrieves all notifications for authenticated user', function () {
            for ($i = 0; $i < 5; $i++) {
                createNotification($this->user);
            }

            $response = $this->getJson('/api/v1/common/notifications');

            $response->assertStatus(200)
                ->assertJsonStructure([
                    'success',
                    'message',
                    'data' => [
                        'notifications' => [
                            '*' => [
                                'id',
                                'type',
                                'title',
                                'message',
                                'data',
                                'read',
                                'read_at',
                                'created_at',
                                'action_url',
                            ],
                        ],
                        'pagination' => [
                            'current_page',
                            'per_page',
                            'total',
                            'total_pages',
                            'has_more',
                        ],
                    ],
                ]);

            expect($response->json('data.notifications'))->toHaveCount(5);
        });

        it('retrieves only unread notifications when unread_only parameter is true', function () {
            for ($i = 0; $i < 3; $i++) {
                createNotification($this->user, [
                    'read_at' => now(),
                    'data' => ['title' => 'Read Notification', 'message' => 'Read'],
                ]);
            }

            for ($i = 0; $i < 2; $i++) {
                createNotification($this->user, [
                    'read_at' => null,
                    'data' => ['title' => 'Unread Notification', 'message' => 'Unread'],
                ]);
            }

            $response = $this->getJson('/api/v1/common/notifications?unread_only=1');

            $response->assertStatus(200);
            expect($response->json('data.notifications'))->toHaveCount(2);

            foreach ($response->json('data.notifications') as $notification) {
                expect($notification['read'])->toBeFalse();
            }
        });

        it('supports pagination with per_page parameter', function () {
            for ($i = 0; $i < 25; $i++) {
                createNotification($this->user, ['data' => ['title' => 'Test', 'message' => 'Test']]);
            }

            $response = $this->getJson('/api/v1/common/notifications?per_page=10');

            $response->assertStatus(200);
            expect($response->json('data.pagination.per_page'))->toBe(10)
                ->and($response->json('data.pagination.total'))->toBe(25)
                ->and($response->json('data.notifications'))->toHaveCount(10);
        });

        it('requires authentication', function () {
            $this->actingAs(null);

            $response = $this->getJson('/api/v1/common/notifications');

            $response->assertStatus(401);
        });
    });

    describe('GET /api/v1/common/notifications/unread-count', function () {
        it('returns correct unread notification count', function () {
            for ($i = 0; $i < 5; $i++) {
                createNotification($this->user, [
                    'read_at' => null,
                    'data' => ['title' => 'Unread', 'message' => 'Unread'],
                ]);
            }

            for ($i = 0; $i < 3; $i++) {
                createNotification($this->user, [
                    'read_at' => now(),
                    'data' => ['title' => 'Read', 'message' => 'Read'],
                ]);
            }

            $response = $this->getJson('/api/v1/common/notifications/unread-count');

            $response->assertStatus(200)
                ->assertJson([
                    'success' => true,
                    'data' => [
                        'unread_count' => 5,
                    ],
                ]);
        });

        it('returns zero when no unread notifications exist', function () {
            $response = $this->getJson('/api/v1/common/notifications/unread-count');

            $response->assertStatus(200)
                ->assertJson([
                    'data' => ['unread_count' => 0],
                ]);
        });
    });

    describe('PUT /api/v1/common/notifications/{id}/read', function () {
        it('marks a notification as read', function () {
            $notificationId = createNotification($this->user, [
                'read_at' => null,
                'data' => ['title' => 'Test', 'message' => 'Test'],
            ]);

            $response = $this->putJson("/api/v1/common/notifications/{$notificationId}/read");

            $response->assertStatus(200)
                ->assertJson([
                    'success' => true,
                    'data' => ['marked' => true],
                ]);

            $notification = DB::table('notifications')->where('id', $notificationId)->first();
            expect($notification->read_at)->not->toBeNull();
        });

        it('returns 404 when notification does not exist', function () {
            $response = $this->putJson('/api/v1/common/notifications/invalid-id/read');

            $response->assertStatus(404);
        });

        it('returns 404 when trying to read another users notification', function () {
            $otherUser = createUser('student', $this->academy);
            $notificationId = createNotification($otherUser, [
                'data' => ['title' => 'Test', 'message' => 'Test'],
            ]);

            $response = $this->putJson("/api/v1/common/notifications/{$notificationId}/read");

            $response->assertStatus(404);
        });
    });

    describe('PUT /api/v1/common/notifications/read-all', function () {
        it('marks all unread notifications as read', function () {
            for ($i = 0; $i < 5; $i++) {
                createNotification($this->user, [
                    'read_at' => null,
                    'data' => ['title' => 'Unread', 'message' => 'Unread'],
                ]);
            }

            $response = $this->putJson('/api/v1/common/notifications/read-all');

            $response->assertStatus(200)
                ->assertJson([
                    'success' => true,
                    'data' => ['marked' => true],
                ]);

            expect($this->user->unreadNotifications()->count())->toBe(0);
        });

        it('works when no unread notifications exist', function () {
            $response = $this->putJson('/api/v1/common/notifications/read-all');

            $response->assertStatus(200);
        });
    });

    describe('DELETE /api/v1/common/notifications/{id}', function () {
        it('deletes a notification', function () {
            $notificationId = createNotification($this->user, [
                'data' => ['title' => 'Test', 'message' => 'Test'],
            ]);

            $response = $this->deleteJson("/api/v1/common/notifications/{$notificationId}");

            $response->assertStatus(200)
                ->assertJson([
                    'success' => true,
                    'data' => ['deleted' => true],
                ]);

            expect(DB::table('notifications')->where('id', $notificationId)->first())->toBeNull();
        });

        it('returns 404 when notification does not exist', function () {
            $response = $this->deleteJson('/api/v1/common/notifications/invalid-id');

            $response->assertStatus(404);
        });

        it('prevents deleting another users notification', function () {
            $otherUser = createUser('student', $this->academy);
            $notificationId = createNotification($otherUser, [
                'data' => ['title' => 'Test', 'message' => 'Test'],
            ]);

            $response = $this->deleteJson("/api/v1/common/notifications/{$notificationId}");

            $response->assertStatus(404);
            expect(DB::table('notifications')->where('id', $notificationId)->first())->not->toBeNull();
        });
    });

    describe('DELETE /api/v1/common/notifications/clear-all', function () {
        it('clears all notifications for user', function () {
            for ($i = 0; $i < 10; $i++) {
                createNotification($this->user, ['data' => ['title' => 'Test', 'message' => 'Test']]);
            }

            $response = $this->deleteJson('/api/v1/common/notifications/clear-all');

            $response->assertStatus(200)
                ->assertJson([
                    'success' => true,
                    'data' => ['cleared' => true],
                ]);

            expect($this->user->notifications()->count())->toBe(0);
        });

        it('does not affect other users notifications', function () {
            $otherUser = createUser('student', $this->academy);

            for ($i = 0; $i < 5; $i++) {
                createNotification($this->user, ['data' => ['title' => 'Test', 'message' => 'Test']]);
            }

            for ($i = 0; $i < 3; $i++) {
                createNotification($otherUser, ['data' => ['title' => 'Test', 'message' => 'Test']]);
            }

            $this->deleteJson('/api/v1/common/notifications/clear-all');

            expect($otherUser->notifications()->count())->toBe(3);
        });
    });
});
