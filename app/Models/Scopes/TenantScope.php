<?php

namespace App\Models\Scopes;

use App\Services\AcademyContextService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;

/**
 * TenantScope - Automatically filters models by academy_id
 *
 * This scope should be applied to models that need tenant isolation.
 * It uses AcademyContextService to determine the current academy context.
 *
 * Usage:
 * In the model's booted() method:
 * static::addGlobalScope(new TenantScope);
 *
 * To bypass the scope:
 * Model::withoutGlobalScope(TenantScope::class)->get();
 *
 * Note: This scope is NOT applied to the User model because:
 * 1. Users need to be loaded during authentication before academy context is known
 * 2. Super admins need to access users across all academies
 * 3. User queries should explicitly filter by academy_id in controllers/services
 */
class TenantScope implements Scope
{
    /**
     * Apply the scope to a given Eloquent query builder.
     */
    public function apply(Builder $builder, Model $model): void
    {
        // Skip scoping if:
        // 1. We're in console (CLI) context without web request
        // 2. Super admin is in global view mode
        // 3. No academy context is available

        if ($this->shouldSkipScoping()) {
            return;
        }

        $academyId = AcademyContextService::getCurrentAcademyId();

        if ($academyId !== null) {
            $builder->where($model->getTable().'.academy_id', $academyId);
        }
    }

    /**
     * Determine if scoping should be skipped.
     */
    protected function shouldSkipScoping(): bool
    {
        // Skip in console context (artisan commands, queue workers, etc.)
        if (app()->runningInConsole() && ! app()->runningUnitTests()) {
            return true;
        }

        // Skip if super admin is in global view mode
        if (AcademyContextService::isSuperAdmin() && AcademyContextService::isGlobalViewMode()) {
            return true;
        }

        return false;
    }
}
