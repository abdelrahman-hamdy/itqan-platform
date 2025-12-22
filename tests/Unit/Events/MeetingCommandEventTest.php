<?php

use App\Enums\SessionStatus;
use App\Events\MeetingCommandEvent;
use App\Models\Academy;
use App\Models\QuranSession;
use App\Models\User;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;

describe('MeetingCommandEvent', function () {
    beforeEach(function () {
        $this->academy = Academy::factory()->create();
        $this->teacher = User::factory()->quranTeacher()->forAcademy($this->academy)->create();
        $this->session = QuranSession::factory()->create([
            'academy_id' => $this->academy->id,
            'quran_teacher_id' => $this->teacher->id,
            'status' => SessionStatus::ONGOING,
            'is_live' => true,
        ]);
    });

    describe('constructor', function () {
        it('sets session and command data', function () {
            $commandData = [
                'command' => 'mute',
                'target_user_id' => 1,
            ];

            $event = new MeetingCommandEvent($this->session, $commandData);

            expect($event->session->id)->toBe($this->session->id);
            expect($event->commandData)->toBe($commandData);
        });
    });

    describe('broadcastOn', function () {
        it('returns presence and private channels', function () {
            $event = new MeetingCommandEvent($this->session, []);

            $channels = $event->broadcastOn();

            expect($channels)->toHaveCount(2);
            expect($channels[0])->toBeInstanceOf(PresenceChannel::class);
            expect($channels[1])->toBeInstanceOf(PrivateChannel::class);
        });
    });

    describe('broadcastWith', function () {
        it('returns correct broadcast data', function () {
            $commandData = ['command' => 'test'];
            $event = new MeetingCommandEvent($this->session, $commandData);

            $data = $event->broadcastWith();

            expect($data)->toHaveKey('session_id');
            expect($data)->toHaveKey('command_data');
            expect($data)->toHaveKey('broadcast_at');
            expect($data['session_id'])->toBe($this->session->id);
            expect($data['command_data'])->toBe($commandData);
        });
    });

    describe('broadcastAs', function () {
        it('returns correct event name', function () {
            $event = new MeetingCommandEvent($this->session, []);

            expect($event->broadcastAs())->toBe('meeting.command');
        });
    });

    describe('shouldBroadcast', function () {
        it('returns true when session is live', function () {
            $this->session->update(['is_live' => true]);

            $event = new MeetingCommandEvent($this->session, []);

            expect($event->shouldBroadcast())->toBeTrue();
        });

        it('returns true when session is ongoing', function () {
            $this->session->update(['status' => SessionStatus::ONGOING, 'is_live' => false]);

            $event = new MeetingCommandEvent($this->session, []);

            expect($event->shouldBroadcast())->toBeTrue();
        });

        it('returns false when session is not live and not ongoing', function () {
            $this->session->update([
                'status' => SessionStatus::SCHEDULED,
                'is_live' => false,
            ]);

            $event = new MeetingCommandEvent($this->session, []);

            expect($event->shouldBroadcast())->toBeFalse();
        });
    });
});
