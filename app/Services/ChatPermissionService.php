<?php

namespace App\Services;

use App\Contracts\ChatPermissionServiceInterface;
use App\Enums\EnrollmentStatus;
use App\Enums\SessionSubscriptionStatus;
use App\Enums\UserType;
use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class ChatPermissionService implements ChatPermissionServiceInterface
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
            $targetUser->user_type === UserType::ADMIN->value) {
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
            $targetUser->user_type === UserType::ADMIN->value) {
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
        if ($targetUser->hasRole(User::ROLE_ACADEMY_ADMIN) || $targetUser->user_type === UserType::ADMIN->value) {
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
        // Get teacher profile IDs if needed
        $academicTeacherProfile = $teacher->academicTeacherProfile;
        $academicTeacherProfileId = $academicTeacherProfile ? $academicTeacherProfile->id : null;

        // Check multiple relationships - return true if any exists
        // Check Quran sessions
        if (DB::table('quran_sessions')
            ->where('quran_teacher_id', $teacher->id)
            ->where('student_id', $student->id)
            ->where('academy_id', $academyId)
            ->exists()) {
            return true;
        }

        // Academic sessions - uses profile ID
        if ($academicTeacherProfileId && DB::table('academic_sessions')
            ->where('academic_teacher_id', $academicTeacherProfileId)
            ->where('student_id', $student->id)
            ->where('academy_id', $academyId)
            ->exists()) {
            return true;
        }

        // Active academic subscriptions
        if (DB::table('academic_subscriptions')
            ->where('student_id', $student->id)
            ->where('teacher_id', $teacher->id)
            ->where('academy_id', $academyId)
            ->where('status', SessionSubscriptionStatus::ACTIVE->value)
            ->exists()) {
            return true;
        }

        // Active Quran subscriptions
        if (DB::table('quran_subscriptions')
            ->where('student_id', $student->id)
            ->where('quran_teacher_id', $teacher->id)
            ->where('academy_id', $academyId)
            ->where('status', SessionSubscriptionStatus::ACTIVE->value)
            ->exists()) {
            return true;
        }

        // Group Quran circle memberships
        if (DB::table('quran_circles')
            ->join('quran_circle_students', 'quran_circles.id', '=', 'quran_circle_students.circle_id')
            ->where('quran_circles.quran_teacher_id', $teacher->id)
            ->where('quran_circle_students.student_id', $student->id)
            ->where('quran_circles.academy_id', $academyId)
            ->where('quran_circle_students.status', EnrollmentStatus::ENROLLED->value)
            ->where('quran_circles.status', true)
            ->exists()) {
            return true;
        }

        return false;
    }

    /**
     * Check if user is parent of student
     */
    protected function isParentOfStudent(User $parent, User $student): bool
    {
        // Get parent and student profile IDs
        $parentProfile = $parent->parentProfile;
        $studentProfile = $student->studentProfile;

        if (! $parentProfile || ! $studentProfile) {
            return false;
        }

        return DB::table('parent_student_relationships')
            ->where('parent_id', $parentProfile->id)
            ->where('student_id', $studentProfile->id)
            ->exists();
    }

    /**
     * Check if teacher teaches any of parent's children
     */
    protected function isTeacherOfParentChildren(User $teacher, User $parent, int $academyId): bool
    {
        // Get parent profile
        $parentProfile = $parent->parentProfile;
        if (! $parentProfile) {
            return false;
        }

        // Get student profile IDs from parent relationships
        $studentProfileIds = DB::table('parent_student_relationships')
            ->where('parent_id', $parentProfile->id)
            ->pluck('student_id');

        if ($studentProfileIds->isEmpty()) {
            return false;
        }

        // Get actual user IDs from student profiles
        $childrenUserIds = DB::table('student_profiles')
            ->whereIn('id', $studentProfileIds)
            ->pluck('user_id');

        if ($childrenUserIds->isEmpty()) {
            return false;
        }

        // Get teacher profile IDs if needed
        $academicTeacherProfile = $teacher->academicTeacherProfile;
        $academicTeacherProfileId = $academicTeacherProfile ? $academicTeacherProfile->id : null;

        // Check if teacher has sessions with any of parent's children
        // Check Quran sessions
        if (DB::table('quran_sessions')
            ->where('quran_teacher_id', $teacher->id)
            ->whereIn('student_id', $childrenUserIds)
            ->where('academy_id', $academyId)
            ->exists()) {
            return true;
        }

        // Check Academic sessions
        if ($academicTeacherProfileId && DB::table('academic_sessions')
            ->where('academic_teacher_id', $academicTeacherProfileId)
            ->whereIn('student_id', $childrenUserIds)
            ->where('academy_id', $academyId)
            ->exists()) {
            return true;
        }

        return false;
    }

    /**
     * Clear permission cache for a user
     */
    public function clearUserCache(int $userId): void
    {
        // Delete all directional cache keys involving this user.
        // Cache keys are sorted pairs: chat:permission:{min}:{max}
        // We delete by scanning matching patterns rather than flushing all cache.
        $store = Cache::getStore();

        if ($store instanceof \Illuminate\Cache\RedisStore) {
            $redis = $store->connection();
            $prefix = config('cache.prefix', '')
                ? config('cache.prefix').':'.$this->cachePrefix
                : $this->cachePrefix;
            // Scan and delete keys matching this user on either side of the pair
            foreach (["$prefix*:$userId", "$prefix$userId:*"] as $pattern) {
                $keys = $redis->keys($pattern);
                if (! empty($keys)) {
                    $redis->del($keys);
                }
            }
        } else {
            // For non-Redis stores, flush only the known direct keys we can reconstruct.
            // We cannot enumerate all pairs, so this is best-effort for other drivers.
            Log::warning('ChatPermissionService::clearUserCache called on non-Redis store — cache not fully cleared for user '.$userId);
        }
    }

    /**
     * Generate cache key
     */
    protected function getCacheKey(int $userId1, int $userId2): string
    {
        // Always sort IDs to ensure consistent cache keys
        $ids = [$userId1, $userId2];
        sort($ids);

        return $this->cachePrefix.implode(':', $ids);
    }

    /**
     * Batch check permissions for multiple users
     * Returns array of user IDs that current user can message
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

    /**
     * Check if user can start a private (1-on-1) chat with another user.
     *
     * This method enforces the supervised chat policy:
     * - Teachers and students CANNOT start private chats directly
     * - They must use supervised group chats instead
     * - Admins and supervisors can chat with anyone
     *
     * @param  User  $initiator  The user trying to start the chat
     * @param  User  $target  The user they want to chat with
     * @return bool True if private chat is allowed
     */
    public function canStartPrivateChat(User $initiator, User $target): bool
    {
        // Don't allow messaging self
        if ($initiator->id === $target->id) {
            return false;
        }

        // Super admin can start private chats with anyone
        if ($initiator->hasRole(User::ROLE_SUPER_ADMIN)) {
            return true;
        }

        // Academy admin can start private chats with anyone in their academy
        if ($initiator->hasRole(User::ROLE_ACADEMY_ADMIN)) {
            return true;
        }

        // Supervisors can start private chats with anyone in their academy
        if ($initiator->hasRole(User::ROLE_SUPERVISOR)) {
            return true;
        }

        // Check if this is a teacher-student pair (in either direction)
        $isTeacherStudentPair = $this->isTeacherStudentPair($initiator, $target);

        if ($isTeacherStudentPair) {
            // Teacher-student private chats are NOT allowed
            // They must use supervised group chats
            return false;
        }

        // Allow other private chats (e.g., student-parent, teacher-teacher)
        return $this->canMessage($initiator, $target);
    }

    /**
     * Check if two users form a teacher-student pair.
     *
     * @return bool True if one is a teacher and the other is a student
     */
    protected function isTeacherStudentPair(User $user1, User $user2): bool
    {
        $isUser1Teacher = $user1->hasRole([User::ROLE_QURAN_TEACHER, User::ROLE_ACADEMIC_TEACHER])
            || $user1->isTeacher();
        $isUser1Student = $user1->hasRole(User::ROLE_STUDENT) || $user1->user_type === UserType::STUDENT->value;

        $isUser2Teacher = $user2->hasRole([User::ROLE_QURAN_TEACHER, User::ROLE_ACADEMIC_TEACHER])
            || $user2->isTeacher();
        $isUser2Student = $user2->hasRole(User::ROLE_STUDENT) || $user2->user_type === UserType::STUDENT->value;

        // True if one is a teacher and the other is a student
        return ($isUser1Teacher && $isUser2Student) || ($isUser1Student && $isUser2Teacher);
    }
}
