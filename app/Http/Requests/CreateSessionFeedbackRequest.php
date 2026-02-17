<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class CreateSessionFeedbackRequest extends FormRequest
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
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'feedback' => 'required|string|max:1000',
            'rating' => 'nullable|integer|min:1|max:5',
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
            'feedback.required' => 'التقييم مطلوب',
            'feedback.max' => 'التقييم يجب ألا يتجاوز 1000 حرف',
            'rating.integer' => 'التقييم يجب أن يكون رقماً صحيحاً',
            'rating.min' => 'التقييم يجب أن يكون على الأقل 1',
            'rating.max' => 'التقييم يجب ألا يتجاوز 5',
        ];
    }
}
