<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use App\Traits\ScopedToAcademy;

class ParentProfile extends Model
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
        'parent_code',
        'occupation',
        'relationship_type',
        'address',
        'secondary_phone',
        'preferred_contact_method',
        'admin_notes', // Visible to admin only
    ];

    /**
     * Get the attributes that should be cast.
     */
    protected $casts = [
        'relationship_type' => \App\Enums\RelationshipType::class,
    ];

    /**
     * Boot method to auto-generate parent code
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            if (empty($model->parent_code)) {
                // Use academy_id from the model, or fallback to 1 if not set
                $academyId = $model->academy_id ?: 1;

                // Generate unique code with timestamp to avoid race conditions
                $timestamp = now()->format('His'); // HHMMSS
                $random = rand(100, 999);
                $model->parent_code = 'PAR-' . str_pad($academyId, 2, '0', STR_PAD_LEFT) . '-' . $timestamp . $random;
            }
        });
    }

    /**
     * Academy relationship path for trait
     */
    protected static function getAcademyRelationshipPath(): string
    {
        return 'academy'; // ParentProfile -> Academy (direct relationship)
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

    public function students(): BelongsToMany
    {
        return $this->belongsToMany(StudentProfile::class, 'parent_student_relationships', 'parent_id', 'student_id')
            ->using(ParentStudentRelationship::class)
            ->withPivot('relationship_type')
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
