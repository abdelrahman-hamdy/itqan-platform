<?php

namespace App\Contracts;

use App\Models\QuranSession;

interface SessionMeetingServiceInterface
{
    public function ensureMeetingAvailable(QuranSession $session, bool $forceCreate = false): array;

    public function processScheduledSessions(): array;

    public function forceCreateMeeting(QuranSession $session): array;

    public function createMeetingsForReadySessions(): array;

    public function terminateExpiredMeetings(): array;

    public function processSessionMeetings(): array;
}
