<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Traits\ApiResponses;
use Illuminate\Http\Request;
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

    public function upload(Request $request): \Illuminate\Http\JsonResponse
    {
        $request->validate([
            'file' => [
                'required',
                'file',
                'max:' . self::MAX_FILE_SIZE,
                'mimes:' . implode(',', self::ALLOWED_MIMES),
            ],
            'disk' => ['required', 'string', 'in:' . implode(',', self::ALLOWED_DISKS)],
            'directory' => ['nullable', 'string', 'regex:/^[a-zA-Z0-9_\-\/]+$/'],
        ], [
            'file.mimes' => 'نوع الملف غير مسموح به. الأنواع المسموحة: ' . implode(', ', self::ALLOWED_MIMES),
            'file.max' => 'حجم الملف يتجاوز الحد المسموح به (' . (self::MAX_FILE_SIZE / 1024) . ' ميجابايت)',
            'disk.in' => 'موقع التخزين غير صالح',
            'directory.regex' => 'مسار المجلد يحتوي على أحرف غير مسموح بها',
        ]);

        try {
            if ($request->hasFile('file')) {
                $file = $request->file('file');
                $disk = $request->input('disk', 'public');
                $directory = $request->input('directory', '');

                // Sanitize directory path to prevent traversal
                $directory = $this->sanitizeDirectory($directory);

                // Generate safe filename
                $safeFilename = $this->generateSafeFilename($file);

                // Store the file with sanitized name
                $path = $file->storeAs($directory, $safeFilename, $disk);

                return $this->successResponse([
                    'path' => $path,
                    'filename' => $safeFilename,
                    'url' => Storage::disk($disk)->url($path),
                ]);
            }
        } catch (\Illuminate\Validation\ValidationException $e) {
            return $this->validationErrorResponse($e->errors(), 'فشل التحقق من الملف');
        } catch (\Exception $e) {
            report($e);

            return $this->serverErrorResponse('حدث خطأ أثناء رفع الملف');
        }

        return $this->errorResponse('لم يتم توفير ملف', 400);
    }

    /**
     * Sanitize directory path to prevent path traversal attacks
     */
    private function sanitizeDirectory(string $directory): string
    {
        // Remove any path traversal attempts
        $directory = str_replace(['..', '\\'], '', $directory);

        // Remove leading/trailing slashes
        $directory = trim($directory, '/');

        // Remove any double slashes
        $directory = preg_replace('#/+#', '/', $directory);

        return $directory;
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
        if (!in_array($extension, self::ALLOWED_MIMES)) {
            $extension = 'bin'; // Fallback extension
        }

        return "{$safeName}_{$timestamp}.{$extension}";
    }
}
