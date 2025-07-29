<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SupervisorProfile extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'supervisor_code',
        'department',
        'supervision_level',
        'assigned_teachers',
        'monitoring_permissions',
        'reports_access_level',
        'hired_date',
        'contract_end_date',
        'salary',
        'performance_rating',
        'notes',
    ];

    protected $casts = [
        'assigned_teachers' => 'array',
        'monitoring_permissions' => 'array',
        'hired_date' => 'date',
        'contract_end_date' => 'date',
        'salary' => 'decimal:2',
        'performance_rating' => 'decimal:2',
    ];

    /**
     * Boot method to auto-generate supervisor code
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            if (empty($model->supervisor_code)) {
                $academyId = $model->user->academy_id ?? 1;
                $count = static::whereHas('user', function ($query) use ($academyId) {
                    $query->where('academy_id', $academyId);
                })->count() + 1;
                $model->supervisor_code = 'SUP-' . str_pad($academyId, 2, '0', STR_PAD_LEFT) . '-' . str_pad($count, 4, '0', STR_PAD_LEFT);
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

    /**
     * Helper methods
     */
    public function getDisplayName(): string
    {
        return $this->user->name . ' (' . $this->supervisor_code . ')';
    }

    public function getDepartmentInArabicAttribute(): string
    {
        return match($this->department) {
            'quran' => 'قسم القرآن الكريم',
            'academic' => 'القسم الأكاديمي',
            'both' => 'جميع الأقسام',
            default => $this->department,
        };
    }

    public function getSupervisionLevelInArabicAttribute(): string
    {
        return match($this->supervision_level) {
            'junior' => 'مشرف مبتدئ',
            'senior' => 'مشرف أول',
            'head' => 'رئيس مشرفين',
            default => $this->supervision_level,
        };
    }

    public function getReportsAccessLevelInArabicAttribute(): string
    {
        return match($this->reports_access_level) {
            'basic' => 'أساسي',
            'advanced' => 'متقدم',
            'full' => 'كامل',
            default => $this->reports_access_level,
        };
    }

    /**
     * Check if supervisor can access specific department
     */
    public function canAccessDepartment(string $department): bool
    {
        return $this->department === 'both' || $this->department === $department;
    }

    /**
     * Check if supervisor can monitor specific teacher
     */
    public function canMonitorTeacher(int $teacherId): bool
    {
        return in_array($teacherId, $this->assigned_teachers ?? []);
    }

    /**
     * Scopes
     */
    public function scopeForAcademy($query, int $academyId)
    {
        return $query->whereHas('user', function ($q) use ($academyId) {
            $q->where('academy_id', $academyId);
        });
    }

    public function scopeForDepartment($query, string $department)
    {
        return $query->where(function ($q) use ($department) {
            $q->where('department', $department)
              ->orWhere('department', 'both');
        });
    }
}
