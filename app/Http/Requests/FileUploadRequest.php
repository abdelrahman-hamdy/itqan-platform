<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class FileUploadRequest extends FormRequest
{
    private const ALLOWED_MIMES = [
        'jpg', 'jpeg', 'png', 'gif', 'webp', 'svg',
        'pdf', 'doc', 'docx', 'xls', 'xlsx', 'ppt', 'pptx',
        'txt', 'csv',
        'mp3', 'wav', 'm4a',
        'mp4', 'webm', 'mov',
    ];

    private const ALLOWED_DISKS = ['public', 'private', 'tenant'];

    private const MAX_FILE_SIZE = 51200; // 50MB in KB

    public function authorize(): bool
    {
        return auth()->check();
    }

    public function rules(): array
    {
        return [
            'file' => [
                'required',
                'file',
                'max:'.self::MAX_FILE_SIZE,
                'mimes:'.implode(',', self::ALLOWED_MIMES),
            ],
            'disk' => ['required', 'string', 'in:'.implode(',', self::ALLOWED_DISKS)],
            'directory' => ['nullable', 'string', 'regex:/^[a-zA-Z0-9_\-\/]+$/'],
        ];
    }

    public function messages(): array
    {
        return [
            'file.required' => 'الملف مطلوب',
            'file.file' => 'يجب أن يكون ملف صحيح',
            'file.mimes' => 'نوع الملف غير مسموح به. الأنواع المسموحة: '.implode(', ', self::ALLOWED_MIMES),
            'file.max' => 'حجم الملف يتجاوز الحد المسموح به ('.(self::MAX_FILE_SIZE / 1024).' ميجابايت)',
            'disk.required' => 'موقع التخزين مطلوب',
            'disk.in' => 'موقع التخزين غير صالح',
            'directory.regex' => 'مسار المجلد يحتوي على أحرف غير مسموح بها',
        ];
    }
}
