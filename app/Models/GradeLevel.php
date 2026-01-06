<?php

namespace App\Models;

use App\Models\Traits\ScopedToAcademy;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class GradeLevel extends Model
{
    use HasFactory, ScopedToAcademy;

    protected $fillable = [
        'academy_id',
        'name',
        'name_en',
        'description',
        'level',
        'is_active',
    ];

    protected $casts = [
        'level' => 'integer',
        'is_active' => 'boolean',
    ];

    /**
     * Academy relationship
     */
    public function academy(): BelongsTo
    {
        return $this->belongsTo(Academy::class);
    }

    /**
     * Students in this grade level (via student profiles)
     */
    public function studentProfiles(): HasMany
    {
        return $this->hasMany(StudentProfile::class);
    }

    /**
     * Interactive courses for this grade level
     */
    public function interactiveCourses(): HasMany
    {
        return $this->hasMany(InteractiveCourse::class);
    }

    /**
     * Recorded courses for this grade level
     */
    public function recordedCourses(): HasMany
    {
        return $this->hasMany(RecordedCourse::class);
    }

    /**
     * Scope for active grade levels
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope for grade levels by academy
     */
    public function scopeForAcademy($query, $academyId)
    {
        return $query->where('academy_id', $academyId);
    }

    /**
     * Scope ordered by level
     */
    public function scopeOrdered($query)
    {
        return $query->orderBy('level');
    }

    /**
     * Get display name with level
     */
    public function getDisplayNameAttribute(): string
    {
        return $this->name.' (المستوى '.$this->level.')';
    }
}
