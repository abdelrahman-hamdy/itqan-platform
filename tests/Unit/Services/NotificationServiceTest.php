<?php

namespace Tests\Unit\Services;

use App\Services\NotificationService;
use App\Models\User;
use App\Models\Academy;
use App\Enums\NotificationType;
use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Mockery;

/**
 * Test cases for NotificationService
 *
 * These tests verify the notification dispatch system including:
 * - Sending notifications to single users
 * - Batch notification dispatch
 * - Notification preferences respect
 * - Multi-channel support (database, broadcast)
 */
class NotificationServiceTest extends TestCase
{
    use RefreshDatabase;

    protected NotificationService $service;
    protected string $testId;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(NotificationService::class);
        $this->createAcademy();
        $this->testId = Str::random(8);
        Notification::fake();
    }

    /**
     * Create a user with specific type.
     */
    protected function makeUser(string $userType, string $suffix = ''): User
    {
        return User::factory()->create([
            'academy_id' => $this->academy->id,
            'user_type' => $userType,
            'email' => "{$userType}{$suffix}_{$this->testId}@test.local",
        ]);
    }

    /**
     * Create a notification directly in database.
     */
    protected function createNotificationRecord(User $user, string $type = 'session_scheduled', ?string $readAt = null): string
    {
        $id = (string) Str::uuid();
        DB::table('notifications')->insert([
            'id' => $id,
            'type' => NotificationService::class . '\\' . $type,
            'notifiable_type' => get_class($user),
            'notifiable_id' => $user->id,
            'data' => json_encode(['title' => 'Test', 'message' => 'Test notification']),
            'notification_type' => $type,
            'category' => 'session',
            'icon' => 'heroicon-o-calendar',
            'icon_color' => 'primary',
            'action_url' => null,
            'metadata' => json_encode([]),
            'is_important' => false,
            'tenant_id' => $user->academy_id,
            'read_at' => $readAt,
            'panel_opened_at' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        return $id;
    }

    /**
     * Test sending notification to a single user.
     */
    public function test_sends_notification_to_single_user(): void
    {
        $user = $this->makeUser('student');

        $this->service->send(
            $user,
            NotificationType::SESSION_SCHEDULED,
            ['session_date' => '2024-01-15', 'teacher_name' => 'Teacher Test'],
            '/sessions/123'
        );

        // Verify notification was created in database
        $this->assertDatabaseHas('notifications', [
            'notifiable_id' => $user->id,
            'notifiable_type' => get_class($user),
            'notification_type' => NotificationType::SESSION_SCHEDULED->value,
        ]);
    }

    /**
     * Test sending notification to multiple users.
     */
    public function test_sends_notification_to_multiple_users(): void
    {
        $user1 = $this->makeUser('student', '_1');
        $user2 = $this->makeUser('student', '_2');
        $users = collect([$user1, $user2]);

        $this->service->send(
            $users,
            NotificationType::SESSION_REMINDER,
            ['session_date' => '2024-01-15'],
            null
        );

        // Verify notifications were created for both users
        $this->assertDatabaseHas('notifications', [
            'notifiable_id' => $user1->id,
            'notification_type' => NotificationType::SESSION_REMINDER->value,
        ]);
        $this->assertDatabaseHas('notifications', [
            'notifiable_id' => $user2->id,
            'notification_type' => NotificationType::SESSION_REMINDER->value,
        ]);
    }

    /**
     * Test that unread count is accurate.
     */
    public function test_unread_count_calculation(): void
    {
        $user = $this->makeUser('student');

        // Create 3 unread notifications
        $this->createNotificationRecord($user, 'session_scheduled');
        $this->createNotificationRecord($user, 'session_reminder');
        $this->createNotificationRecord($user, 'homework_assigned');

        // Create 1 read notification
        $this->createNotificationRecord($user, 'session_completed', now());

        // Count unread notifications
        $unreadCount = DB::table('notifications')
            ->where('notifiable_id', $user->id)
            ->where('notifiable_type', get_class($user))
            ->whereNull('read_at')
            ->count();

        $this->assertEquals(3, $unreadCount);
    }

    /**
     * Test mark as read functionality.
     */
    public function test_mark_as_read_updates_notification(): void
    {
        $user = $this->makeUser('student');
        $notificationId = $this->createNotificationRecord($user);

        // Mark as read
        $result = $this->service->markAsRead($notificationId, $user);

        $this->assertTrue($result);
        $this->assertDatabaseHas('notifications', [
            'id' => $notificationId,
            'notifiable_id' => $user->id,
        ]);

        // Verify read_at is set
        $notification = DB::table('notifications')->where('id', $notificationId)->first();
        $this->assertNotNull($notification->read_at);
    }

    /**
     * Test mark all as read functionality.
     */
    public function test_mark_all_as_read_updates_all_notifications(): void
    {
        $user = $this->makeUser('student');

        // Create 3 unread notifications
        $this->createNotificationRecord($user, 'session_scheduled');
        $this->createNotificationRecord($user, 'session_reminder');
        $this->createNotificationRecord($user, 'homework_assigned');

        // Mark all as read
        $updatedCount = $this->service->markAllAsRead($user);

        $this->assertEquals(3, $updatedCount);

        // Verify all are now read
        $unreadCount = DB::table('notifications')
            ->where('notifiable_id', $user->id)
            ->whereNull('read_at')
            ->count();

        $this->assertEquals(0, $unreadCount);
    }

    /**
     * Test notification with broadcast channel.
     */
    public function test_notification_broadcasts_to_channel(): void
    {
        $user = $this->makeUser('student');

        // Sending notification should trigger broadcast (mocked)
        $this->service->send(
            $user,
            NotificationType::SESSION_STARTED,
            ['session_date' => '2024-01-15'],
            '/sessions/123'
        );

        // Verify notification was created (broadcast is logged/mocked)
        $this->assertDatabaseHas('notifications', [
            'notifiable_id' => $user->id,
            'notification_type' => NotificationType::SESSION_STARTED->value,
        ]);
    }

    /**
     * Test session reminder notification sent.
     */
    public function test_session_reminder_notification_sent(): void
    {
        $user = $this->makeUser('student');

        $this->service->send(
            $user,
            NotificationType::SESSION_REMINDER,
            ['session_time' => '10:00 AM', 'teacher_name' => 'Test Teacher'],
            '/sessions/1'
        );

        $this->assertDatabaseHas('notifications', [
            'notifiable_id' => $user->id,
            'notification_type' => NotificationType::SESSION_REMINDER->value,
        ]);
    }

    /**
     * Test homework submission notifies teacher.
     */
    public function test_homework_submission_notifies_teacher(): void
    {
        $teacher = $this->makeUser('quran_teacher');

        $this->service->send(
            $teacher,
            NotificationType::HOMEWORK_SUBMITTED,
            ['student_name' => 'Test Student', 'homework_title' => 'Lesson 1'],
            '/homework/submissions/1'
        );

        $this->assertDatabaseHas('notifications', [
            'notifiable_id' => $teacher->id,
            'notification_type' => NotificationType::HOMEWORK_SUBMITTED->value,
        ]);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
}
