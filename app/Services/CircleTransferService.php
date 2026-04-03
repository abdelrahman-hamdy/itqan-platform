<?php

namespace App\Services;

use App\Enums\SessionStatus;
use App\Enums\SessionSubscriptionStatus;
use App\Models\ChatGroup;
use App\Models\QuranIndividualCircle;
use App\Models\QuranSession;
use App\Models\QuranSubscription;
use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class CircleTransferService
{
    public function __construct(
        private SupervisedChatGroupService $chatGroupService,
        private ChatPermissionService $chatPermissionService,
    ) {}

    public function transfer(
        QuranIndividualCircle $circle,
        User $newTeacher,
        User $performedBy,
        ?string $reason = null,
    ): void {
        $this->validate($circle, $newTeacher);

        $oldTeacherId = $circle->quran_teacher_id;

        DB::transaction(function () use ($circle, $newTeacher, $oldTeacherId, $performedBy, $reason) {
            // 1. Update circle
            $circle->update(['quran_teacher_id' => $newTeacher->id]);

            // 2. Update active/pending subscriptions (both polymorphic and legacy FK paths)
            $activeStatuses = [SessionSubscriptionStatus::ACTIVE, SessionSubscriptionStatus::PENDING];

            QuranSubscription::where('education_unit_type', QuranIndividualCircle::class)
                ->where('education_unit_id', $circle->id)
                ->whereIn('status', $activeStatuses)
                ->update(['quran_teacher_id' => $newTeacher->id]);

            if ($circle->subscription_id) {
                QuranSubscription::where('id', $circle->subscription_id)
                    ->whereIn('status', $activeStatuses)
                    ->update(['quran_teacher_id' => $newTeacher->id]);
            }

            // 3. Update future scheduled sessions only
            $updatedSessions = QuranSession::where('individual_circle_id', $circle->id)
                ->whereIn('status', array_map(fn ($s) => $s->value, SessionStatus::upcomingStatuses()))
                ->update(['quran_teacher_id' => $newTeacher->id]);

            // 4. Update chat group
            $chatGroupUpdated = $this->transferChatGroup($circle, $oldTeacherId, $newTeacher);

            // 5. Clear permission caches
            $this->clearCaches($oldTeacherId, $newTeacher->id, $circle->student_id, $circle->academy_id);

            // 6. Log via Spatie Activity Log
            activity('circle_transfer')
                ->performedOn($circle)
                ->causedBy($performedBy)
                ->withProperties([
                    'old_teacher_id' => $oldTeacherId,
                    'new_teacher_id' => $newTeacher->id,
                    'circle_code' => $circle->circle_code,
                    'student_id' => $circle->student_id,
                    'reason' => $reason,
                    'sessions_updated' => $updatedSessions,
                    'chat_group_updated' => $chatGroupUpdated,
                ])
                ->log(__('circles.transfer.log_message'));
        });

        // 7. Notify student (outside transaction — non-critical)
        try {
            $student = $circle->student;
            if ($student) {
                $oldTeacher = User::find($oldTeacherId);
                $student->notify(new \App\Notifications\CircleTeacherChangedNotification(
                    $circle,
                    $oldTeacher,
                    $newTeacher,
                ));
            }
        } catch (\Throwable $e) {
            Log::warning('Failed to notify student about circle transfer', [
                'circle_id' => $circle->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    private function validate(QuranIndividualCircle $circle, User $newTeacher): void
    {
        if ($newTeacher->id === $circle->quran_teacher_id) {
            throw new \InvalidArgumentException(__('circles.transfer.same_teacher_error'));
        }

        if ($newTeacher->academy_id !== $circle->academy_id) {
            throw new \InvalidArgumentException(__('common.unauthorized'));
        }
    }

    private function transferChatGroup(QuranIndividualCircle $circle, int $oldTeacherId, User $newTeacher): bool
    {
        $chatGroup = ChatGroup::where('quran_individual_circle_id', $circle->id)->first();
        if (! $chatGroup) {
            return false;
        }

        // Swap teacher: remove old, add new as admin
        $oldTeacher = User::find($oldTeacherId);
        if ($oldTeacher && $chatGroup->hasMember($oldTeacher)) {
            $chatGroup->members()->detach($oldTeacher->id);
        }

        if (! $chatGroup->hasMember($newTeacher)) {
            $chatGroup->members()->attach($newTeacher->id, ['role' => 'admin']);
        }

        // Update owner
        $chatGroup->update([
            'owner_id' => $newTeacher->id,
            'metadata' => array_merge($chatGroup->metadata ?? [], [
                'teacher_name' => trim(($newTeacher->first_name ?? '').' '.($newTeacher->last_name ?? '')),
            ]),
        ]);

        // Update WireChat participant if conversation exists
        if ($chatGroup->conversation_id) {
            \Namu\WireChat\Models\Participant::where('conversation_id', $chatGroup->conversation_id)
                ->where('participantable_id', $oldTeacherId)
                ->where('participantable_type', User::class)
                ->update(['participantable_id' => $newTeacher->id]);
        }

        // Resolve new supervisor and swap if different
        try {
            $supervisorService = app(SupervisorResolutionService::class);
            $newSupervisor = $supervisorService->getSupervisorForTeacher($newTeacher);
            if ($newSupervisor && $chatGroup->supervisor_id !== $newSupervisor->id) {
                $this->chatGroupService->replaceSupervisor($chatGroup, $chatGroup->supervisor, $newSupervisor);
            }
        } catch (\Throwable $e) {
            Log::warning('Failed to update supervisor during circle transfer', [
                'circle_id' => $circle->id,
                'error' => $e->getMessage(),
            ]);
        }

        return true;
    }

    private function clearCaches(int $oldTeacherId, int $newTeacherId, int $studentId, int $academyId): void
    {
        $this->chatPermissionService->clearUserCache($oldTeacherId);
        $this->chatPermissionService->clearUserCache($newTeacherId);

        Cache::forget("chat_perm:{$oldTeacherId}:{$studentId}:{$academyId}");
        Cache::forget("chat_perm:{$newTeacherId}:{$studentId}:{$academyId}");
        Cache::forget("supervisor_for_teacher_{$newTeacherId}");
    }
}
