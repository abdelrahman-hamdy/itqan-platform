<?php

namespace App\Events;

use App\Models\SupervisorProfile;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class SupervisorAssignmentChangedEvent
{
    use Dispatchable, SerializesModels;

    /**
     * Create a new event instance.
     *
     * @param  SupervisorProfile  $supervisorProfile  The supervisor profile involved
     * @param  int  $teacherId  The teacher's user ID
     * @param  string  $changeType  Either 'assigned' or 'unassigned'
     * @param  int|null  $previousSupervisorUserId  The previous supervisor's user ID (if unassigned)
     */
    public function __construct(
        public readonly SupervisorProfile $supervisorProfile,
        public readonly int $teacherId,
        public readonly string $changeType,
        public readonly ?int $previousSupervisorUserId = null
    ) {}

    /**
     * Check if this is an assignment event.
     */
    public function isAssignment(): bool
    {
        return $this->changeType === 'assigned';
    }

    /**
     * Check if this is an unassignment event.
     */
    public function isUnassignment(): bool
    {
        return $this->changeType === 'unassigned';
    }
}
