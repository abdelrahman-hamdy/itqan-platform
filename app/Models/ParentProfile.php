<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class ParentProfile extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'email',
        'first_name',
        'last_name',
        'phone',
        'avatar',
        'parent_code',
        'relationship_type',
        'occupation',
        'workplace',
        'national_id',
        'passport_number',
        'address',
        'secondary_phone',
        'emergency_contact_name',
        'emergency_contact_phone',
        'preferred_contact_method',
        'notes',
    ];

    /**
     * Boot method to auto-generate parent code
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            if (empty($model->parent_code)) {
                $academyId = $model->user->academy_id ?? 1;
                $count = static::whereHas('user', function ($query) use ($academyId) {
                    $query->where('academy_id', $academyId);
                })->count() + 1;
                $model->parent_code = 'PAR-' . str_pad($academyId, 2, '0', STR_PAD_LEFT) . '-' . str_pad($count, 4, '0', STR_PAD_LEFT);
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

    public function students(): BelongsToMany
    {
        return $this->belongsToMany(StudentProfile::class, 'parent_student_relationships', 'parent_id', 'student_id')
            ->withPivot('relationship_type', 'is_primary_contact', 'can_view_grades', 'can_receive_notifications')
            ->withTimestamps();
    }

    /**
     * Helper methods
     */
    public function getDisplayName(): string
    {
        return $this->user->name . ' (' . $this->parent_code . ')';
    }

    public function getFullNameAttribute(): string
    {
        return trim($this->first_name . ' ' . $this->last_name);
    }

    /**
     * Check if profile is linked to a user account
     */
    public function isLinked(): bool
    {
        return !is_null($this->user_id);
    }

    public function getRelationshipTypeInArabicAttribute(): string
    {
        return match($this->relationship_type) {
            'father' => 'الأب',
            'mother' => 'الأم',
            'guardian' => 'الوصي',
            'relative' => 'قريب',
            default => $this->relationship_type,
        };
    }

    public function getPreferredContactMethodInArabicAttribute(): string
    {
        return match($this->preferred_contact_method) {
            'phone' => 'هاتف',
            'email' => 'بريد إلكتروني',
            'sms' => 'رسالة نصية',
            default => $this->preferred_contact_method,
        };
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
}
