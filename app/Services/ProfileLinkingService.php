<?php

namespace App\Services;

use App\Models\AcademicTeacherProfile;
use App\Models\ParentProfile;
use App\Models\QuranTeacherProfile;
use App\Models\StudentProfile;
use App\Models\SupervisorProfile;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Hash;
use App\Enums\SessionStatus;

class ProfileLinkingService
{
    /**
     * Register a new user and link to existing profile by email
     */
    public function registerUserWithProfile(array $userData): array
    {
        $email = $userData['email'];
        $password = $userData['password'];

        // Find existing profile by email
        $profile = $this->findProfileByEmail($email);

        if (! $profile) {
            return [
                'success' => false,
                'message' => 'لم يتم العثور على ملف شخصي مطابق لهذا البريد الإلكتروني. يرجى التواصل مع الإدارة.',
                'profile' => null,
                'user' => null,
            ];
        }

        // Check if profile is already linked
        if ($profile->isLinked()) {
            return [
                'success' => false,
                'message' => 'هذا البريد الإلكتروني مرتبط بحساب موجود بالفعل.',
                'profile' => $profile,
                'user' => null,
            ];
        }

        // Determine user type based on profile
        $userType = $this->determineUserType($profile);

        // Extract academy ID from profile or use default
        $academyId = $this->extractAcademyId($profile);

        // Create user account
        $user = User::create([
            'academy_id' => $academyId,
            'email' => $email,
            'password' => Hash::make($password),
            'first_name' => $profile->first_name,
            'last_name' => $profile->last_name,
            'phone' => $profile->phone,
            'user_type' => $userType,
            'status' => 'active',
            'active_status' => true,
            'email_verified_at' => now(), // Auto-verify since profile was created by admin
        ]);

        // Link profile to user
        $profile->update(['user_id' => $user->id]);

        return [
            'success' => true,
            'message' => 'تم إنشاء الحساب وربطه بنجاح!',
            'profile' => $profile,
            'user' => $user,
        ];
    }

    /**
     * Find profile by email across all profile types
     *
     * Note: Teacher profiles (Quran/Academic) store personal info on User model,
     * so we search through User relationship for those.
     */
    private function findProfileByEmail(string $email): ?Model
    {
        // Check StudentProfile (has email column)
        $profile = StudentProfile::where('email', $email)->first();
        if ($profile) {
            return $profile;
        }

        // Check QuranTeacherProfile (email is on User, not profile)
        $profile = QuranTeacherProfile::with('user')
            ->whereHas('user', fn($q) => $q->where('email', $email))
            ->first();
        if ($profile) {
            return $profile;
        }

        // Check AcademicTeacherProfile (email is on User, not profile)
        $profile = AcademicTeacherProfile::with('user')
            ->whereHas('user', fn($q) => $q->where('email', $email))
            ->first();
        if ($profile) {
            return $profile;
        }

        // Check ParentProfile (if table exists)
        if (class_exists(ParentProfile::class)) {
            $profile = ParentProfile::where('email', $email)->first();
            if ($profile) {
                return $profile;
            }
        }

        // Check SupervisorProfile (if table exists)
        if (class_exists(SupervisorProfile::class)) {
            $profile = SupervisorProfile::where('email', $email)->first();
            if ($profile) {
                return $profile;
            }
        }

        return null;
    }

    /**
     * Determine user type based on profile model
     */
    private function determineUserType(Model $profile): string
    {
        return match (class_basename($profile)) {
            'StudentProfile' => 'student',
            'QuranTeacherProfile' => 'quran_teacher',
            'AcademicTeacherProfile' => 'academic_teacher',
            'ParentProfile' => 'parent',
            'SupervisorProfile' => 'supervisor',
            default => 'student',
        };
    }

    /**
     * Extract academy ID from profile or use default
     */
    private function extractAcademyId(Model $profile): int
    {
        // For StudentProfile, get academy through grade level
        if ($profile instanceof StudentProfile) {
            return $profile->gradeLevel?->academy_id ?? AcademyContextService::getCurrentAcademyId() ?? AcademyContextService::getDefaultAcademy()?->id ?? 2;
        }

        // For other profiles, use current academy context or default
        return AcademyContextService::getCurrentAcademyId() ?? AcademyContextService::getDefaultAcademy()?->id ?? 2;
    }

    /**
     * Check if email has an existing profile
     */
    public function hasExistingProfile(string $email): bool
    {
        return $this->findProfileByEmail($email) !== null;
    }

    /**
     * Get profile type by email
     */
    public function getProfileTypeByEmail(string $email): ?string
    {
        $profile = $this->findProfileByEmail($email);

        if (! $profile) {
            return null;
        }

        return match (class_basename($profile)) {
            'StudentProfile' => 'طالب',
            'QuranTeacherProfile' => 'معلم قرآن',
            'AcademicTeacherProfile' => 'معلم أكاديمي',
            'ParentProfile' => 'ولي أمر',
            'SupervisorProfile' => 'مشرف',
            default => 'غير محدد',
        };
    }

    /**
     * Get unlinked profiles count for admin dashboard
     */
    public function getUnlinkedProfilesCount(): array
    {
        return [
            'students' => StudentProfile::unlinked()->count(),
            'quran_teacher_profiles' => QuranTeacherProfile::unlinked()->count(),
            'academic_teachers' => AcademicTeacherProfile::unlinked()->count(),
            'parents' => class_exists(ParentProfile::class) ? ParentProfile::unlinked()->count() : 0,
            'supervisors' => class_exists(SupervisorProfile::class) ? SupervisorProfile::unlinked()->count() : 0,
        ];
    }
}
