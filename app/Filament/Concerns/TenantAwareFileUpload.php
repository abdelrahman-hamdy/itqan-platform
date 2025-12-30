<?php

namespace App\Filament\Concerns;

use App\Services\AcademyContextService;
use Illuminate\Support\Facades\Auth;

/**
 * Trait TenantAwareFileUpload
 *
 * Provides tenant-aware file upload paths for Filament resources.
 * All uploaded files will be stored in: tenants/{academy_id}/{directory}/
 *
 * Usage in Filament resources:
 *
 * use App\Filament\Concerns\TenantAwareFileUpload;
 *
 * class MyResource extends Resource
 * {
 *     use TenantAwareFileUpload;
 *
 *     public static function form(Form $form): Form
 *     {
 *         return $form->schema([
 *             FileUpload::make('avatar')
 *                 ->directory(static::getTenantDirectory('avatars'))
 *                 ->disk('public'),
 *         ]);
 *     }
 * }
 */
trait TenantAwareFileUpload
{
    /**
     * Get a tenant-aware directory path for file uploads.
     *
     * @param string $baseDirectory The base directory (e.g., 'avatars', 'courses/thumbnails')
     * @return string The tenant-prefixed directory path
     */
    protected static function getTenantDirectory(string $baseDirectory): string
    {
        $academyId = static::resolveAcademyId();

        if ($academyId) {
            return "tenants/{$academyId}/{$baseDirectory}";
        }

        // Fallback for super admin without academy context
        return $baseDirectory;
    }

    /**
     * Get a tenant-aware directory path using a closure for lazy evaluation.
     *
     * Useful when the academy context might not be available at form definition time.
     *
     * @param string $baseDirectory The base directory
     * @return \Closure A closure that returns the tenant-prefixed path
     */
    protected static function getTenantDirectoryLazy(string $baseDirectory): \Closure
    {
        return function () use ($baseDirectory): string {
            return static::getTenantDirectory($baseDirectory);
        };
    }

    /**
     * Resolve the current academy ID from multiple sources.
     *
     * Priority:
     * 1. AcademyContextService (handles subdomain, session, API header)
     * 2. Authenticated user's academy_id
     * 3. Record's academy_id (if editing existing record)
     *
     * @return int|null
     */
    protected static function resolveAcademyId(): ?int
    {
        // 1. Try AcademyContextService first
        try {
            $contextService = app(AcademyContextService::class);
            $academyId = $contextService->getCurrentAcademyId();

            if ($academyId) {
                return $academyId;
            }
        } catch (\Exception $e) {
            // Service not available, continue to fallback
        }

        // 2. Try authenticated user's academy
        if (Auth::check()) {
            $user = Auth::user();
            if ($user && $user->academy_id) {
                return $user->academy_id;
            }
        }

        return null;
    }

    /**
     * Get tenant-aware directory for user avatars.
     */
    protected static function getAvatarDirectory(string $type = 'users'): string
    {
        return static::getTenantDirectory("avatars/{$type}");
    }

    /**
     * Get tenant-aware directory for course thumbnails.
     */
    protected static function getCourseThumbnailDirectory(): string
    {
        return static::getTenantDirectory('courses/thumbnails');
    }

    /**
     * Get tenant-aware directory for lesson videos.
     */
    protected static function getLessonVideoDirectory(): string
    {
        return static::getTenantDirectory('lessons/videos');
    }

    /**
     * Get tenant-aware directory for lesson attachments.
     */
    protected static function getLessonAttachmentDirectory(): string
    {
        return static::getTenantDirectory('lessons/attachments');
    }

    /**
     * Get tenant-aware directory for academy branding (logo, favicon).
     */
    protected static function getBrandingDirectory(): string
    {
        return static::getTenantDirectory('branding');
    }

    /**
     * Get tenant-aware directory for documents.
     */
    protected static function getDocumentDirectory(): string
    {
        return static::getTenantDirectory('documents');
    }

    /**
     * Get tenant-aware directory for homework submissions.
     */
    protected static function getHomeworkDirectory(): string
    {
        return static::getTenantDirectory('homework');
    }

    /**
     * Get tenant-aware directory for certificates.
     */
    protected static function getCertificateDirectory(): string
    {
        return static::getTenantDirectory('certificates');
    }
}
