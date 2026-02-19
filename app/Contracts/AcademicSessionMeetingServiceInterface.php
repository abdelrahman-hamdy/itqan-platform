<?php

namespace App\Contracts;

use App\Models\AcademicSession;

interface AcademicSessionMeetingServiceInterface
{
    public function ensureMeetingAvailable(AcademicSession $session, bool $forceCreate = false): array;

    public function processScheduledSessions(): array;

    public function forceCreateMeeting(AcademicSession $session): array;

    public function createMeetingsForReadySessions(): array;

    public function terminateExpiredMeetings(): array;

    public function processSessionMeetings(): array;
}
