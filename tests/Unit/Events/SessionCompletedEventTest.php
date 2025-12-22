<?php

use App\Events\SessionCompletedEvent;
use App\Models\Academy;
use App\Models\QuranSession;
use App\Models\User;
use Illuminate\Broadcasting\Channel;

describe('SessionCompletedEvent', function () {
    beforeEach(function () {
        $this->academy = Academy::factory()->create();
        $this->teacher = User::factory()->quranTeacher()->forAcademy($this->academy)->create();
        $this->session = QuranSession::factory()->create([
            'academy_id' => $this->academy->id,
            'quran_teacher_id' => $this->teacher->id,
        ]);
    });

    describe('constructor', function () {
        it('sets session', function () {
            $event = new SessionCompletedEvent($this->session);

            expect($event->session->id)->toBe($this->session->id);
        });
    });

    describe('broadcastOn', function () {
        it('returns correct channel', function () {
            $event = new SessionCompletedEvent($this->session);

            $channel = $event->broadcastOn();

            expect($channel)->toBeInstanceOf(Channel::class);
        });
    });

    describe('broadcastWith', function () {
        it('returns session data', function () {
            $event = new SessionCompletedEvent($this->session);

            $data = $event->broadcastWith();

            expect($data)->toHaveKey('session_id');
            expect($data['session_id'])->toBe($this->session->id);
        });
    });
});
