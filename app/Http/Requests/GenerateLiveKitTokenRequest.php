<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class GenerateLiveKitTokenRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'room_name' => 'required|string|max:255',
            'participant_name' => 'nullable|string|max:255',
            'session_type' => 'nullable|string|in:quran,academic,interactive',
            'session_id' => 'nullable|integer',
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
            'room_name.required' => 'اسم الغرفة مطلوب',
            'room_name.max' => 'اسم الغرفة يجب ألا يتجاوز 255 حرفاً',
            'participant_name.max' => 'اسم المشارك يجب ألا يتجاوز 255 حرفاً',
            'session_type.in' => 'نوع الجلسة غير صالح',
            'session_id.integer' => 'معرف الجلسة يجب أن يكون رقماً صحيحاً',
        ];
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        if (! $this->has('participant_name') && $this->user()) {
            $this->merge([
                'participant_name' => $this->user()->name,
            ]);
        }
    }
}
