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
            'id' => $this->resource->id,
            'teacher_code' => $this->resource->teacher_code,
            'type' => $isQuranTeacher ? 'quran' : 'academic',

            // Status (single source of truth: User.active_status)
            'is_active' => $this->resource->isActive(),

            // User information
            'user' => $this->whenLoaded('user', [
                'id' => $this->resource->user?->id,
                'email' => $this->resource->user?->email,
                'first_name' => $this->resource->user?->first_name,
                'last_name' => $this->resource->user?->last_name,
                'full_name' => $this->resource->user?->name,
                'phone' => $this->resource->user?->phone,
                'avatar_url' => $this->getAvatarUrl(),
            ]),

            // Profile
            'bio_arabic' => $this->resource->bio_arabic,
            'bio_english' => $this->resource->bio_english,

            // Qualifications
            'educational_qualification' => $this->when(
                $isQuranTeacher,
                fn () => [
                    'value' => $this->resource->educational_qualification?->value,
                    'label' => $this->resource->educational_qualification?->label(),
                ]
            ),
            'education_level' => $this->when(! $isQuranTeacher, $this->resource->education_level),
            'teaching_experience_years' => $this->resource->teaching_experience_years,

            // Pricing
            'pricing' => [
                'session_price_individual' => (float) $this->resource->session_price_individual,
                'session_price_group' => $isQuranTeacher ? (float) $this->resource->session_price_group : null,
                'currency' => $this->resource->academy?->currency?->value ?? 'SAR',
            ],

            // Statistics
            'statistics' => [
                'rating' => $this->resource->rating ? (float) $this->resource->rating : null,
                'total_reviews' => $this->resource->total_reviews ?? 0,
                'total_students' => $this->resource->total_students ?? 0,
                'total_sessions' => $this->resource->total_sessions ?? 0,
            ],

            // Academic-specific
            'subjects' => $this->when(
                ! $isQuranTeacher && $this->relationLoaded('academicSubjects'),
                fn () => $this->resource->academicSubjects->map(fn ($subject) => [
                    'id' => $subject->id,
                    'name' => $subject->name,
                ])
            ),
            'grade_levels' => $this->when(
                ! $isQuranTeacher && $this->relationLoaded('gradeLevels'),
                fn () => $this->resource->gradeLevels->map(fn ($level) => [
                    'id' => $level->id,
                    'name' => $level->name,
                ])
            ),

            // Academy
            'academy' => $this->whenLoaded('academy', [
                'id' => $this->resource->academy?->id,
                'name' => $this->resource->academy?->name,
                'subdomain' => $this->resource->academy?->subdomain,
            ]),

            // Timestamps
            'created_at' => $this->resource->created_at->toISOString(),
            'updated_at' => $this->resource->updated_at->toISOString(),
        ];
    }

    /**
     * Get avatar URL
     */
    protected function getAvatarUrl(): ?string
    {
        if ($this->resource->avatar) {
            if (str_starts_with($this->resource->avatar, 'http')) {
                return $this->resource->avatar;
            }

            return asset('storage/'.$this->resource->avatar);
        }

        if ($this->resource->user?->avatar) {
            if (str_starts_with($this->resource->user->avatar, 'http')) {
                return $this->resource->user->avatar;
            }

            return asset('storage/'.$this->resource->user->avatar);
        }

        return 'https://ui-avatars.com/api/?name='.urlencode($this->resource->user?->name ?? __('api.avatar.teacher')).'&background=0ea5e9&color=fff';
    }
}
