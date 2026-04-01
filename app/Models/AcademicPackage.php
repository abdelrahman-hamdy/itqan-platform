<?php

namespace App\Models;

use App\Models\Traits\HasSalePrices;
use App\Models\Traits\ScopedToAcademy;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class AcademicPackage extends Model
{
    use HasFactory, HasSalePrices, ScopedToAcademy, SoftDeletes;

    protected $fillable = [
        'academy_id',
        'name',
        'description',
        'sessions_per_month',
        'session_duration_minutes',
        'monthly_price',
        'sale_monthly_price',
        'quarterly_price',
        'sale_quarterly_price',
        'yearly_price',
        'sale_yearly_price',
        'currency',
        'features',
        'is_active',
        'sort_order',
    ];

    protected $casts = [
        'sessions_per_month' => 'integer',
        'session_duration_minutes' => 'integer',
        'monthly_price' => 'decimal:2',
        'sale_monthly_price' => 'decimal:2',
        'quarterly_price' => 'decimal:2',
        'sale_quarterly_price' => 'decimal:2',
        'yearly_price' => 'decimal:2',
        'sale_yearly_price' => 'decimal:2',
        'features' => 'array',
        'is_active' => 'boolean',
        'sort_order' => 'integer',
    ];

    protected $attributes = [
        'currency' => 'SAR',
        'session_duration_minutes' => 60,
        'sessions_per_month' => 8,
        'is_active' => true,
        'sort_order' => 0,
    ];

    // Relationships
    public function academy(): BelongsTo
    {
        return $this->belongsTo(Academy::class);
    }

    protected static function booted(): void
    {
        static::deleting(function (self $package) {
            if ($package->subscriptions()->exists()) {
                throw new \Exception(__('subscriptions.errors.cannot_delete_package_with_subscriptions'));
            }
        });
    }

    public function subscriptions(): HasMany
    {
        return $this->hasMany(AcademicSubscription::class, 'academic_package_id');
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

    /**
     * Get the display name for this package
     */
    public function getDisplayName(): string
    {
        return $this->name ?? __('packages.unnamed_package');
    }
}
