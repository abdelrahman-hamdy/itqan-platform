<?php

namespace App\Services;

use App\Models\ChatGroup;
use App\Models\SupervisorProfile;
use App\Models\SupervisorResponsibility;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class SupervisorResolutionService
{
    /**
     * Cache TTL in seconds (1 hour)
     */
    private const CACHE_TTL = 3600;

    /**
     * Find the supervisor responsible for a given teacher.
     * Returns null if no supervisor is assigned.
     */
    public function getSupervisorForTeacher(User $teacher): ?User
    {
        if (!in_array($teacher->user_type, ['quran_teacher', 'academic_teacher'])) {
            return null;
        }

        $cacheKey = "supervisor_for_teacher_{$teacher->id}";

        return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($teacher) {
            $responsibility = SupervisorResponsibility::where('responsable_type', User::class)
                ->where('responsable_id', $teacher->id)
                ->with('supervisorProfile.user')
                ->first();

            if (!$responsibility || !$responsibility->supervisorProfile) {
                return null;
            }

            return $responsibility->supervisorProfile->user;
        });
    }

    /**
     * Check if a teacher has an assigned supervisor.
     * Used to determine if chat functionality should be available.
     */
    public function teacherHasSupervisor(User $teacher): bool
    {
        if (!in_array($teacher->user_type, ['quran_teacher', 'academic_teacher'])) {
            return false;
        }

        $cacheKey = "teacher_has_supervisor_{$teacher->id}";

        return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($teacher) {
            return SupervisorResponsibility::where('responsable_type', User::class)
                ->where('responsable_id', $teacher->id)
                ->exists();
        });
    }

    /**
     * Get all teachers assigned to a specific supervisor.
     */
    public function getTeachersForSupervisor(User $supervisor): Collection
    {
        $supervisorProfile = $supervisor->supervisorProfile;

        if (!$supervisorProfile) {
            return collect();
        }

        $teacherIds = SupervisorResponsibility::where('supervisor_profile_id', $supervisorProfile->id)
            ->where('responsable_type', User::class)
            ->pluck('responsable_id');

        return User::whereIn('id', $teacherIds)
            ->whereIn('user_type', ['quran_teacher', 'academic_teacher'])
            ->get();
    }

    /**
     * Get all chat groups that a supervisor should be monitoring.
     */
    public function getSupervisedChatGroups(User $supervisor): Collection
    {
        return ChatGroup::where('supervisor_id', $supervisor->id)
            ->where('is_active', true)
            ->get();
    }

    /**
     * When a teacher is reassigned to a new supervisor, update all related chat groups.
     */
    public function handleSupervisorChange(User $teacher, ?User $oldSupervisor, User $newSupervisor): int
    {
        return DB::transaction(function () use ($teacher, $oldSupervisor, $newSupervisor) {
            $chatGroupService = app(ChatGroupService::class);

            // Find all chat groups where this teacher is involved
            $chatGroups = $this->getTeacherChatGroups($teacher);

            $updatedCount = 0;

            foreach ($chatGroups as $group) {
                // Remove old supervisor from group
                if ($oldSupervisor && $group->hasMember($oldSupervisor)) {
                    $chatGroupService->removeMember($group, $oldSupervisor);
                }

                // Add new supervisor to group as moderator
                if (!$group->hasMember($newSupervisor)) {
                    $chatGroupService->addMember($group, $newSupervisor, ChatGroup::ROLE_MODERATOR);
                }

                // Update supervisor_id on the group
                $group->update(['supervisor_id' => $newSupervisor->id]);

                $updatedCount++;
            }

            // Clear cache for this teacher
            $this->clearTeacherCache($teacher);

            Log::info('Supervisor change processed', [
                'teacher_id' => $teacher->id,
                'old_supervisor_id' => $oldSupervisor?->id,
                'new_supervisor_id' => $newSupervisor->id,
                'groups_updated' => $updatedCount,
            ]);

            return $updatedCount;
        });
    }

    /**
     * Remove supervisor from all teacher's chat groups.
     * Called when a teacher loses their supervisor assignment.
     */
    public function removeSupervisorFromTeacherChats(User $teacher, User $supervisor): int
    {
        return DB::transaction(function () use ($teacher, $supervisor) {
            $chatGroupService = app(ChatGroupService::class);

            $chatGroups = $this->getTeacherChatGroups($teacher);
            $removedCount = 0;

            foreach ($chatGroups as $group) {
                if ($group->hasMember($supervisor)) {
                    $chatGroupService->removeMember($group, $supervisor);
                    $group->update(['supervisor_id' => null]);
                    $removedCount++;
                }
            }

            // Clear cache for this teacher
            $this->clearTeacherCache($teacher);

            Log::info('Supervisor removed from teacher chats', [
                'teacher_id' => $teacher->id,
                'supervisor_id' => $supervisor->id,
                'groups_updated' => $removedCount,
            ]);

            return $removedCount;
        });
    }

    /**
     * Validate that a supervisor change is valid.
     */
    public function validateSupervisorChange(User $teacher, User $newSupervisor): bool
    {
        // Supervisor must be in the same academy as teacher
        if ($teacher->academy_id !== $newSupervisor->academy_id) {
            throw new \InvalidArgumentException('Supervisor must be in the same academy as teacher');
        }

        // New supervisor must actually be a supervisor
        if ($newSupervisor->user_type !== 'supervisor') {
            throw new \InvalidArgumentException('Target user is not a supervisor');
        }

        return true;
    }

    /**
     * Get all chat groups associated with a teacher.
     */
    public function getTeacherChatGroups(User $teacher): Collection
    {
        // Get groups where teacher is owner or admin member
        return ChatGroup::where('is_active', true)
            ->where(function ($query) use ($teacher) {
                $query->where('owner_id', $teacher->id)
                    ->orWhereHas('memberships', function ($q) use ($teacher) {
                        $q->where('user_id', $teacher->id)
                            ->where('role', ChatGroup::ROLE_ADMIN);
                    });
            })
            ->get();
    }

    /**
     * Clear supervisor-related cache for a teacher.
     */
    public function clearTeacherCache(User $teacher): void
    {
        Cache::forget("supervisor_for_teacher_{$teacher->id}");
        Cache::forget("teacher_has_supervisor_{$teacher->id}");
    }

    /**
     * Clear all supervisor-related cache for a supervisor.
     */
    public function clearSupervisorCache(User $supervisor): void
    {
        // Get all teachers assigned to this supervisor and clear their cache
        $teachers = $this->getTeachersForSupervisor($supervisor);

        foreach ($teachers as $teacher) {
            $this->clearTeacherCache($teacher);
        }
    }
}
