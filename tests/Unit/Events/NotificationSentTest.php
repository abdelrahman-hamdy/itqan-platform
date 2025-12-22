<?php

use App\Events\NotificationSent;
use Illuminate\Broadcasting\PrivateChannel;

describe('NotificationSent', function () {
    describe('constructor', function () {
        it('sets user id and notification data', function () {
            $userId = 123;
            $notificationData = [
                'type' => 'session_reminder',
                'message' => 'Session starts in 15 minutes',
            ];

            $event = new NotificationSent($userId, $notificationData);

            expect($event->userId)->toBe($userId);
            expect($event->notificationData)->toBe($notificationData);
        });
    });

    describe('broadcastOn', function () {
        it('returns private channel for user', function () {
            $userId = 123;
            $event = new NotificationSent($userId, []);

            $channel = $event->broadcastOn();

            expect($channel)->toBeInstanceOf(PrivateChannel::class);
        });
    });

    describe('broadcastWith', function () {
        it('returns correct broadcast data', function () {
            $notificationData = ['type' => 'test'];
            $event = new NotificationSent(123, $notificationData);

            $data = $event->broadcastWith();

            expect($data)->toHaveKey('notification');
            expect($data)->toHaveKey('timestamp');
        });
    });
});
