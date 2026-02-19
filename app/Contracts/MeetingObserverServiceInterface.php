<?php

namespace App\Contracts;

use App\Models\BaseSession;
use App\Models\User;

interface MeetingObserverServiceInterface
{
    public function canObserveSession(User $user, BaseSession $session): bool;

    public function isSessionObservable(BaseSession $session): bool;

    public function generateObserverToken(string $roomName, User $user): string;

    public function resolveSession(string $sessionType, int|string $sessionId): ?BaseSession;
}
