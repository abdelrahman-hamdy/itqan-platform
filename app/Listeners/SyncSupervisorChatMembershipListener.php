<?php

namespace App\Listeners;

use Exception;
use Throwable;
use App\Contracts\SupervisedChatGroupServiceInterface;
use App\Events\SupervisorAssignmentChangedEvent;
use App\Models\User;
use App\Services\SupervisorResolutionService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Log;

class SyncSupervisorChatMembershipListener implements ShouldQueue
{
    /**
     * The queue to use for this listener.
     */
    public string $queue = 'default';

    /**
     * The number of times the job may be attempted.
     */
    public int $tries = 3;

    /**
     * The number of seconds to wait before retrying (exponential backoff).
     *
     * @var array<int>
     */
    public array $backoff = [30, 60, 120];

    /**
     * Create the event listener.
     */
    public function __construct(
        protected SupervisedChatGroupServiceInterface $chatGroupService,
        protected SupervisorResolutionService $supervisorService
    ) {}

    /**
     * Handle the event.
     */
    public function handle(SupervisorAssignmentChangedEvent $event): void
    {
        try {
            $teacher = User::find($event->teacherId);

            if (! $teacher) {
                Log::warning('Teacher not found for supervisor sync', [
                    'teacher_id' => $event->teacherId,
                ]);

                return;
            }

            if ($event->isAssignment()) {
                $this->handleAssignment($event, $teacher);
            } else {
                $this->handleUnassignment($event, $teacher);
            }
        } catch (Exception $e) {
            Log::error('SyncSupervisorChatMembership failed', [
                'supervisor_profile_id' => $event->supervisorProfile->id,
                'teacher_id' => $event->teacherId,
                'change_type' => $event->changeType,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            throw $e;
        }
    }

    /**
     * Handle supervisor assignment.
     */
    protected function handleAssignment(SupervisorAssignmentChangedEvent $event, User $teacher): void
    {
        $supervisor = $event->supervisorProfile->user;

        if (! $supervisor) {
            Log::warning('Supervisor profile has no user', [
                'supervisor_profile_id' => $event->supervisorProfile->id,
            ]);

            return;
        }

        // Add supervisor to all existing chat groups for this teacher
        $updatedCount = $this->chatGroupService->addSupervisorToTeacherGroups($teacher, $supervisor);

        Log::info('Supervisor assigned to teacher chat groups', [
            'teacher_id' => $teacher->id,
            'supervisor_id' => $supervisor->id,
            'groups_updated' => $updatedCount,
        ]);
    }

    /**
     * Handle supervisor unassignment.
     */
    protected function handleUnassignment(SupervisorAssignmentChangedEvent $event, User $teacher): void
    {
        // Check if teacher has a new supervisor
        $newSupervisor = $this->supervisorService->getSupervisorForTeacher($teacher);

        if ($newSupervisor) {
            // Teacher was reassigned to a different supervisor
            $oldSupervisor = $event->previousSupervisorUserId ? User::find($event->previousSupervisorUserId) : null;

            $this->supervisorService->handleSupervisorChange($teacher, $oldSupervisor, $newSupervisor);

            Log::info('Teacher reassigned to new supervisor', [
                'teacher_id' => $teacher->id,
                'old_supervisor_id' => $oldSupervisor?->id,
                'new_supervisor_id' => $newSupervisor->id,
            ]);
        } else {
            // Teacher no longer has a supervisor - remove old supervisor from chats
            if ($event->previousSupervisorUserId) {
                $oldSupervisor = User::find($event->previousSupervisorUserId);

                if ($oldSupervisor) {
                    $this->supervisorService->removeSupervisorFromTeacherChats($teacher, $oldSupervisor);

                    Log::info('Supervisor removed from teacher chats (no replacement)', [
                        'teacher_id' => $teacher->id,
                        'old_supervisor_id' => $oldSupervisor->id,
                    ]);
                }
            }
        }
    }

    /**
     * Handle a job failure.
     */
    public function failed(SupervisorAssignmentChangedEvent $event, Throwable $exception): void
    {
        Log::error('SyncSupervisorChatMembershipListener job failed', [
            'supervisor_profile_id' => $event->supervisorProfile->id,
            'teacher_id' => $event->teacherId,
            'change_type' => $event->changeType,
            'error' => $exception->getMessage(),
        ]);
    }
}
