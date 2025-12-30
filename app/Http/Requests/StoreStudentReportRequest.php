<?php

namespace App\Http\Requests;

use App\Enums\AttendanceStatus;
use Illuminate\Foundation\Http\FormRequest;

class StoreStudentReportRequest extends FormRequest
{
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
            'session_id' => 'required|integer',
            'student_id' => 'required|integer',
            'attendance_status' => 'nullable|string|in:' . implode(',', AttendanceStatus::values()),
            'notes' => 'nullable|string',
            // Quran-specific fields
            'new_memorization_degree' => 'nullable|numeric|min:0|max:10',
            'reservation_degree' => 'nullable|numeric|min:0|max:10',
            // Academic & Interactive unified fields
            'homework_degree' => 'nullable|numeric|min:0|max:10',
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
            'session_id.required' => 'معرف الجلسة مطلوب',
            'session_id.integer' => 'يجب أن يكون معرف الجلسة رقماً صحيحاً',
            'student_id.required' => 'معرف الطالب مطلوب',
            'student_id.integer' => 'يجب أن يكون معرف الطالب رقماً صحيحاً',
            'attendance_status.string' => 'يجب أن تكون حالة الحضور نصاً',
            'attendance_status.in' => 'حالة الحضور غير صحيحة',
            'notes.string' => 'يجب أن تكون الملاحظات نصاً',
            'new_memorization_degree.numeric' => 'يجب أن تكون درجة الحفظ الجديد رقماً',
            'new_memorization_degree.min' => 'يجب ألا تقل درجة الحفظ الجديد عن 0',
            'new_memorization_degree.max' => 'يجب ألا تتجاوز درجة الحفظ الجديد 10',
            'reservation_degree.numeric' => 'يجب أن تكون درجة المراجعة رقماً',
            'reservation_degree.min' => 'يجب ألا تقل درجة المراجعة عن 0',
            'reservation_degree.max' => 'يجب ألا تتجاوز درجة المراجعة 10',
            'homework_degree.numeric' => 'يجب أن تكون درجة الواجب رقماً',
            'homework_degree.min' => 'يجب ألا تقل درجة الواجب عن 0',
            'homework_degree.max' => 'يجب ألا تتجاوز درجة الواجب 10',
        ];
    }
}
