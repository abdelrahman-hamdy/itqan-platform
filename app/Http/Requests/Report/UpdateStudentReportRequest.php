<?php

namespace App\Http\Requests\Report;

use App\Enums\AttendanceStatus;
use Illuminate\Foundation\Http\FormRequest;

class UpdateStudentReportRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check();
    }

    public function rules(): array
    {
        return [
            'attendance_status' => 'nullable|string|in:' . implode(',', AttendanceStatus::values()),
            'notes' => 'nullable|string',
            // Quran-specific fields
            'new_memorization_degree' => 'nullable|numeric|min:0|max:10',
            'reservation_degree' => 'nullable|numeric|min:0|max:10',
            // Academic & Interactive unified fields
            'homework_degree' => 'nullable|numeric|min:0|max:10',
        ];
    }

    public function messages(): array
    {
        return [
            'attendance_status.in' => 'حالة الحضور غير صحيحة',
            'notes.string' => 'الملاحظات يجب أن تكون نص',
            'new_memorization_degree.numeric' => 'درجة الحفظ يجب أن تكون رقم',
            'new_memorization_degree.min' => 'درجة الحفظ يجب أن تكون 0 على الأقل',
            'new_memorization_degree.max' => 'درجة الحفظ يجب ألا تتجاوز 10',
            'reservation_degree.numeric' => 'درجة المراجعة يجب أن تكون رقم',
            'reservation_degree.min' => 'درجة المراجعة يجب أن تكون 0 على الأقل',
            'reservation_degree.max' => 'درجة المراجعة يجب ألا تتجاوز 10',
            'homework_degree.numeric' => 'درجة الواجب يجب أن تكون رقم',
            'homework_degree.min' => 'درجة الواجب يجب أن تكون 0 على الأقل',
            'homework_degree.max' => 'درجة الواجب يجب ألا تتجاوز 10',
        ];
    }
}
