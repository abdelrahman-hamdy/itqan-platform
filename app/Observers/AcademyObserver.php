<?php

namespace App\Observers;

use App\Models\Academy;
use App\Services\AcademyAdminSyncService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class AcademyObserver
{
    /**
     * Subdirectories created under each tenant's public storage.
     */
    private const TENANT_PUBLIC_DIRS = [
        'avatars/students',
        'avatars/quran-teachers',
        'avatars/academic-teachers',
        'avatars/supervisors',
        'videos/quran-teachers',
        'videos/academic-teachers',
        'course-thumbnails',
        'course-materials',
        'chat-attachments',
        'academy-logos',
        'academy-favicons',
    ];

    /**
     * Subdirectories created under each tenant's private storage.
     */
    private const TENANT_PRIVATE_DIRS = [
        'invoices',
        'exports',
        'homework',
    ];

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
     * Handle the Academy "updated" event.
     * Invalidate cached academy data when settings change.
     */
    public function updated(Academy $academy): void
    {
        Cache::forget("academy:{$academy->id}");
        Cache::forget('academy:default');
        Cache::forget('academies:all');
    }

    /**
     * Handle the Academy "created" event.
     * Provision tenant storage directories and sync admin relationship.
     */
    public function created(Academy $academy): void
    {
        Cache::forget('academy:default');
        Cache::forget('academies:all');

        $this->provisionTenantStorage($academy);

        if ($academy->admin_id && ! AcademyAdminSyncService::isSyncing()) {
            $this->syncService->syncFromAcademy(
                $academy,
                $academy->admin_id,
                null
            );
        }
    }

    /**
     * Handle the Academy "deleted" event.
     * Clear cached academy data.
     */
    public function deleted(Academy $academy): void
    {
        Cache::forget("academy:{$academy->id}");
        Cache::forget('academy:default');
        Cache::forget('academies:all');
    }

    /**
     * Create the tenant-isolated storage directories for a new academy.
     * Sets 2775 (SGID + group-writable) so www-data group can write files.
     */
    private function provisionTenantStorage(Academy $academy): void
    {
        $tenantId = $academy->id;

        try {
            $publicRoot = Storage::disk('public')->path('');
            foreach (self::TENANT_PUBLIC_DIRS as $dir) {
                $path = $publicRoot . "tenants/{$tenantId}/{$dir}";
                if (! is_dir($path)) {
                    mkdir($path, 02775, true);
                }
            }

            $localRoot = Storage::disk('local')->path('');
            foreach (self::TENANT_PRIVATE_DIRS as $dir) {
                $path = $localRoot . "tenants/{$tenantId}/{$dir}";
                if (! is_dir($path)) {
                    mkdir($path, 02775, true);
                }
            }

            Log::info("Tenant storage provisioned for academy {$tenantId}");
        } catch (\Throwable $e) {
            Log::error("Failed to provision tenant storage for academy {$tenantId}", [
                'error' => $e->getMessage(),
            ]);
            report($e);
        }
    }
}
