<?php

namespace App\Models;

use App\Models\Traits\ScopedToAcademy;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class QuranPackage extends Model
{
    use HasFactory, SoftDeletes, ScopedToAcademy;

    protected $fillable = [
        'academy_id',
        'name_ar',
        'name_en',
        'description_ar',
        'description_en',
        'sessions_per_month',
        'session_duration_minutes',
        'monthly_price',
        'quarterly_price',
        'yearly_price',
        'currency',
        'features',
        'is_active',
        'sort_order',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'sessions_per_month' => 'integer',
        'session_duration_minutes' => 'integer',
        'monthly_price' => 'decimal:2',
        'quarterly_price' => 'decimal:2',
        'yearly_price' => 'decimal:2',
        'features' => 'array',
        'is_active' => 'boolean',
        'sort_order' => 'integer',
    ];

    // Relationships
    public function academy(): BelongsTo
    {
        return $this->belongsTo(Academy::class);
    }

    public function subscriptions(): HasMany
    {
        return $this->hasMany(QuranSubscription::class, 'package_id');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeForAcademy($query, $academyId)
    {
        return $query->where('academy_id', $academyId);
    }

    // Helper methods
    public function getPriceForBillingCycle(string $billingCycle): ?float
    {
        return match($billingCycle) {
            'monthly' => $this->monthly_price,
            'quarterly' => $this->quarterly_price,
            'yearly' => $this->yearly_price,
            default => null,
        };
    }

    public function getDisplayName(): string
    {
        return app()->getLocale() === 'ar'
            ? ($this->name_ar ?? '')
            : ($this->name_en ?? '');
    }

    public function getDescription(): string
    {
        return app()->getLocale() === 'ar'
            ? ($this->description_ar ?? '')
            : ($this->description_en ?? '');
    }

    public function getFormattedCurrency(): string
    {
        return 'SAR';
    }

    public function getDisplayCurrency(): string
    {
        return 'ريال';
    }
} 