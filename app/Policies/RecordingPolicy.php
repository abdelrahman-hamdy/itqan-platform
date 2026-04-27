<?php

namespace App\Policies;

use App\Models\InteractiveCourseSession;
use App\Models\SessionRecording;
use App\Models\User;

/**
 * Recordings are admin-only content; teachers, students, and parents are
 * always denied regardless of enrollment or teaching relationships.
 */
class RecordingPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->canManageRecordings();
    }

    public function view(User $user, SessionRecording $recording): bool
    {
        return $user->canManageRecordings() && $this->sameAcademy($user, $recording);
    }

    public function download(User $user, SessionRecording $recording): bool
    {
        return $this->view($user, $recording);
    }

    public function delete(User $user, SessionRecording $recording): bool
    {
        return $user->isAdmin() && $this->sameAcademy($user, $recording);
    }

    public function restore(User $user, SessionRecording $recording): bool
    {
        return $user->isSuperAdmin();
    }

    public function forceDelete(User $user, SessionRecording $recording): bool
    {
        return $user->isSuperAdmin();
    }

    private function sameAcademy(User $user, SessionRecording $recording): bool
    {
        if ($user->isSuperAdmin()) {
            return true;
        }

        $session = $recording->recordable;
        if (! $session) {
            return false;
        }

        if ($session instanceof InteractiveCourseSession) {
            return $session->course?->academy_id === $user->academy_id;
        }

        return $session->academy_id === $user->academy_id;
    }
}
