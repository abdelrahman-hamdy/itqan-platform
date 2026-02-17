<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Validation\ValidationException;
use Exception;
use App\Http\Requests\CustomFileUploadRequest;
use App\Http\Traits\Api\ApiResponses;
use App\Services\AcademyContextService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class CustomFileUploadController extends Controller
{
    use ApiResponses;

    /**
     * Allowed file types for upload (MIME types)
     */
    private const ALLOWED_MIMES = [
        // Images
        'jpg', 'jpeg', 'png', 'gif', 'webp', 'svg',
        // Documents
        'pdf', 'doc', 'docx', 'xls', 'xlsx', 'ppt', 'pptx',
        // Text
        'txt', 'csv',
        // Audio (for Quran recitations)
        'mp3', 'wav', 'm4a',
        // Video
        'mp4', 'webm', 'mov',
    ];

    /**
     * Allowed storage disks
     */
    private const ALLOWED_DISKS = ['public', 'private', 'tenant'];

    /**
     * Maximum file size in KB (50MB)
     */
    private const MAX_FILE_SIZE = 51200;

    public function upload(CustomFileUploadRequest $request): JsonResponse
    {

        try {
            if ($request->hasFile('file')) {
                $file = $request->file('file');
                $disk = $request->input('disk', 'public');
                $directory = $request->input('directory', '');

                // Sanitize directory path to prevent traversal
                $directory = $this->sanitizeDirectory($directory);

                // Apply tenant isolation - prepend tenant path
                $directory = $this->getTenantAwarePath($directory);

                // Generate safe filename
                $safeFilename = $this->generateSafeFilename($file);

                // Store the file with sanitized name
                $path = $file->storeAs($directory, $safeFilename, $disk);

                return $this->success([
                    'path' => $path,
                    'filename' => $safeFilename,
                    'url' => Storage::disk($disk)->url($path),
                ]);
            }
        } catch (ValidationException $e) {
            return $this->validationError($e->errors(), 'فشل التحقق من الملف');
        } catch (Exception $e) {
            report($e);

            return $this->serverError('حدث خطأ أثناء رفع الملف');
        }

        return $this->error('لم يتم توفير ملف', 400);
    }

    /**
     * Get tenant-aware storage path by prepending tenant ID.
     *
     * This ensures file isolation between academies in multi-tenant environment.
     */
    private function getTenantAwarePath(string $directory): string
    {
        // Get current academy ID from context or authenticated user
        $academyId = AcademyContextService::getCurrentAcademyId();

        if (! $academyId && Auth::check()) {
            $academyId = Auth::user()->academy_id;
        }

        // If we have an academy ID, prepend tenant path for isolation
        if ($academyId) {
            return "tenants/{$academyId}/{$directory}";
        }

        // Fallback for super admin without academy context (e.g., global uploads)
        return $directory;
    }

    /**
     * Allowed directory prefixes for uploads (whitelist approach)
     */
    private const ALLOWED_DIRECTORY_PREFIXES = [
        'avatars',
        'documents',
        'attachments',
        'homework',
        'recordings',
        'certificates',
        'course-materials',
        'chat-attachments',
        'temp',
    ];

    /**
     * Sanitize directory path to prevent path traversal attacks
     * Uses whitelist approach for security
     */
    private function sanitizeDirectory(string $directory): string
    {
        // Normalize: decode URL encoding that could bypass filters
        $directory = urldecode($directory);

        // Remove null bytes and control characters
        $directory = preg_replace('/[\x00-\x1F\x7F]/', '', $directory);

        // Normalize path separators to forward slash
        $directory = str_replace('\\', '/', $directory);

        // Remove any double slashes
        $directory = preg_replace('#/+#', '/', $directory);

        // Remove leading/trailing slashes
        $directory = trim($directory, '/');

        // Split into segments and validate each
        $segments = explode('/', $directory);
        $cleanSegments = [];

        foreach ($segments as $segment) {
            // Skip empty segments
            if ($segment === '' || $segment === '.') {
                continue;
            }

            // Reject path traversal attempts
            if ($segment === '..' || str_contains($segment, '..')) {
                continue;
            }

            // Only allow alphanumeric, dash, underscore in segment names
            if (! preg_match('/^[a-zA-Z0-9_-]+$/', $segment)) {
                continue;
            }

            $cleanSegments[] = $segment;
        }

        // Rebuild the path
        $cleanPath = implode('/', $cleanSegments);

        // Validate against whitelist - first segment must be an allowed prefix
        if (! empty($cleanSegments)) {
            $firstSegment = $cleanSegments[0];
            if (! in_array($firstSegment, self::ALLOWED_DIRECTORY_PREFIXES, true)) {
                // Default to 'attachments' if not in whitelist
                $cleanPath = 'attachments/' . $cleanPath;
            }
        } else {
            $cleanPath = 'attachments';
        }

        return $cleanPath;
    }

    /**
     * Generate a safe filename to prevent security issues
     */
    private function generateSafeFilename($file): string
    {
        $originalName = $file->getClientOriginalName();
        $extension = $file->getClientOriginalExtension();

        // Get filename without extension
        $nameWithoutExt = pathinfo($originalName, PATHINFO_FILENAME);

        // Slugify the filename (removes special characters, converts to ASCII)
        $safeName = Str::slug($nameWithoutExt);

        // If slug is empty, use a random string
        if (empty($safeName)) {
            $safeName = Str::random(10);
        }

        // Add timestamp to ensure uniqueness
        $timestamp = now()->format('Ymd_His');

        // Validate extension is in allowed list
        $extension = strtolower($extension);
        if (! in_array($extension, self::ALLOWED_MIMES)) {
            $extension = 'bin'; // Fallback extension
        }

        return "{$safeName}_{$timestamp}.{$extension}";
    }
}
