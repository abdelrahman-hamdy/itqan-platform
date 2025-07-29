<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class StudentProfile extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'student_code',
        'grade_level_id',
        'birth_date',
        'gender',
        'nationality',
        'parent_id',
        'address',
        'emergency_contact',
        'enrollment_date',
        'graduation_date',
        'academic_status',
        'notes',
    ];

    protected $casts = [
        'birth_date' => 'date',
        'enrollment_date' => 'date',
        'graduation_date' => 'date',
    ];

    /**
     * Boot method to auto-generate student code
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            if (empty($model->student_code)) {
                $academyId = $model->user->academy_id ?? 1;
                $count = static::whereHas('user', function ($query) use ($academyId) {
                    $query->where('academy_id', $academyId);
                })->count() + 1;
                $model->student_code = 'STU-' . str_pad($academyId, 2, '0', STR_PAD_LEFT) . '-' . str_pad($count, 4, '0', STR_PAD_LEFT);
            }
        });
    }

    /**
     * Relationships
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function gradeLevel(): BelongsTo
    {
        return $this->belongsTo(GradeLevel::class);
    }

    public function parents(): BelongsToMany
    {
        return $this->belongsToMany(ParentProfile::class, 'parent_student_relationships', 'student_id', 'parent_id')
            ->withPivot('relationship_type', 'is_primary_contact', 'can_view_grades', 'can_receive_notifications')
            ->withTimestamps();
    }

    /**
     * Helper methods
     */
    public function getDisplayName(): string
    {
        return $this->user->name . ' (' . $this->student_code . ')';
    }

    public function getAge(): ?int
    {
        return $this->birth_date ? $this->birth_date->age : null;
    }

    public function isActive(): bool
    {
        return $this->academic_status === 'enrolled';
    }

    /**
     * Scopes
     */
    public function scopeActive($query)
    {
        return $query->where('academic_status', 'enrolled');
    }

    public function scopeForAcademy($query, int $academyId)
    {
        return $query->whereHas('user', function ($q) use ($academyId) {
            $q->where('academy_id', $academyId);
        });
    }
}
