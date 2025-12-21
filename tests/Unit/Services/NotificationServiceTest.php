<?php

use App\Enums\NotificationCategory;
use App\Enums\NotificationType;
use App\Models\Academy;
use App\Models\User;
use App\Services\NotificationService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

describe('NotificationService', function () {
    beforeEach(function () {
        $this->service = new NotificationService();
        $this->academy = Academy::factory()->create();
    });

    describe('send()', function () {
        it('creates a notification for a single user', function () {
            $user = User::factory()->student()->forAcademy($this->academy)->create();

            $this->service->send(
                $user,
                NotificationType::SESSION_REMINDER,
                ['session_title' => 'Test Session']
            );

            $notification = DB::table('notifications')
                ->where('notifiable_id', $user->id)
                ->where('notifiable_type', get_class($user))
                ->first();

            expect($notification)->not->toBeNull()
                ->and($notification->notification_type)->toBe(NotificationType::SESSION_REMINDER->value);
        });

        it('creates notifications for multiple users', function () {
            $users = User::factory()->student()->forAcademy($this->academy)->count(3)->create();

            $this->service->send(
                $users,
                NotificationType::SESSION_REMINDER,
                ['session_title' => 'Test Session']
            );

            $count = DB::table('notifications')
                ->whereIn('notifiable_id', $users->pluck('id'))
                ->count();

            expect($count)->toBe(3);
        });

        it('stores action URL when provided', function () {
            $user = User::factory()->student()->forAcademy($this->academy)->create();

            $this->service->send(
                $user,
                NotificationType::SESSION_REMINDER,
                ['session_title' => 'Test Session'],
                '/sessions/123'
            );

            $notification = DB::table('notifications')
                ->where('notifiable_id', $user->id)
                ->first();

            expect($notification->action_url)->toBe('/sessions/123');
        });

        it('stores metadata when provided', function () {
            $user = User::factory()->student()->forAcademy($this->academy)->create();

            $this->service->send(
                $user,
                NotificationType::SESSION_REMINDER,
                ['session_title' => 'Test Session'],
                null,
                ['extra_key' => 'extra_value']
            );

            $notification = DB::table('notifications')
                ->where('notifiable_id', $user->id)
                ->first();

            $metadata = json_decode($notification->metadata, true);
            expect($metadata)->toHaveKey('extra_key')
                ->and($metadata['extra_key'])->toBe('extra_value');
        });

        it('marks notification as important when flag is set', function () {
            $user = User::factory()->student()->forAcademy($this->academy)->create();

            $this->service->send(
                $user,
                NotificationType::SESSION_REMINDER,
                ['session_title' => 'Test Session'],
                null,
                [],
                true // isImportant
            );

            $notification = DB::table('notifications')
                ->where('notifiable_id', $user->id)
                ->first();

            expect($notification->is_important)->toBe(1);
        });

        it('uses custom icon when provided', function () {
            $user = User::factory()->student()->forAcademy($this->academy)->create();

            $this->service->send(
                $user,
                NotificationType::SESSION_REMINDER,
                ['session_title' => 'Test Session'],
                null,
                [],
                false,
                'ri-custom-icon'
            );

            $notification = DB::table('notifications')
                ->where('notifiable_id', $user->id)
                ->first();

            expect($notification->icon)->toBe('ri-custom-icon');
        });

        it('uses custom color when provided', function () {
            $user = User::factory()->student()->forAcademy($this->academy)->create();

            $this->service->send(
                $user,
                NotificationType::SESSION_REMINDER,
                ['session_title' => 'Test Session'],
                null,
                [],
                false,
                null,
                'purple'
            );

            $notification = DB::table('notifications')
                ->where('notifiable_id', $user->id)
                ->first();

            expect($notification->icon_color)->toBe('purple');
        });

        it('stores tenant id from user academy', function () {
            $user = User::factory()->student()->forAcademy($this->academy)->create();

            $this->service->send(
                $user,
                NotificationType::SESSION_REMINDER,
                ['session_title' => 'Test Session']
            );

            $notification = DB::table('notifications')
                ->where('notifiable_id', $user->id)
                ->first();

            expect((int) $notification->tenant_id)->toBe($this->academy->id);
        });

        it('logs error but does not throw exception on failure', function () {
            $user = User::factory()->student()->forAcademy($this->academy)->create();

            // Mock DB to throw exception
            DB::shouldReceive('table')
                ->once()
                ->andThrow(new \Exception('Database error'));

            Log::shouldReceive('error')
                ->once()
                ->withArgs(function ($message, $context) use ($user) {
                    return str_contains($message, 'Failed to send notification')
                        && $context['user_id'] === $user->id;
                });

            // Should not throw
            $this->service->send(
                $user,
                NotificationType::SESSION_REMINDER,
                ['session_title' => 'Test Session']
            );

            expect(true)->toBeTrue();
        });
    });

    describe('notification data structure', function () {
        it('stores correct category for notification type', function () {
            $user = User::factory()->student()->forAcademy($this->academy)->create();

            $this->service->send(
                $user,
                NotificationType::SESSION_REMINDER,
                ['session_title' => 'Test Session']
            );

            $notification = DB::table('notifications')
                ->where('notifiable_id', $user->id)
                ->first();

            expect($notification->category)->toBe(NotificationType::SESSION_REMINDER->getCategory()->value);
        });

        it('stores notification data as JSON', function () {
            $user = User::factory()->student()->forAcademy($this->academy)->create();

            $this->service->send(
                $user,
                NotificationType::SESSION_REMINDER,
                ['session_title' => 'Test Session']
            );

            $notification = DB::table('notifications')
                ->where('notifiable_id', $user->id)
                ->first();

            $data = json_decode($notification->data, true);
            expect($data)->toBeArray()
                ->and($data)->toHaveKey('title')
                ->and($data)->toHaveKey('message');
        });

        it('generates UUID for notification id', function () {
            $user = User::factory()->student()->forAcademy($this->academy)->create();

            $this->service->send(
                $user,
                NotificationType::SESSION_REMINDER,
                ['session_title' => 'Test Session']
            );

            $notification = DB::table('notifications')
                ->where('notifiable_id', $user->id)
                ->first();

            expect($notification->id)->toMatch('/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/i');
        });
    });
});
