<?php

use App\Events\AttendanceUpdated;
use Illuminate\Broadcasting\Channel;

describe('AttendanceUpdated', function () {
    describe('constructor', function () {
        it('sets session id, user id, and attendance data', function () {
            $sessionId = 123;
            $userId = 456;
            $attendanceData = [
                'status' => 'present',
                'attended_at' => now()->toISOString(),
            ];

            $event = new AttendanceUpdated($sessionId, $userId, $attendanceData);

            expect($event->sessionId)->toBe($sessionId);
            expect($event->userId)->toBe($userId);
            expect($event->attendanceData)->toBe($attendanceData);
        });
    });

    describe('broadcastOn', function () {
        it('returns correct channel', function () {
            $sessionId = 123;
            $event = new AttendanceUpdated($sessionId, 456, []);

            $channel = $event->broadcastOn();

            expect($channel)->toBeInstanceOf(Channel::class);
            expect($channel->name)->toBe('session.123');
        });
    });

    describe('broadcastAs', function () {
        it('returns correct event name', function () {
            $event = new AttendanceUpdated(123, 456, []);

            expect($event->broadcastAs())->toBe('attendance.updated');
        });
    });

    describe('broadcastWith', function () {
        it('returns correct broadcast data', function () {
            $userId = 456;
            $attendanceData = ['status' => 'present'];
            $event = new AttendanceUpdated(123, $userId, $attendanceData);

            $data = $event->broadcastWith();

            expect($data)->toHaveKey('user_id');
            expect($data)->toHaveKey('attendance');
            expect($data)->toHaveKey('timestamp');
            expect($data['user_id'])->toBe($userId);
            expect($data['attendance'])->toBe($attendanceData);
        });
    });
});
