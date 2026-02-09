<?php

namespace App\Filament\Concerns;

use App\Enums\UserType;
use App\Services\AcademyContextService;
use Illuminate\Support\Facades\Log;

/**
 * Trait for resources that allow super_admin cross-academy access.
 * Provides audit logging for security tracking.
 */
trait HasCrossAcademyAccess
{
    /**
     * Get the academy_id field path on the model.
     * Override in resources where academy is accessed differently.
     */
    protected static function getAcademyIdField(): string
    {
        return 'academy_id';
    }

    /**
     * Log cross-academy access when super_admin views/edits records from other academies.
     */
    protected static function logCrossAcademyAccess($record, string $action): void
    {
        $user = auth()->user();

        if (! $user || ! $user->hasRole(UserType::SUPER_ADMIN->value)) {
            return;
        }

        $currentAcademyId = AcademyContextService::getCurrentAcademyId();
        $recordAcademyId = static::getRecordAcademyId($record);

        // Only log if accessing a record from a different academy than the current context
        if ($currentAcademyId !== null && $recordAcademyId !== null && $currentAcademyId !== $recordAcademyId) {
            Log::channel('audit')->info('Cross-academy access', [
                'action' => $action,
                'resource' => static::class,
                'record_id' => $record->getKey(),
                'record_type' => get_class($record),
                'record_academy_id' => $recordAcademyId,
                'current_academy_context' => $currentAcademyId,
                'user_id' => $user->id,
                'user_email' => $user->email,
                'ip_address' => request()->ip(),
                'user_agent' => request()->userAgent(),
                'timestamp' => now()->toIso8601String(),
            ]);
        }
    }

    /**
     * Get the academy ID from the record.
     * Override for models where academy is accessed through a relationship.
     */
    protected static function getRecordAcademyId($record): ?int
    {
        $field = static::getAcademyIdField();

        // Handle dot notation for nested relationships (e.g., 'user.academy_id')
        if (str_contains($field, '.')) {
            $parts = explode('.', $field);
            $value = $record;
            foreach ($parts as $part) {
                if (is_object($value) && isset($value->$part)) {
                    $value = $value->$part;
                } else {
                    return null;
                }
            }

            return $value;
        }

        return $record->$field ?? null;
    }

    /**
     * Super admin can view any record regardless of academy context.
     * Logs cross-academy access for audit trail.
     */
    public static function canView($record): bool
    {
        $user = auth()->user();

        if ($user?->hasRole(UserType::SUPER_ADMIN->value)) {
            static::logCrossAcademyAccess($record, 'view');

            return true;
        }

        return parent::canView($record);
    }

    /**
     * Super admin can edit any record regardless of academy context.
     * Logs cross-academy access for audit trail.
     */
    public static function canEdit($record): bool
    {
        $user = auth()->user();

        if ($user?->hasRole(UserType::SUPER_ADMIN->value)) {
            static::logCrossAcademyAccess($record, 'edit');

            return true;
        }

        return parent::canEdit($record);
    }

    /**
     * Super admin can delete any record.
     * Logs cross-academy access for audit trail.
     */
    public static function canDelete($record): bool
    {
        $user = auth()->user();

        if ($user?->hasRole(UserType::SUPER_ADMIN->value)) {
            static::logCrossAcademyAccess($record, 'delete');

            return true;
        }

        return parent::canDelete($record);
    }
}
