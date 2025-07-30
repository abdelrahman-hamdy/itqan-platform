<?php

namespace App\Services;

use App\Models\Academy;
use App\Models\User;
use Illuminate\Support\Facades\Session;

class AcademyContextService
{
    const SELECTED_ACADEMY_SESSION_KEY = 'selected_academy_id';
    const ACADEMY_OBJECT_SESSION_KEY = 'selected_academy';

    /**
     * Get the current academy context for the authenticated user
     */
    public static function getCurrentAcademy(): ?Academy
    {
        $user = auth()->user();
        
        if (!$user) {
            return null;
        }

        // For super admin, use selected academy from session
        if (self::isSuperAdmin($user)) {
            $academyId = Session::get(self::SELECTED_ACADEMY_SESSION_KEY);
            
            if ($academyId) {
                $academy = Academy::find($academyId);
                if ($academy) {
                    // Cache academy object in session to avoid repeated queries
                    Session::put(self::ACADEMY_OBJECT_SESSION_KEY, $academy);
                    return $academy;
                }
            }
            
            return null; // Super admin with no academy selected
        }

        // For regular users, use their assigned academy
        return $user->academy;
    }

    /**
     * Get the current academy ID for database queries
     */
    public static function getCurrentAcademyId(): ?int
    {
        $academy = self::getCurrentAcademy();
        return $academy?->id;
    }

    /**
     * Set the academy context for super admin
     */
    public static function setAcademyContext(?int $academyId): bool
    {
        $user = auth()->user();
        
        if (!$user || !self::isSuperAdmin($user)) {
            return false;
        }

        if ($academyId) {
            $academy = Academy::find($academyId);
            if (!$academy) {
                return false;
            }

            Session::put(self::SELECTED_ACADEMY_SESSION_KEY, $academyId);
            Session::put(self::ACADEMY_OBJECT_SESSION_KEY, $academy);
            
            // Make academy available globally for this request
            app()->instance('current_academy', $academy);
        } else {
            // Clear academy context
            Session::forget(self::SELECTED_ACADEMY_SESSION_KEY);
            Session::forget(self::ACADEMY_OBJECT_SESSION_KEY);
            app()->forgetInstance('current_academy');
        }

        return true;
    }

    /**
     * Check if user is super admin
     */
    public static function isSuperAdmin(?User $user = null): bool
    {
        $user = $user ?? auth()->user();
        return $user && $user->role === 'super_admin';
    }

    /**
     * Check if super admin has an academy selected
     */
    public static function hasAcademySelected(): bool
    {
        return self::isSuperAdmin() && Session::has(self::SELECTED_ACADEMY_SESSION_KEY);
    }

    /**
     * Get all academies available for super admin selection
     */
    public static function getAvailableAcademies(): \Illuminate\Database\Eloquent\Collection
    {
        return Academy::where('is_active', true)
            ->orderBy('name')
            ->get();
    }

    /**
     * Clear academy context
     */
    public static function clearAcademyContext(): void
    {
        Session::forget(self::SELECTED_ACADEMY_SESSION_KEY);
        Session::forget(self::ACADEMY_OBJECT_SESSION_KEY);
        app()->forgetInstance('current_academy');
    }

    /**
     * Check if current user can access multi-academy management
     */
    public static function canManageMultipleAcademies(): bool
    {
        return self::isSuperAdmin();
    }

    /**
     * Get academy context info for debugging
     */
    public static function getContextInfo(): array
    {
        $user = auth()->user();
        $currentAcademy = self::getCurrentAcademy();
        
        return [
            'user_id' => $user?->id,
            'user_role' => $user?->role,
            'user_academy_id' => $user?->academy_id,
            'is_super_admin' => self::isSuperAdmin($user),
            'selected_academy_id' => Session::get(self::SELECTED_ACADEMY_SESSION_KEY),
            'current_academy_id' => $currentAcademy?->id,
            'current_academy_name' => $currentAcademy?->name,
            'has_academy_selected' => self::hasAcademySelected(),
        ];
    }
} 