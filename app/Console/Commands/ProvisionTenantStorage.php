<?php

namespace App\Console\Commands;

use App\Models\Academy;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

class ProvisionTenantStorage extends Command
{
    protected $signature = 'tenants:provision-storage {--academy= : Provision a specific academy ID}';

    protected $description = 'Create tenant-isolated storage directories for all (or a specific) academy';

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

    public function handle(): int
    {
        $academyId = $this->option('academy');

        $academies = $academyId
            ? Academy::where('id', $academyId)->get()
            : Academy::all();

        if ($academies->isEmpty()) {
            $this->error('No academies found.');

            return self::FAILURE;
        }

        $publicDisk = Storage::disk('public');
        $localDisk = Storage::disk('local');

        foreach ($academies as $academy) {
            $tenantId = $academy->id;
            $created = 0;

            foreach (self::TENANT_PUBLIC_DIRS as $dir) {
                $path = "tenants/{$tenantId}/{$dir}";
                if (! $publicDisk->exists($path)) {
                    $publicDisk->makeDirectory($path);
                    $created++;
                }
            }

            foreach (self::TENANT_PRIVATE_DIRS as $dir) {
                $path = "tenants/{$tenantId}/{$dir}";
                if (! $localDisk->exists($path)) {
                    $localDisk->makeDirectory($path);
                    $created++;
                }
            }

            $total = count(self::TENANT_PUBLIC_DIRS) + count(self::TENANT_PRIVATE_DIRS);
            $this->info("Academy {$tenantId} ({$academy->name}): {$created} new dirs created, " . ($total - $created) . ' already existed.');
        }

        $this->info('Tenant storage provisioning complete.');

        return self::SUCCESS;
    }
}
