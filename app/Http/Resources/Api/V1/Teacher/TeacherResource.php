<?php

namespace App\Http\Resources\Api\V1\Teacher;

use App\Models\AcademicTeacherProfile;
use App\Models\QuranTeacherProfile;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Teacher Resource (Full Profile)
 *
 * Complete teacher profile data for detail pages.
 * Supports both Quran and Academic teachers.
 *
 * @mixin QuranTeacherProfile|AcademicTeacherProfile
 */
class TeacherResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $isQuranTeacher = $this->resource instanceof QuranTeacherProfile;

        return [
            'id' => $this->id,
            'teacher_code' => $this->teacher_code,
            'type' => $isQuranTeacher ? 'quran' : 'academic',

            // Status
            'is_active' => $this->is_active,
            'approval_status' => [
                'value' => $this->approval_status->value,
                'label' => $this->approval_status->label(),
            ],

            // User information
            'user' => $this->whenLoaded('user', [
                'id' => $this->user?->id,
                'email' => $this->user?->email,
                'first_name' => $this->user?->first_name,
                'last_name' => $this->user?->last_name,
                'full_name' => $this->user?->name,
                'phone' => $this->user?->phone,
                'avatar_url' => $this->getAvatarUrl(),
            ]),

            // Profile
            'bio_arabic' => $this->bio_arabic,
            'bio_english' => $this->bio_english,

            // Qualifications
            'educational_qualification' => $this->when(
                $isQuranTeacher,
                fn() => [
                    'value' => $this->educational_qualification?->value,
                    'label' => $this->educational_qualification?->label(),
                ]
            ),
            'education_level' => $this->when(!$isQuranTeacher, $this->education_level),
            'teaching_experience_years' => $this->teaching_experience_years,

            // Pricing
            'pricing' => [
                'session_price_individual' => (float) $this->session_price_individual,
                'session_price_group' => $isQuranTeacher ? (float) $this->session_price_group : null,
                'currency' => $this->academy?->currency?->value ?? 'SAR',
            ],

            // Statistics
            'statistics' => [
                'rating' => $this->rating ? (float) $this->rating : null,
                'total_reviews' => $this->total_reviews ?? 0,
                'total_students' => $this->total_students ?? 0,
                'total_sessions' => $this->total_sessions ?? 0,
            ],

            // Academic-specific
            'subjects' => $this->when(
                !$isQuranTeacher && $this->relationLoaded('academicSubjects'),
                fn() => $this->academicSubjects->map(fn($subject) => [
                    'id' => $subject->id,
                    'name' => $subject->name,
                ])
            ),
            'grade_levels' => $this->when(
                !$isQuranTeacher && $this->relationLoaded('gradeLevels'),
                fn() => $this->gradeLevels->map(fn($level) => [
                    'id' => $level->id,
                    'name' => $level->name,
                ])
            ),

            // Academy
            'academy' => $this->whenLoaded('academy', [
                'id' => $this->academy?->id,
                'name' => $this->academy?->name,
                'subdomain' => $this->academy?->subdomain,
            ]),

            // Timestamps
            'created_at' => $this->created_at->toISOString(),
            'updated_at' => $this->updated_at->toISOString(),
        ];
    }

    /**
     * Get avatar URL
     */
    protected function getAvatarUrl(): ?string
    {
        if ($this->avatar) {
            if (str_starts_with($this->avatar, 'http')) {
                return $this->avatar;
            }
            return asset('storage/' . $this->avatar);
        }

        if ($this->user?->avatar) {
            if (str_starts_with($this->user->avatar, 'http')) {
                return $this->user->avatar;
            }
            return asset('storage/' . $this->user->avatar);
        }

        return 'https://ui-avatars.com/api/?name=' . urlencode($this->user?->name ?? 'Teacher') . '&background=0ea5e9&color=fff';
    }
}
