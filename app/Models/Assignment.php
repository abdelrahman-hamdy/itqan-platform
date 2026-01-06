<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int|null $academy_id
 * @property int|null $course_id
 * @property int|null $teacher_id
 * @property string $title
 * @property string|null $description
 * @property string|null $instructions
 * @property string|null $type
 * @property float|null $max_score
 * @property \Carbon\Carbon|null $due_date
 * @property bool $is_active
 * @property bool $allow_late_submission
 * @property float|null $late_penalty_percentage
 * @property \Carbon\Carbon|null $created_at
 * @property \Carbon\Carbon|null $updated_at
 */
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
}
