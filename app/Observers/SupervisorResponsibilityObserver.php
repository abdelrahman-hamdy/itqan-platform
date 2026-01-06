<?php

namespace App\Observers;

use App\Events\SupervisorAssignmentChangedEvent;
use App\Models\SupervisorResponsibility;
use App\Models\User;
use Illuminate\Support\Facades\Log;

class SupervisorResponsibilityObserver
{
    /**
     * Handle the SupervisorResponsibility "created" event.
     * Fired when a new supervisor-teacher assignment is created.
     */
    public function created(SupervisorResponsibility $responsibility): void
    {
        // Only handle User (teacher) assignments
        if ($responsibility->responsable_type !== User::class) {
            return;
        }

        $teacher = User::find($responsibility->responsable_id);

        // Only process if it's a teacher
        if (! $teacher || ! in_array($teacher->user_type, ['quran_teacher', 'academic_teacher'])) {
            return;
        }

        $responsibility->load('supervisorProfile.user');

        if (! $responsibility->supervisorProfile) {
            return;
        }

        Log::info('Supervisor responsibility created - dispatching event', [
            'responsibility_id' => $responsibility->id,
            'supervisor_profile_id' => $responsibility->supervisor_profile_id,
            'teacher_id' => $responsibility->responsable_id,
        ]);

        SupervisorAssignmentChangedEvent::dispatch(
            $responsibility->supervisorProfile,
            $responsibility->responsable_id,
            'assigned'
        );
    }

    /**
     * Handle the SupervisorResponsibility "deleted" event.
     * Fired when a supervisor-teacher assignment is removed.
     */
    public function deleted(SupervisorResponsibility $responsibility): void
    {
        // Only handle User (teacher) assignments
        if ($responsibility->responsable_type !== User::class) {
            return;
        }

        $teacher = User::find($responsibility->responsable_id);

        // Only process if it's a teacher
        if (! $teacher || ! in_array($teacher->user_type, ['quran_teacher', 'academic_teacher'])) {
            return;
        }

        $responsibility->load('supervisorProfile.user');

        if (! $responsibility->supervisorProfile) {
            return;
        }

        Log::info('Supervisor responsibility deleted - dispatching event', [
            'responsibility_id' => $responsibility->id,
            'supervisor_profile_id' => $responsibility->supervisor_profile_id,
            'teacher_id' => $responsibility->responsable_id,
            'supervisor_user_id' => $responsibility->supervisorProfile->user_id,
        ]);

        SupervisorAssignmentChangedEvent::dispatch(
            $responsibility->supervisorProfile,
            $responsibility->responsable_id,
            'unassigned',
            $responsibility->supervisorProfile->user_id
        );
    }
}
