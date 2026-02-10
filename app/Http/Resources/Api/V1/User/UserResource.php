<?php

namespace App\Http\Resources\Api\V1\User;

use App\Http\Helpers\ImageHelper;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin User
 */
class UserResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'email' => $this->email,
            'first_name' => $this->first_name,
            'last_name' => $this->last_name,
            'full_name' => $this->name,
            'phone' => $this->phone,
            // Legacy field for backward compatibility
            'avatar_url' => $this->getAvatarUrl(),
            // New structured avatar with size variants for mobile optimization
            'avatar' => $this->getAvatarVariants(),
            'user_type' => $this->user_type,
            'user_type_label' => $this->getUserTypeLabel(),
            'is_active' => $this->isActive(),
            'email_verified' => $this->hasVerifiedEmail(),
            'phone_verified' => $this->hasVerifiedPhone(),
            'profile_completed' => $this->hasCompletedProfile(),
            'last_login_at' => $this->last_login_at?->toISOString(),
            'created_at' => $this->created_at->toISOString(),
            'updated_at' => $this->updated_at->toISOString(),

            // Academy info
            'academy' => [
                'id' => $this->academy_id,
                'name' => $this->academy?->name,
                'subdomain' => $this->academy?->subdomain,
            ],

            // Profile based on user type
            'profile' => $this->getProfileData(),
        ];
    }

    /**
     * Get avatar URL (legacy single URL)
     */
    protected function getAvatarUrl(): ?string
    {
        $avatarPath = $this->getAvatarPath();

        if ($avatarPath && str_starts_with($avatarPath, 'http')) {
            return $avatarPath;
        }

        if ($avatarPath) {
            return asset('storage/'.$avatarPath);
        }

        // Generate default avatar
        return config('services.ui_avatars.base_url', 'https://ui-avatars.com/api/').'?name='.urlencode($this->name).'&background=0ea5e9&color=fff';
    }

    /**
     * Get avatar variants for mobile optimization
     *
     * Returns structured object with different sizes:
     * - thumb: 100px - for lists, small icons
     * - small: 200px - for compact displays
     * - medium: 400px - for standard displays
     * - large: 800px - for full-screen/profile pages
     * - original: full resolution
     */
    protected function getAvatarVariants(): array
    {
        $avatarPath = $this->getAvatarPath();

        return ImageHelper::getAvatarUrls($avatarPath, $this->name);
    }

    /**
     * Get raw avatar path from user or profile
     */
    private function getAvatarPath(): ?string
    {
        if ($this->avatar) {
            return $this->avatar;
        }

        // Check profile for avatar
        $profile = $this->resource->getProfile();
        if ($profile && isset($profile->avatar) && $profile->avatar) {
            return $profile->avatar;
        }

        return null;
    }

    /**
     * Get profile data based on user type
     */
    protected function getProfileData(): ?array
    {
        $profile = $this->resource->getProfile();

        if (! $profile) {
            return null;
        }

        return match ($this->user_type) {
            'student' => $this->formatStudentProfile($profile),
            'parent' => $this->formatParentProfile($profile),
            'quran_teacher' => $this->formatQuranTeacherProfile($profile),
            'academic_teacher' => $this->formatAcademicTeacherProfile($profile),
            'supervisor' => $this->formatSupervisorProfile($profile),
            'admin', 'super_admin' => $this->formatAdminProfile(),
            default => null,
        };
    }

    /**
     * Format student profile
     */
    protected function formatStudentProfile($profile): array
    {
        return [
            'id' => $profile->id,
            'student_code' => $profile->student_code,
            'grade_level' => [
                'id' => $profile->grade_level_id,
                'name' => $profile->gradeLevel?->name,
            ],
            'birth_date' => $profile->birth_date?->format('Y-m-d'),
            'age' => $profile->birth_date?->age,
            'gender' => $profile->gender,
            'nationality' => $profile->nationality,
            'enrollment_date' => $profile->enrollment_date?->format('Y-m-d'),
        ];
    }

    /**
     * Format parent profile
     */
    protected function formatParentProfile($profile): array
    {
        return [
            'id' => $profile->id,
            'parent_code' => $profile->parent_code ?? null,
            'relationship_type' => $profile->relationship_type,
            'occupation' => $profile->occupation,
            'preferred_contact_method' => $profile->preferred_contact_method,
            'children_count' => $profile->children?->count() ?? 0,
        ];
    }

    /**
     * Format Quran teacher profile
     */
    protected function formatQuranTeacherProfile($profile): array
    {
        return [
            'id' => $profile->id,
            'teacher_code' => $profile->teacher_code,
            'teaching_experience_years' => $profile->teaching_experience_years,
            'educational_qualification' => $profile->educational_qualification,
            'session_price_individual' => (float) $profile->session_price_individual,
            'session_price_group' => (float) $profile->session_price_group,
            'rating' => (float) $profile->rating,
            'total_reviews' => $profile->total_reviews,
            'total_students' => $profile->total_students,
            'total_sessions' => $profile->total_sessions,
            'bio' => $profile->bio_arabic,
        ];
    }

    /**
     * Format Academic teacher profile
     */
    protected function formatAcademicTeacherProfile($profile): array
    {
        return [
            'id' => $profile->id,
            'teacher_code' => $profile->teacher_code,
            'education_level' => $profile->education_level,
            'teaching_experience_years' => $profile->teaching_experience_years,
            'session_price_individual' => (float) $profile->session_price_individual,
            'rating' => (float) $profile->rating,
            'total_reviews' => $profile->total_reviews,
            'subject_ids' => $profile->subject_ids ?? [],
            'grade_level_ids' => $profile->grade_level_ids ?? [],
            'bio' => $profile->bio_arabic,
        ];
    }

    /**
     * Format Supervisor profile
     */
    protected function formatSupervisorProfile($profile): array
    {
        return [
            'id' => $profile->id,
            'supervisor_code' => $profile->supervisor_code,
            'can_manage_teachers' => $profile->can_manage_teachers ?? false,
            'assigned_teachers_count' => $profile->getAllAssignedTeacherIds()->count(),
            'responsibilities' => $profile->getResponsibilityCountByType(),
        ];
    }

    /**
     * Format Admin profile (for admin and super_admin)
     */
    protected function formatAdminProfile(): array
    {
        return [
            'id' => $this->id,
            'admin_code' => $this->admin_code,
            'can_manage_all' => $this->isSuperAdmin(),
        ];
    }
}
