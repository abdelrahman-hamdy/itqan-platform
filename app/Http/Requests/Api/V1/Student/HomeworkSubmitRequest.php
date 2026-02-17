<?php

namespace App\Http\Requests\Api\V1\Student;

use Illuminate\Contracts\Validation\ValidationRule;
use App\Http\Requests\Api\BaseApiFormRequest;

/**
 * Form request for homework submission.
 *
 * Validates:
 * - Content (text response) - required if no attachments
 * - Attachments (files) - required if no content
 * - File constraints (max 5 files, 10MB each, specific types)
 */
class HomeworkSubmitRequest extends BaseApiFormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'content' => ['required_without:attachments', 'nullable', 'string', 'max:10000'],
            'attachments' => ['required_without:content', 'nullable', 'array', 'max:5'],
            'attachments.*' => ['file', 'mimes:pdf,doc,docx,jpg,jpeg,png,webp', 'max:10240'], // 10MB
        ];
    }

    /**
     * Get custom attributes for validator errors.
     *
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'content' => __('homework content'),
            'attachments' => __('attachments'),
            'attachments.*' => __('attachment file'),
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
            'content.required_without' => __('Please provide content or attach files.'),
            'attachments.required_without' => __('Please provide content or attach files.'),
            'attachments.max' => __('You can upload a maximum of 5 files.'),
            'attachments.*.max' => __('Each file must be less than 10MB.'),
            'attachments.*.mimes' => __('Files must be PDF, DOC, DOCX, or images (JPG, PNG, WEBP).'),
        ];
    }

    /**
     * Get the validation error message.
     */
    protected function getValidationErrorMessage(): string
    {
        return __('Homework submission validation failed.');
    }
}
