<?php

namespace App\Http\Resources\Api\V1\Student;

use App\Models\StudentProfile;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Student Resource (Full Profile)
 *
 * Complete student profile data for detail pages.
 *
 * @mixin StudentProfile
 */
class StudentResource extends JsonResource
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

            // Personal information
            'birth_date' => $this->resource->birth_date?->format('Y-m-d'),
            'age' => $this->resource->birth_date?->age,
            'gender' => $this->resource->gender,
            'nationality' => $this->resource->nationality,

            // Academic information
            'grade_level' => $this->whenLoaded('gradeLevel', [
                'id' => $this->resource->gradeLevel?->id,
                'name' => $this->resource->gradeLevel?->name,
            ]),

            // Parent information
            'parent' => $this->whenLoaded('parentProfile', [
                'id' => $this->resource->parentProfile?->id,
                'name' => $this->resource->parentProfile?->user?->name,
                'email' => $this->resource->parentProfile?->user?->email,
                'phone' => $this->resource->parentProfile?->user?->phone,
            ]),

            // Enrollment
            'enrollment_date' => $this->resource->enrollment_date?->format('Y-m-d'),

            // Academy
            'academy' => $this->whenLoaded('academy', [
                'id' => $this->resource->academy?->id,
                'name' => $this->resource->academy?->name,
                'subdomain' => $this->resource->subdomain,
            ]),

            // Subscriptions
            'active_subscriptions_count' => $this->when(
                $this->resource->relationLoaded('quranSubscriptions') || $this->resource->relationLoaded('academicSubscriptions'),
                fn () => $this->getActiveSubscriptionsCount()
            ),

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

        return config('services.ui_avatars.base_url', 'https://ui-avatars.com/api/').'?name='.urlencode($this->resource->user?->name ?? __('api.avatar.student')).'&background=10b981&color=fff';
    }

    /**
     * Get count of active subscriptions
     */
    protected function getActiveSubscriptionsCount(): int
    {
        $count = 0;

        if ($this->resource->relationLoaded('quranSubscriptions')) {
            $count += $this->resource->quranSubscriptions->where('status', 'active')->count();
        }

        if ($this->resource->relationLoaded('academicSubscriptions')) {
            $count += $this->resource->academicSubscriptions->where('status', 'active')->count();
        }

        return $count;
    }
}
