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
        'description',
        'category',
        'is_academic',
        'is_active',
    ];

    protected $casts = [
        'is_academic' => 'boolean',
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
     * Scope for academic subjects (non-Quran)
     */
    public function scopeAcademic($query)
    {
        return $query->where('is_academic', true);
    }

    /**
     * Scope for Quran subjects
     */
    public function scopeQuran($query)
    {
        return $query->where('is_academic', false);
    }
}
