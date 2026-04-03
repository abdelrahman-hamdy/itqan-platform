<?php

namespace App\Services;

use App\Enums\SessionStatus;
use App\Enums\SessionSubscriptionStatus;
use App\Models\ChatGroup;
use App\Models\QuranIndividualCircle;
use App\Models\QuranSession;
use App\Models\QuranSubscription;
use App\Models\QuranTeacherProfile;
use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Wirechat\Wirechat\Models\Participant;

class CircleTransferService
{
    public function __construct(
        private SupervisedChatGroupService $chatGroupService,
        private ChatPermissionService $chatPermissionService,
        private SupervisorResolutionService $supervisorResolutionService,
    ) {}

    public function transfer(
        QuranIndividualCircle $circle,
        User $newTeacher,
        User $performedBy,
        ?string $reason = null,
    ): void {
        $this->validate($circle, $newTeacher);

        $oldTeacher = User::findOrFail($circle->quran_teacher_id);

        DB::transaction(function () use ($circle, $oldTeacher, $newTeacher, $performedBy, $reason) {
            $circle->update(['quran_teacher_id' => $newTeacher->id]);

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

            $updatedSessions = QuranSession::where('individual_circle_id', $circle->id)
                ->whereIn('status', array_map(fn ($s) => $s->value, SessionStatus::upcomingStatuses()))
                ->update(['quran_teacher_id' => $newTeacher->id]);

            $chatGroupUpdated = $this->transferChatGroup($circle, $oldTeacher, $newTeacher);

            $this->clearCaches($oldTeacher, $newTeacher, $circle->student_id, $circle->academy_id);

            activity('circle_transfer')
                ->performedOn($circle)
                ->causedBy($performedBy)
                ->withProperties([
                    'old_teacher_id' => $oldTeacher->id,
                    'new_teacher_id' => $newTeacher->id,
                    'circle_code' => $circle->circle_code,
                    'student_id' => $circle->student_id,
                    'reason' => $reason,
                    'sessions_updated' => $updatedSessions,
                    'chat_group_updated' => $chatGroupUpdated,
                ])
                ->log(__('circles.transfer.log_message'));
        });

        try {
            $student = $circle->student;
            if ($student) {
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

        $hasProfile = QuranTeacherProfile::where('user_id', $newTeacher->id)
            ->where('academy_id', $circle->academy_id)
            ->exists();

        if (! $hasProfile || ! $newTeacher->active_status) {
            throw new \InvalidArgumentException(__('subscriptions.no_teachers_available'));
        }
    }

    private function transferChatGroup(QuranIndividualCircle $circle, User $oldTeacher, User $newTeacher): bool
    {
        $chatGroup = ChatGroup::where('quran_individual_circle_id', $circle->id)->first();
        if (! $chatGroup) {
            return false;
        }

        if ($chatGroup->hasMember($oldTeacher)) {
            $chatGroup->members()->detach($oldTeacher->id);
        }

        if (! $chatGroup->hasMember($newTeacher)) {
            $chatGroup->members()->attach($newTeacher->id, ['role' => 'admin']);
        }

        $chatGroup->update([
            'owner_id' => $newTeacher->id,
            'metadata' => array_merge($chatGroup->metadata ?? [], [
                'teacher_name' => $newTeacher->name,
            ]),
        ]);

        if ($chatGroup->conversation_id) {
            Participant::where('conversation_id', $chatGroup->conversation_id)
                ->where('participantable_id', $oldTeacher->id)
                ->where('participantable_type', User::class)
                ->update(['participantable_id' => $newTeacher->id]);
        }

        try {
            $newSupervisor = $this->supervisorResolutionService->getSupervisorForTeacher($newTeacher);
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

    private function clearCaches(User $oldTeacher, User $newTeacher, int $studentId, int $academyId): void
    {
        $this->chatPermissionService->clearUserCache($oldTeacher->id);
        $this->chatPermissionService->clearUserCache($newTeacher->id);

        Cache::forget("chat_perm:{$oldTeacher->id}:{$studentId}:{$academyId}");
        Cache::forget("chat_perm:{$newTeacher->id}:{$studentId}:{$academyId}");

        $this->supervisorResolutionService->clearTeacherCache($newTeacher);
    }
}
