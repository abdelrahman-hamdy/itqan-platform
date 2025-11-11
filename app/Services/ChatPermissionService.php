<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class ChatPermissionService
{
    /**
     * Cache TTL in seconds (1 hour)
     */
    protected int $cacheTtl;

    /**
     * Cache prefix for permission checks
     */
    protected string $cachePrefix = 'chat:permission:';

    public function __construct()
    {
        $this->cacheTtl = config('chat.cache.ttl', 3600);
    }

    /**
     * Check if current user can message target user
     *
     * @param User $currentUser
     * @param User $targetUser
     * @return bool
     */
    public function canMessage(User $currentUser, User $targetUser): bool
    {
        // Don't allow messaging self
        if ($currentUser->id === $targetUser->id) {
            return false;
        }

        // Super admin can message anyone
        if ($currentUser->hasRole(User::ROLE_SUPER_ADMIN)) {
            return true;
        }

        // Users must be in the same academy (except super admin)
        if ($currentUser->academy_id !== $targetUser->academy_id) {
            return false;
        }

        // Check cache first
        $cacheKey = $this->getCacheKey($currentUser->id, $targetUser->id);

        return Cache::remember($cacheKey, $this->cacheTtl, function () use ($currentUser, $targetUser) {
            return $this->checkPermission($currentUser, $targetUser);
        });
    }

    /**
     * Perform actual permission check
     *
     * @param User $currentUser
     * @param User $targetUser
     * @return bool
     */
    protected function checkPermission(User $currentUser, User $targetUser): bool
    {
        $academyId = $currentUser->academy_id;

        // Academy admin can message all users in their academy
        if ($currentUser->hasRole(User::ROLE_ACADEMY_ADMIN)) {
            return true;
        }

        // Supervisor can message all users in their academy
        if ($currentUser->hasRole(User::ROLE_SUPERVISOR)) {
            return true;
        }

        // Student permissions
        if ($currentUser->hasRole(User::ROLE_STUDENT)) {
            return $this->checkStudentPermissions($currentUser, $targetUser, $academyId);
        }

        // Teacher permissions (Quran or Academic)
        if ($currentUser->hasRole([User::ROLE_QURAN_TEACHER, User::ROLE_ACADEMIC_TEACHER])) {
            return $this->checkTeacherPermissions($currentUser, $targetUser, $academyId);
        }

        // Parent permissions
        if ($currentUser->hasRole(User::ROLE_PARENT)) {
            return $this->checkParentPermissions($currentUser, $targetUser, $academyId);
        }

        return false;
    }

    /**
     * Check student messaging permissions
     */
    protected function checkStudentPermissions(User $student, User $targetUser, int $academyId): bool
    {
        // Can message academy admin, supervisors
        if ($targetUser->hasRole([User::ROLE_ACADEMY_ADMIN, User::ROLE_SUPERVISOR]) ||
            $targetUser->user_type === 'admin') {
            return true;
        }

        // Can message their teachers (both Quran and Academic)
        if ($targetUser->hasRole([User::ROLE_QURAN_TEACHER, User::ROLE_ACADEMIC_TEACHER])) {
            return $this->isTeacherOfStudent($targetUser, $student, $academyId);
        }

        // Can message their parents
        if ($targetUser->hasRole(User::ROLE_PARENT)) {
            return $this->isParentOfStudent($targetUser, $student);
        }

        return false;
    }

    /**
     * Check teacher messaging permissions
     */
    protected function checkTeacherPermissions(User $teacher, User $targetUser, int $academyId): bool
    {
        // Can message academy admin, supervisors
        if ($targetUser->hasRole([User::ROLE_ACADEMY_ADMIN, User::ROLE_SUPERVISOR]) ||
            $targetUser->user_type === 'admin') {
            return true;
        }

        // Can message their students
        if ($targetUser->hasRole(User::ROLE_STUDENT)) {
            return $this->isTeacherOfStudent($teacher, $targetUser, $academyId);
        }

        return false;
    }

    /**
     * Check parent messaging permissions
     */
    protected function checkParentPermissions(User $parent, User $targetUser, int $academyId): bool
    {
        // Can message academy admin
        if ($targetUser->hasRole(User::ROLE_ACADEMY_ADMIN) || $targetUser->user_type === 'admin') {
            return true;
        }

        // Can message their children
        if ($targetUser->hasRole(User::ROLE_STUDENT)) {
            return $this->isParentOfStudent($parent, $targetUser);
        }

        // Can message their children's teachers
        if ($targetUser->hasRole([User::ROLE_QURAN_TEACHER, User::ROLE_ACADEMIC_TEACHER])) {
            return $this->isTeacherOfParentChildren($targetUser, $parent, $academyId);
        }

        return false;
    }

    /**
     * Check if teacher teaches the student (optimized with single query)
     */
    protected function isTeacherOfStudent(User $teacher, User $student, int $academyId): bool
    {
        // Use a single query to check all relationships
        return DB::table(function ($query) use ($teacher, $student, $academyId) {
            // Quran sessions
            $query->select(DB::raw('1 as has_relationship'))
                ->from('quran_sessions')
                ->where('quran_teacher_id', $teacher->id)
                ->where('student_id', $student->id)
                ->where('academy_id', $academyId)

                // Academic sessions
                ->unionAll(
                    DB::table('academic_sessions')
                        ->select(DB::raw('1 as has_relationship'))
                        ->where('academic_teacher_id', $teacher->id)
                        ->where('student_id', $student->id)
                        ->where('academy_id', $academyId)
                )

                // Active academic subscriptions
                ->unionAll(
                    DB::table('academic_subscriptions')
                        ->select(DB::raw('1 as has_relationship'))
                        ->where('student_id', $student->id)
                        ->where('teacher_id', $teacher->id)
                        ->where('academy_id', $academyId)
                        ->where('status', 'active')
                )

                // Active Quran subscriptions
                ->unionAll(
                    DB::table('quran_subscriptions')
                        ->select(DB::raw('1 as has_relationship'))
                        ->where('student_id', $student->id)
                        ->where('quran_teacher_id', $teacher->id)
                        ->where('academy_id', $academyId)
                        ->where('subscription_status', 'active')
                )

                // Group Quran circle memberships
                ->unionAll(
                    DB::table('quran_circles')
                        ->select(DB::raw('1 as has_relationship'))
                        ->join('quran_circle_students', 'quran_circles.id', '=', 'quran_circle_students.quran_circle_id')
                        ->where('quran_circles.quran_teacher_id', $teacher->id)
                        ->where('quran_circle_students.student_id', $student->id)
                        ->where('quran_circles.academy_id', $academyId)
                        ->where('quran_circle_students.status', 'active')
                        ->where('quran_circles.status', 'active')
                );
        })->exists();
    }

    /**
     * Check if user is parent of student
     */
    protected function isParentOfStudent(User $parent, User $student): bool
    {
        return DB::table('parent_students')
            ->where('parent_id', $parent->id)
            ->where('student_id', $student->id)
            ->exists();
    }

    /**
     * Check if teacher teaches any of parent's children
     */
    protected function isTeacherOfParentChildren(User $teacher, User $parent, int $academyId): bool
    {
        $childrenIds = DB::table('parent_students')
            ->where('parent_id', $parent->id)
            ->pluck('student_id');

        if ($childrenIds->isEmpty()) {
            return false;
        }

        return DB::table(function ($query) use ($teacher, $childrenIds, $academyId) {
            $query->select(DB::raw('1 as has_relationship'))
                ->from('quran_sessions')
                ->where('quran_teacher_id', $teacher->id)
                ->whereIn('student_id', $childrenIds)
                ->where('academy_id', $academyId)

                ->unionAll(
                    DB::table('academic_sessions')
                        ->select(DB::raw('1 as has_relationship'))
                        ->where('academic_teacher_id', $teacher->id)
                        ->whereIn('student_id', $childrenIds)
                        ->where('academy_id', $academyId)
                );
        })->exists();
    }

    /**
     * Clear permission cache for a user
     */
    public function clearUserCache(int $userId): void
    {
        $pattern = $this->cachePrefix . '*:' . $userId . ':*';
        // Note: This assumes Redis cache driver. For other drivers, implement accordingly.
        Cache::flush(); // Simple approach - flush all cache
    }

    /**
     * Generate cache key
     */
    protected function getCacheKey(int $userId1, int $userId2): string
    {
        // Always sort IDs to ensure consistent cache keys
        $ids = [$userId1, $userId2];
        sort($ids);
        return $this->cachePrefix . implode(':', $ids);
    }

    /**
     * Batch check permissions for multiple users
     * Returns array of user IDs that current user can message
     *
     * @param User $currentUser
     * @param array $userIds
     * @return array
     */
    public function filterAllowedContacts(User $currentUser, array $userIds): array
    {
        $allowedIds = [];

        foreach ($userIds as $userId) {
            $user = User::find($userId);
            if ($user && $this->canMessage($currentUser, $user)) {
                $allowedIds[] = $userId;
            }
        }

        return $allowedIds;
    }
}
