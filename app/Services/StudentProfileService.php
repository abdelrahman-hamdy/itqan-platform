<?php

namespace App\Services;

use App\Models\AcademicGradeLevel;
use App\Models\StudentProfile;
use App\Models\User;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use App\Enums\SessionStatus;

/**
 * Service for managing student profiles.
 *
 * Extracted from StudentProfileController to reduce controller size.
 * Handles profile creation, updates, and retrieval.
 */
class StudentProfileService
{
    /**
     * Get or create a student profile for a user.
     *
     * This handles cases where student profile doesn't exist or was orphaned
     * (e.g., when grade levels are deleted).
     *
     * @param  User  $user  The student user
     * @return StudentProfile|null The student profile or null if creation failed
     */
    public function getOrCreateProfile(User $user): ?StudentProfile
    {
        $studentProfile = $user->studentProfileUnscoped;

        if ($studentProfile) {
            return $studentProfile;
        }

        return $this->createBasicProfile($user);
    }

    /**
     * Create a basic student profile for users who don't have one.
     *
     * @param  User  $user  The student user
     * @return StudentProfile|null The created profile or null if creation failed
     */
    public function createBasicProfile(User $user): ?StudentProfile
    {
        try {
            // Check if a profile already exists but might be orphaned
            $existingProfile = StudentProfile::withoutGlobalScopes()
                ->where('user_id', $user->id)
                ->first();

            if ($existingProfile) {
                return $existingProfile;
            }

            // Generate a unique student code
            $studentCode = $this->generateUniqueStudentCode($user);

            // Find the default grade level for the user's academy
            $defaultGradeLevel = AcademicGradeLevel::where('academy_id', $user->academy_id)
                ->where('is_active', true)
                ->orderBy('name')
                ->first();

            // Create a basic student profile
            $studentProfile = StudentProfile::create([
                'user_id' => $user->id,
                'email' => $user->email,
                'first_name' => $user->first_name ?? 'طالب',
                'last_name' => $user->last_name ?? 'جديد',
                'student_code' => $studentCode,
                'grade_level_id' => $defaultGradeLevel?->id,
                'enrollment_date' => now(),
                'notes' => 'تم إنشاء الملف الشخصي تلقائياً بعد حل مشكلة البيانات المفقودة',
            ]);

            Log::info('Created basic student profile', [
                'user_id' => $user->id,
                'profile_id' => $studentProfile->id,
            ]);

            return $studentProfile;
        } catch (\Exception $e) {
            Log::error('Failed to create basic student profile', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }

    /**
     * Update a student profile with validated data.
     *
     * @param  StudentProfile  $profile  The profile to update
     * @param  array  $data  Validated data
     * @param  \Illuminate\Http\UploadedFile|null  $avatarFile  Optional avatar file
     * @return StudentProfile The updated profile
     */
    public function updateProfile(StudentProfile $profile, array $data, $avatarFile = null): StudentProfile
    {
        // Handle avatar upload
        if ($avatarFile) {
            // Delete old avatar if exists
            if ($profile->avatar) {
                Storage::disk('public')->delete($profile->avatar);
            }

            // Store new avatar
            $data['avatar'] = $avatarFile->store('avatars', 'public');
        }

        $profile->update($data);

        return $profile->fresh();
    }

    /**
     * Generate a unique student code.
     *
     * @param  User  $user  The student user
     * @return string Unique student code
     */
    protected function generateUniqueStudentCode(User $user): string
    {
        $studentCode = 'STU' . str_pad($user->id, 6, '0', STR_PAD_LEFT);

        // Check for existing student code and make it unique
        $counter = 1;
        $originalCode = $studentCode;

        while (StudentProfile::where('student_code', $studentCode)->exists()) {
            $studentCode = $originalCode . '-' . $counter;
            $counter++;
        }

        return $studentCode;
    }

    /**
     * Get grade levels for a student's academy.
     *
     * @param  User  $user  The student user
     * @return \Illuminate\Database\Eloquent\Collection Grade levels
     */
    public function getGradeLevels(User $user)
    {
        return AcademicGradeLevel::where('academy_id', $user->academy_id)
            ->active()
            ->ordered()
            ->get();
    }
}
