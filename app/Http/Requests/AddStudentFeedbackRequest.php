<?php

namespace App\Http\Requests;

use App\Models\AcademicSession;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class AddStudentFeedbackRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        // Check if user is authenticated and is a student
        if (! $this->user() || ! $this->user()->isStudent()) {
            return false;
        }

        // Get the session from route parameter
        $sessionParam = $this->route('session');

        // If it's a string (ID), load the model
        if (is_string($sessionParam) || is_numeric($sessionParam)) {
            $session = AcademicSession::find($sessionParam);
        } else {
            $session = $sessionParam;
        }

        // Check if this student owns the session
        return $session && (int) $session->student_id === (int) $this->user()->id;
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
            'feedback.string' => 'التقييم يجب أن يكون نصاً',
            'feedback.max' => 'التقييم يجب ألا يتجاوز 1000 حرف',
        ];
    }
}
