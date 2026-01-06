<?php

namespace App\Contracts;

use App\Models\User;

/**
 * Chat Permission Service Interface
 *
 * Defines the contract for chat permission checking service.
 * This service implements a matrix-based permission system to determine
 * which users can message each other based on their roles and relationships.
 *
 * Key features:
 * - Role-based permission matrix
 * - Relationship-aware checks (teacher-student, parent-child, etc.)
 * - Cache-optimized permission lookups
 * - Multi-tenancy support (academy-scoped permissions)
 *
 * Permission Rules:
 * - Super admins can message anyone
 * - Academy admins and supervisors can message users in their academy
 * - Teachers can message their students and academy staff
 * - Students can message their teachers, parents, and academy staff
 * - Parents can message their children, their children's teachers, and academy staff
 */
interface ChatPermissionServiceInterface
{
    /**
     * Check if current user can message target user.
     *
     * Performs comprehensive permission check based on:
     * - User roles and relationships
     * - Academy membership
     * - Active sessions/subscriptions
     * - Parent-child relationships
     *
     * Results are cached for performance.
     *
     * @param  User  $currentUser  The user initiating the message
     * @param  User  $targetUser  The user being messaged
     * @return bool True if current user can message target user
     */
    public function canMessage(User $currentUser, User $targetUser): bool;

    /**
     * Clear permission cache for a user.
     *
     * Should be called when user relationships change (e.g., new subscription,
     * enrollment status change, role update).
     *
     * @param  int  $userId  The user ID to clear cache for
     */
    public function clearUserCache(int $userId): void;

    /**
     * Batch check permissions for multiple users.
     *
     * Efficiently filters a list of user IDs to only those the current user
     * can message. Useful for contact list generation.
     *
     * @param  User  $currentUser  The user checking permissions
     * @param  array  $userIds  Array of user IDs to check
     * @return array Array of user IDs that current user can message
     */
    public function filterAllowedContacts(User $currentUser, array $userIds): array;
}
