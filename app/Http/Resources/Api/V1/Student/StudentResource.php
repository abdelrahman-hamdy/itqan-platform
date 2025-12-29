<?php

namespace App\Http\Resources\Api\V1\Student;

use App\Models\Student;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Student Resource (Full Profile)
 *
 * Complete student profile data for detail pages.
 *
 * @mixin Student
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
            'id' => $this->id,
            'student_code' => $this->student_code,

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

            // Personal information
            'birth_date' => $this->birth_date?->format('Y-m-d'),
            'age' => $this->birth_date?->age,
            'gender' => $this->gender,
            'nationality' => $this->nationality,

            // Academic information
            'grade_level' => $this->whenLoaded('gradeLevel', [
                'id' => $this->gradeLevel?->id,
                'name' => $this->gradeLevel?->name,
            ]),

            // Parent information
            'parent' => $this->whenLoaded('parentProfile', [
                'id' => $this->parentProfile?->id,
                'name' => $this->parentProfile?->user?->name,
                'email' => $this->parentProfile?->user?->email,
                'phone' => $this->parentProfile?->user?->phone,
            ]),

            // Enrollment
            'enrollment_date' => $this->enrollment_date?->format('Y-m-d'),

            // Academy
            'academy' => $this->whenLoaded('academy', [
                'id' => $this->academy?->id,
                'name' => $this->academy?->name,
                'subdomain' => $this->academy?->subdomain,
            ]),

            // Subscriptions
            'active_subscriptions_count' => $this->when(
                $this->relationLoaded('quranSubscriptions') || $this->relationLoaded('academicSubscriptions'),
                fn() => $this->getActiveSubscriptionsCount()
            ),

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

        return 'https://ui-avatars.com/api/?name=' . urlencode($this->user?->name ?? 'Student') . '&background=10b981&color=fff';
    }

    /**
     * Get count of active subscriptions
     */
    protected function getActiveSubscriptionsCount(): int
    {
        $count = 0;

        if ($this->relationLoaded('quranSubscriptions')) {
            $count += $this->quranSubscriptions->where('status', 'active')->count();
        }

        if ($this->relationLoaded('academicSubscriptions')) {
            $count += $this->academicSubscriptions->where('status', 'active')->count();
        }

        return $count;
    }
}
