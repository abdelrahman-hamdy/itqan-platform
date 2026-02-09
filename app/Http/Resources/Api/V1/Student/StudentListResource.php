<?php

namespace App\Http\Resources\Api\V1\Student;

use App\Models\StudentProfile;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Student List Resource (Minimal)
 *
 * Minimal student data for listings and references.
 * Optimized for performance with minimal data.
 *
 * @mixin StudentProfile
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
            'id' => $this->resource->id,
            'student_code' => $this->resource->student_code,
            'name' => $this->resource->user?->name,
            'avatar_url' => $this->getAvatarUrl(),
            'grade_level' => $this->resource->gradeLevel?->name,
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

        return config('services.ui_avatars.base_url', 'https://ui-avatars.com/api/').'?name='.urlencode($this->resource->user?->name ?? __('api.avatar.student')).'&background=10b981&color=fff';
    }
}
