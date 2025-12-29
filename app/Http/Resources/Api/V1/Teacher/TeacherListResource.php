<?php

namespace App\Http\Resources\Api\V1\Teacher;

use App\Models\AcademicTeacherProfile;
use App\Models\QuranTeacherProfile;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Teacher List Resource (Minimal)
 *
 * Minimal teacher data for listings and references.
 * Optimized for performance with minimal data.
 *
 * @mixin QuranTeacherProfile|AcademicTeacherProfile
 */
class TeacherListResource extends JsonResource
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
            'name' => $this->user?->name,
            'avatar_url' => $this->getAvatarUrl(),
            'rating' => $this->rating ? (float) $this->rating : null,
            'is_active' => $this->is_active,
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
