<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PortfolioItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'project_name',
        'project_description',
        'service_category_id',
        'project_image',
        'project_features',
        'is_active',
        'sort_order',
    ];

    protected $casts = [
        'project_features' => 'array',
        'is_active' => 'boolean',
        'sort_order' => 'integer',
    ];

    /**
     * Get the service category for this portfolio item.
     */
    public function serviceCategory(): BelongsTo
    {
        return $this->belongsTo(BusinessServiceCategory::class, 'service_category_id');
    }

    /**
     * Scope to get only active portfolio items.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope to order by sort order.
     */
    public function scopeOrdered($query)
    {
        return $query->orderBy('sort_order', 'asc');
    }

    /**
     * Get the project image URL.
     */
    public function getImageUrlAttribute(): string
    {
        if ($this->project_image) {
            return asset('storage/'.$this->project_image);
        }

        return asset('images/portfolio-placeholder.jpg');
    }
}
