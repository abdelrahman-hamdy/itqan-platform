<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CustomFileUploadRequest extends FormRequest
{
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

    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'file' => [
                'required',
                'file',
                'max:' . self::MAX_FILE_SIZE,
                'mimes:' . implode(',', self::ALLOWED_MIMES),
            ],
            'disk' => ['required', 'string', 'in:' . implode(',', self::ALLOWED_DISKS)],
            'directory' => ['nullable', 'string', 'regex:/^[a-zA-Z0-9_\-\/]+$/'],
        ];
    }

    /**
     * Get custom messages for validator errors (Arabic).
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'file.required' => 'الملف مطلوب',
            'file.file' => 'يجب أن يكون ملف صحيح',
            'file.mimes' => 'نوع الملف غير مسموح به. الأنواع المسموحة: ' . implode(', ', self::ALLOWED_MIMES),
            'file.max' => 'حجم الملف يتجاوز الحد المسموح به (' . (self::MAX_FILE_SIZE / 1024) . ' ميجابايت)',
            'disk.required' => 'موقع التخزين مطلوب',
            'disk.string' => 'يجب أن يكون موقع التخزين نصاً',
            'disk.in' => 'موقع التخزين غير صالح',
            'directory.string' => 'يجب أن يكون مسار المجلد نصاً',
            'directory.regex' => 'مسار المجلد يحتوي على أحرف غير مسموح بها',
        ];
    }
}
