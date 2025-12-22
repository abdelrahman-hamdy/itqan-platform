<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Assignment extends Model
{
    use HasFactory;

    protected $fillable = [
        'academy_id',
        'course_id',
        'teacher_id',
        'title',
        'description',
        'instructions',
        'type',
        'max_score',
        'due_date',
        'is_active',
        'allow_late_submission',
        'late_penalty_percentage',
    ];

    protected $casts = [
        'due_date' => 'datetime',
        'max_score' => 'decimal:2',
        'is_active' => 'boolean',
        'allow_late_submission' => 'boolean',
        'late_penalty_percentage' => 'decimal:2',
    ];

    /**
     * Academy relationship
     */
    public function academy(): BelongsTo
    {
        return $this->belongsTo(Academy::class);
    }

    /**
     * Course relationship
     */
    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }

    /**
     * Teacher relationship
     */
    public function teacher(): BelongsTo
    {
        return $this->belongsTo(User::class, 'teacher_id');
    }

    /**
     * Scope for active assignments
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope for assignments due today
     */
    public function scopeDueToday($query)
    {
        return $query->whereDate('due_date', today());
    }

    /**
     * Scope for overdue assignments
     */
    public function scopeOverdue($query)
    {
        return $query->where('due_date', '<', now());
    }

    /**
     * Check if assignment is overdue
     */
    public function getIsOverdueAttribute()
    {
        return $this->due_date && $this->due_date < now();
    }

    /**
     * Get submission count
     */
    public function getSubmissionCountAttribute()
    {
        return $this->submissions()->count();
    }

    /**
     * Get graded submission count
     */
    public function getGradedSubmissionCountAttribute()
    {
        return $this->submissions()->whereNotNull('score')->count();
    }
}
