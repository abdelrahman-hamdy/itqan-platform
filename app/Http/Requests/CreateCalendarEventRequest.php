<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CreateCalendarEventRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $user = $this->user();

        return $user && ($user->isAcademicTeacher() || $user->isQuranTeacher() || $user->isAdmin());
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'title' => 'required|string|max:255',
            'start' => 'required|date',
            'end' => 'nullable|date|after_or_equal:start',
            'all_day' => 'nullable|boolean',
            'type' => 'nullable|string|in:session,event,holiday,reminder',
            'description' => 'nullable|string|max:1000',
            'color' => 'nullable|string|max:20',
        ];
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'title.required' => 'عنوان الحدث مطلوب',
            'title.max' => 'عنوان الحدث يجب ألا يتجاوز 255 حرفاً',
            'start.required' => 'وقت البداية مطلوب',
            'start.date' => 'وقت البداية غير صالح',
            'end.date' => 'وقت النهاية غير صالح',
            'end.after_or_equal' => 'وقت النهاية يجب أن يكون بعد أو يساوي وقت البداية',
            'type.in' => 'نوع الحدث غير صالح',
            'description.max' => 'الوصف يجب ألا يتجاوز 1000 حرف',
        ];
    }
}
