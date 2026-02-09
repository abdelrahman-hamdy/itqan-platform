<?php

namespace App\Models\Traits;

use App\Enums\UserType;
use App\Models\AcademicGradeLevel;
use App\Models\AcademicSubject;
use App\Models\AcademicTeacherProfile;
use App\Models\ParentProfile;
use App\Models\QuranTeacherProfile;
use App\Models\StudentProfile;
use App\Models\SupervisorProfile;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

trait HasProfiles
{
    /**
     * Get the user's profile based on user_type
     */
    public function getProfile()
    {
        return match ($this->user_type) {
            UserType::STUDENT->value => $this->studentProfile,
            UserType::QURAN_TEACHER->value => $this->quranTeacherProfile,
            UserType::ACADEMIC_TEACHER->value => $this->academicTeacherProfile,
            UserType::PARENT->value => $this->parentProfile,
            UserType::SUPERVISOR->value => $this->supervisorProfile,
            UserType::ADMIN->value => null, // Admins use basic user info only
            default => null,
        };
    }

    /**
     * Specific profile relationship methods for easier querying
     * Note: Profiles bypass 'academy' global scope since they're linked by user_id
     * and users should always be able to access their own profile
     */
    public function quranTeacherProfile(): HasOne
    {
        return $this->hasOne(QuranTeacherProfile::class)->withoutGlobalScope('academy');
    }

    public function academicTeacherProfile(): HasOne
    {
        return $this->hasOne(AcademicTeacherProfile::class)->withoutGlobalScope('academy');
    }

    public function studentProfile(): HasOne
    {
        return $this->hasOne(StudentProfile::class)->withoutGlobalScope('academy');
    }

    /**
     * Get student profile without global scopes (for internal use)
     * This ensures students can always access their own profile regardless of academy context
     */
    public function studentProfileUnscoped(): HasOne
    {
        return $this->hasOne(StudentProfile::class)->withoutGlobalScopes();
    }

    public function parentProfile(): HasOne
    {
        return $this->hasOne(ParentProfile::class)->withoutGlobalScope('academy');
    }

    public function supervisorProfile(): HasOne
    {
        return $this->hasOne(SupervisorProfile::class)->withoutGlobalScope('academy');
    }

    /**
     * Subjects relationship for academic teachers
     */
    public function subjects(): HasMany
    {
        return $this->hasMany(AcademicSubject::class, 'teacher_id');
    }

    /**
     * Get full name attribute
     */
    public function getNameAttribute(): string
    {
        return trim($this->first_name.' '.$this->last_name) ?: 'مستخدم غير محدد';
    }

    /**
     * Check if user has completed profile
     */
    public function hasCompletedProfile(): bool
    {
        return ! is_null($this->profile_completed_at);
    }

    /**
     * Create profile based on user type
     */
    public function createProfile(): void
    {
        // Skip if user already has a profile or if user_type is admin/super_admin
        if ($this->getProfile() || in_array($this->user_type, [UserType::ADMIN->value, UserType::SUPER_ADMIN->value])) {
            return;
        }

        $profileData = [
            'user_id' => $this->id,
            'email' => $this->email,
            'first_name' => $this->first_name,
            'last_name' => $this->last_name,
            'phone' => $this->phone,
        ];

        $profileDataWithAcademy = array_merge($profileData, [
            'academy_id' => $this->academy_id,
        ]);

        switch ($this->user_type) {
            case UserType::STUDENT->value:
                // Get a random grade level from the user's academy
                $gradeLevel = AcademicGradeLevel::where('academy_id', $this->academy_id)->inRandomOrder()->first();

                StudentProfile::create(array_merge($profileData, [
                    'grade_level_id' => $gradeLevel ? $gradeLevel->id : null,
                    'birth_date' => now()->subYears(rand(8, 18)),
                    'gender' => rand(0, 1) ? 'male' : 'female',
                    'nationality' => null, // Will be set during registration
                    'parent_id' => $this->parent_id,
                    'enrollment_date' => now()->subMonths(rand(1, 12)),
                ]));
                break;

            case UserType::QURAN_TEACHER->value:
                QuranTeacherProfile::create(array_merge($profileDataWithAcademy, [
                    'educational_qualification' => 'bachelor',
                    'teaching_experience_years' => 1,
                    'approval_status' => 'pending',
                ]));
                break;

            case UserType::ACADEMIC_TEACHER->value:
                AcademicTeacherProfile::create(array_merge($profileDataWithAcademy, [
                    'education_level' => 'bachelor',
                    'teaching_experience_years' => 1,
                    'session_price_individual' => 60,
                    'approval_status' => 'pending',
                ]));
                break;

            case UserType::PARENT->value:
                ParentProfile::create(array_merge($profileDataWithAcademy, [
                    'relationship_type' => 'father',
                    'preferred_contact_method' => 'phone',
                ]));
                break;

            case UserType::SUPERVISOR->value:
                SupervisorProfile::create($profileDataWithAcademy);
                break;
        }
    }

