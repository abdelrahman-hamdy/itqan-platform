<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class AddInteractiveSessionFeedbackRequest extends FormRequest
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
            'feedback' => 'required|string|max:1000',
        ];
    }

    /**
     * Get custom error messages for validation rules.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'feedback.required' => 'التقييم مطلوب',
            'feedback.max' => 'التقييم يجب ألا يتجاوز 1000 حرف',
        ];
    }
}
