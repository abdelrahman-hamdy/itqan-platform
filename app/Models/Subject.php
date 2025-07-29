<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Subject extends Model
{
    use HasFactory;

    protected $fillable = [
        'academy_id',
        'name',
        'name_en',
        'subject_code',
        'description',
        'prerequisites',
        'hours_per_week',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'hours_per_week' => 'integer',
    ];

    /**
     * Academy relationship
     */
    public function academy(): BelongsTo
    {
        return $this->belongsTo(Academy::class);
    }

    /**
     * Courses in this subject
     */
    public function courses(): HasMany
    {
        return $this->hasMany(Course::class);
    }

    /**
     * Teachers who teach this subject
     */
    public function teachers(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'teacher_subjects')
                    ->wherePivot('role', 'teacher');
    }

    /**
     * Grade levels for this subject
     */
    public function gradeLevels(): BelongsToMany
    {
        return $this->belongsToMany(GradeLevel::class, 'subject_grade_levels');
    }

    /**
     * Scope for active subjects
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope for subjects by difficulty level
     */
    public function scopeByDifficulty($query, $level)
    {
        return $query->where('difficulty_level', $level);
    }

    /**
     * Scope for subjects by academy
     */
    public function scopeForAcademy($query, $academyId)
    {
        return $query->where('academy_id', $academyId);
    }
}
