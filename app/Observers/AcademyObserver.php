<?php

namespace App\Observers;

use App\Models\Academy;
use App\Services\AcademyAdminSyncService;

class AcademyObserver
{
    public function __construct(
        protected AcademyAdminSyncService $syncService
    ) {}

    /**
     * Handle the Academy "updating" event.
     * Sync admin relationship when admin_id changes.
     */
    public function updating(Academy $academy): void
    {
        if ($academy->isDirty('admin_id') && ! AcademyAdminSyncService::isSyncing()) {
            $this->syncService->syncFromAcademy(
                $academy,
                $academy->admin_id,
                $academy->getOriginal('admin_id')
            );
        }
    }

    /**
     * Handle the Academy "created" event.
     * Sync admin relationship for newly created academies with admin_id set.
     */
    public function created(Academy $academy): void
    {
        if ($academy->admin_id && ! AcademyAdminSyncService::isSyncing()) {
            $this->syncService->syncFromAcademy(
                $academy,
                $academy->admin_id,
                null
            );
        }
    }
}
