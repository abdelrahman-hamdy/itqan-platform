<?php

namespace App\Contracts;

use App\Models\InteractiveCourseSession;

interface InteractiveCourseSessionMeetingServiceInterface
{
    public function ensureMeetingAvailable(InteractiveCourseSession $session, bool $forceCreate = false): array;

    public function generateParticipantToken(InteractiveCourseSession $session, mixed $user, array $permissions = []): string;

    public function endMeeting(InteractiveCourseSession $session): bool;

    public function getMeetingStatus(InteractiveCourseSession $session): array;

    public function createMeetingsForReadySessions(): array;

    public function terminateExpiredMeetings(): array;
}
