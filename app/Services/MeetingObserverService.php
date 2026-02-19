<?php

namespace App\Services;

use App\Contracts\MeetingObserverServiceInterface;
use App\Models\AcademicTeacherProfile;
use App\Enums\SessionStatus;
use App\Enums\UserType;
use App\Models\AcademicSession;
use App\Models\BaseSession;
use App\Models\InteractiveCourseSession;
use App\Models\QuranSession;
use App\Models\User;
use App\Services\LiveKit\LiveKitTokenGenerator;

class MeetingObserverService implements MeetingObserverServiceInterface
{
    public function __construct(
        private LiveKitTokenGenerator $tokenGenerator
    ) {}

    /**
     * Check if a user can observe a specific session.
     *
     * SuperAdmin: any session in their current academy context.
     * Supervisor: only sessions where the teacher is in their assigned responsibilities.
     */
    public function canObserveSession(User $user, BaseSession $session): bool
    {
        if (! $this->isObserverRole($user)) {
            return false;
        }

        if ($user->isSuperAdmin()) {
            return $this->canSuperAdminObserve($user, $session);
        }

        if ($user->isSupervisor()) {
            return $this->canSupervisorObserve($user, $session);
        }

        return false;
    }

    /**
     * Check if a session is currently observable (has an active meeting).
     */
    public function isSessionObservable(BaseSession $session): bool
    {
        if (! $session->meeting_room_name) {
            return false;
        }

        $status = $session->status instanceof SessionStatus
            ? $session->status
            : SessionStatus::tryFrom($session->status);

        return $status && in_array($status, [SessionStatus::READY, SessionStatus::ONGOING]);
    }

    /**
     * Generate a subscribe-only observer token for LiveKit.
     */
    public function generateObserverToken(string $roomName, User $user): string
    {
        return $this->tokenGenerator->generateParticipantToken(
            $roomName,
            $user,
            [
                'can_publish' => false,
                'can_subscribe' => true,
            ],
            'observer'
        );
    }

    /**
     * Resolve a session model by type string and ID.
     */
    public function resolveSession(string $sessionType, int|string $sessionId): ?BaseSession
    {
        return match ($sessionType) {
            'quran' => QuranSession::find($sessionId),
            'academic' => AcademicSession::find($sessionId),
            'interactive' => InteractiveCourseSession::find($sessionId),
            default => null,
        };
    }

    private function isObserverRole(User $user): bool
    {
        return in_array($user->user_type, [
            UserType::SUPERVISOR->value,
            UserType::SUPER_ADMIN->value,
        ]);
    }

    private function canSuperAdminObserve(User $user, BaseSession $session): bool
    {
        $academyId = AcademyContextService::getCurrentAcademyId();

        if (! $academyId) {
            return true; // Global view mode - can observe any session
        }

        return $this->getSessionAcademyId($session) === $academyId;
    }

    private function canSupervisorObserve(User $user, BaseSession $session): bool
    {
        $profile = $user->supervisorProfile;

        if (! $profile) {
            return false;
        }

        if ($session instanceof QuranSession) {
            $assignedTeacherIds = $profile->getAssignedQuranTeacherIds();

            return in_array($session->quran_teacher_id, $assignedTeacherIds);
        }

        if ($session instanceof AcademicSession) {
            $assignedProfileIds = $this->getAssignedAcademicTeacherProfileIds($profile);

            return in_array($session->academic_teacher_id, $assignedProfileIds);
        }

        if ($session instanceof InteractiveCourseSession) {
            $assignedCourseIds = $profile->getDerivedInteractiveCourseIds();

            return in_array($session->course_id, $assignedCourseIds);
        }

        return false;
    }

    private function getSessionAcademyId(BaseSession $session): ?int
    {
        if ($session instanceof InteractiveCourseSession) {
            return $session->course?->academy_id;
        }

        return $session->academy_id ?? null;
    }

    private function getAssignedAcademicTeacherProfileIds($profile): array
    {
        $userIds = $profile->getAssignedAcademicTeacherIds();

        if (empty($userIds)) {
            return [];
        }

        return AcademicTeacherProfile::whereIn('user_id', $userIds)
            ->pluck('id')
            ->toArray();
    }
}
