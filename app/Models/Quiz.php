<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Quiz extends Model
{
    use HasFactory, HasUuids, SoftDeletes;

    protected $fillable = [
        'academy_id',
        'title',
        'description',
        'duration_minutes',
        'passing_score',
        'is_active',
        'created_by',
    ];

    protected $casts = [
        'duration_minutes' => 'integer',
        'passing_score' => 'integer',
        'is_active' => 'boolean',
    ];

    // ========================================
    // Relationships
    // ========================================

    public function academy(): BelongsTo
    {
        return $this->belongsTo(Academy::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function questions(): HasMany
    {
        return $this->hasMany(QuizQuestion::class)->orderBy('order');
    }

    public function assignments(): HasMany
    {
        return $this->hasMany(QuizAssignment::class);
    }

    public function attempts(): HasMany
    {
        return $this->hasMany(QuizAttempt::class);
    }

    // ========================================
    // Scopes
    // ========================================

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope to filter by academy (tenant isolation)
     */
    public function scopeForAcademy($query, int $academyId)
    {
        return $query->where('academy_id', $academyId);
    }

    // ========================================
    // Accessors
    // ========================================

    public function getQuestionsCountAttribute(): int
    {
        return $this->questions()->count();
    }

    public function getTotalAssignmentsAttribute(): int
    {
        return $this->assignments()->count();
    }
}