    /**
     * Get the user's display name for chat interfaces (WireChat/Chatify)
     */
    public function getChatifyName(): string
    {
        // For students, use their student profile name
        if ($this->isStudent() && $this->studentProfile) {
            $firstName = $this->studentProfile->first_name ?? '';
            $lastName = $this->studentProfile->last_name ?? '';

            return trim($firstName.' '.$lastName) ?: $this->name;
        }

        // For Quran teachers, use their profile name
        if ($this->isQuranTeacher() && $this->quranTeacherProfile) {
            return $this->quranTeacherProfile->full_name ?? $this->name;
        }

        // For Academic teachers, use their profile name
        if ($this->isAcademicTeacher() && $this->academicTeacherProfile) {
            return $this->academicTeacherProfile->full_name ?? $this->name;
        }

        // For parents, use their parent profile name
        if ($this->isParent() && $this->parentProfile) {
            $firstName = $this->parentProfile->first_name ?? '';
            $lastName = $this->parentProfile->last_name ?? '';

            return trim($firstName.' '.$lastName) ?: $this->name;
        }

        // Default to full name or name field
        if ($this->first_name || $this->last_name) {
            return trim($this->first_name.' '.$this->last_name);
        }

        return $this->name;
    }

    /**
     * Get the user's avatar URL for chat interfaces
     */
    public function getChatifyAvatar(): ?string
    {
        // Check if user has a direct avatar
        if ($this->avatar) {
            // If it's a full URL, return as is
            if (filter_var($this->avatar, FILTER_VALIDATE_URL)) {
                return $this->avatar;
            }

            // Otherwise, assume it's a path in storage
            return asset('storage/'.$this->avatar);
        }

        // For students, check their profile avatar
        if ($this->isStudent() && $this->studentProfile && $this->studentProfile->avatar) {
            return asset('storage/'.$this->studentProfile->avatar);
        }

        // For Quran teachers, check their profile avatar
        if ($this->isQuranTeacher() && $this->quranTeacherProfile && $this->quranTeacherProfile->avatar) {
            return asset('storage/'.$this->quranTeacherProfile->avatar);
        }

        // For Academic teachers, check their profile avatar
        if ($this->isAcademicTeacher() && $this->academicTeacherProfile && $this->academicTeacherProfile->avatar) {
            return asset('storage/'.$this->academicTeacherProfile->avatar);
        }

        // For parents, check their profile avatar
        if ($this->isParent() && $this->parentProfile && $this->parentProfile->avatar) {
            return asset('storage/'.$this->parentProfile->avatar);
        }

        // Return null to use Chatify's default avatar generation
        return null;
    }

    /**
     * Get user info formatted for chat interfaces
     */
    public function getChatifyInfo(): array
    {
        return [
            'name' => $this->getChatifyName(),
            'avatar' => $this->getChatifyAvatar(),
            'role' => $this->getUserTypeLabel(),
            'academy' => $this->academy ? $this->academy->name : null,
        ];
    }
}
