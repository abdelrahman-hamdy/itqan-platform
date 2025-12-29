<?php

namespace App\Models\Traits;

use App\Models\Academy;
use Filament\Panel;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

trait HasTenantContext
{
    /**
     * Academy relationship
     */
    public function academy(): BelongsTo
    {
        return $this->belongsTo(Academy::class);
    }

    /**
     * Scope to filter by academy
     */
    public function scopeForAcademy($query, int $academyId): Builder
    {
        return $query->where('academy_id', $academyId);
    }

    /**
     * Filament Tenancy Interface Implementation
     */
    public function getTenants(Panel $panel): Collection
    {
        // Only apply tenancy to panels that have tenancy configured
        // Admin panel should NOT use tenancy at all
        if (! in_array($panel->getId(), ['academy', 'teacher', 'supervisor', 'academic-teacher'])) {
            return Academy::where('id', -1)->get(); // Empty collection for non-tenant panels
        }

        // For tenant-enabled panels:
        // Super admins can access all academies
        if ($this->isSuperAdmin()) {
            return Academy::all();
        }

        // Regular users can only access their assigned academy
        if ($this->academy) {
            return Academy::where('id', $this->academy_id)->get();
        }

        return Academy::where('id', -1)->get(); // Empty eloquent collection
    }

    public function canAccessTenant(Model $tenant): bool
    {
        // Super admins can access any academy
        if ($this->isSuperAdmin()) {
            return true;
        }

        // Regular users can only access their assigned academy
        return $this->academy_id === $tenant->id;
    }
}
