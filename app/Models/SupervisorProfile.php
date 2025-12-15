<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Traits\ScopedToAcademy;

class SupervisorProfile extends Model
{
    use HasFactory, ScopedToAcademy;

    protected $fillable = [
        'academy_id', // Direct academy relationship
        'user_id',
        'email',
        'first_name',
        'last_name',
        'phone',
        'avatar',
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
                // Use academy_id from the model, or fallback to 1 if not set
                $academyId = $model->academy_id ?: 1;
                $prefix = 'SUP-' . str_pad($academyId, 2, '0', STR_PAD_LEFT) . '-';

                // Find the highest existing sequence number for this academy
                $maxCode = static::withoutGlobalScopes()
                    ->where('supervisor_code', 'like', $prefix . '%')
                    ->orderByRaw('CAST(SUBSTRING(supervisor_code, -4) AS UNSIGNED) DESC')
                    ->value('supervisor_code');

                if ($maxCode) {
                    // Extract the sequence number and increment
                    $sequence = (int) substr($maxCode, -4) + 1;
                } else {
                    $sequence = 1;
                }

                $model->supervisor_code = $prefix . str_pad($sequence, 4, '0', STR_PAD_LEFT);
            }
        });
    }

    /**
     * Academy relationship path for trait
     */
    protected static function getAcademyRelationshipPath(): string
    {
        return 'academy'; // SupervisorProfile -> Academy (direct relationship)
    }

    /**
     * Relationships
     */
    public function academy(): BelongsTo
    {
        return $this->belongsTo(Academy::class);
    }

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

    /**
     * Check if profile is linked to a user account
     */
    public function isLinked(): bool
    {
        return !is_null($this->user_id);
    }

    public function getDepartmentInArabicAttribute(): string
    {
        return match($this->department) {
            'quran' => 'قسم القرآن الكريم',
            'academic' => 'القسم الأكاديمي',
            'recorded_courses' => 'قسم الدورات المسجلة',
            'general' => 'الإشراف العام',
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
        return $this->department === 'general' || $this->department === $department;
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
    public function scopeUnlinked($query)
    {
        return $query->whereNull('user_id');
    }

    public function scopeLinked($query)
    {
        return $query->whereNotNull('user_id');
    }

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
              ->orWhere('department', 'general');
        });
    }
}
