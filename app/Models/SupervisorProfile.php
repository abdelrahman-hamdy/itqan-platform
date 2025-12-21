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
        'assigned_teachers',
        'hired_date',
        'salary',
        'performance_rating',
        'notes',
    ];

    protected $casts = [
        'assigned_teachers' => 'array',
        'hired_date' => 'date',
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
