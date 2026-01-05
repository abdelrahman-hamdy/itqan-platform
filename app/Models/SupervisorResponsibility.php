<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class SupervisorResponsibility extends Model
{
    protected $fillable = [
        'supervisor_profile_id',
        'responsable_type',
        'responsable_id',
    ];

    /**
     * Get the supervisor profile that owns this responsibility.
     */
    public function supervisorProfile(): BelongsTo
    {
        return $this->belongsTo(SupervisorProfile::class);
    }

    /**
     * Get the responsible resource (circle, lesson, or course).
     */
    public function responsable(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * Get the type label in Arabic.
     */
    public function getTypeLabelAttribute(): string
    {
        if ($this->responsable_type === User::class) {
            // Determine if it's a Quran or Academic teacher
            $userType = $this->responsable?->user_type;
            return match ($userType) {
                'quran_teacher' => 'معلم قرآن',
                'academic_teacher' => 'معلم أكاديمي',
                default => 'معلم',
            };
        }

        return match ($this->responsable_type) {
            InteractiveCourse::class => 'دورة تفاعلية',
            default => 'غير معروف',
        };
    }

    /**
     * Get a short type key for grouping.
     */
    public function getTypeKeyAttribute(): string
    {
        if ($this->responsable_type === User::class) {
            $userType = $this->responsable?->user_type;
            return match ($userType) {
                'quran_teacher' => 'quran_teachers',
                'academic_teacher' => 'academic_teachers',
                default => 'teachers',
            };
        }

        return match ($this->responsable_type) {
            InteractiveCourse::class => 'interactive_courses',
            default => 'unknown',
        };
    }
}
