<?php

namespace App\Http\Resources\Api\V1\Student;

use App\Models\Student;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Student List Resource (Minimal)
 *
 * Minimal student data for listings and references.
 * Optimized for performance with minimal data.
 *
 * @mixin Student
 */
class StudentListResource extends JsonResource
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
            'student_code' => $this->student_code,
            'name' => $this->user?->name,
            'avatar_url' => $this->getAvatarUrl(),
            'grade_level' => $this->gradeLevel?->name,
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

        return 'https://ui-avatars.com/api/?name=' . urlencode($this->user?->name ?? 'Student') . '&background=10b981&color=fff';
    }
}
