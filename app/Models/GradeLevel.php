<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class GradeLevel extends Model
{
    use HasFactory;

    protected $fillable = [
        'academy_id',
        'name',
        'name_en',
        'description',
        'level',
        'min_age',
        'max_age',
        'is_active',
    ];

    protected $casts = [
        'level' => 'integer',
        'min_age' => 'integer',
        'max_age' => 'integer',
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
     * Students in this grade level
     */
    public function students(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'student_grade_levels')
                    ->where('role', 'student');
    }

    /**
     * Teachers who teach this grade level
     */
    public function teachers(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'teacher_grade_levels')
                    ->where('role', 'teacher');
    }

    /**
     * Subjects available for this grade level
     */
    public function subjects(): BelongsToMany
    {
        return $this->belongsToMany(Subject::class, 'subject_grade_levels');
    }

    /**
     * Courses for this grade level
     */
    public function courses(): HasMany
    {
        return $this->hasMany(Course::class);
    }

    /**
     * Scope for active grade levels
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope ordered by level
     */
    public function scopeOrdered($query)
    {
        return $query->orderBy('level');
    }
}
