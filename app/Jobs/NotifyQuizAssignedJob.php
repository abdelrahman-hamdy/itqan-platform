<?php

namespace App\Jobs;

use App\Models\QuizAssignment;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class NotifyQuizAssignedJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public int $timeout = 120;

    public function __construct(public readonly int|string $assignmentId)
    {
        $this->onQueue('notifications');
    }

    public function handle(): void
    {
        $assignment = QuizAssignment::find($this->assignmentId);
        if (! $assignment) {
            return;
        }
        $assignment->notifyQuizAssigned();
    }
}
