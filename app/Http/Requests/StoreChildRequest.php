<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreChildRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     * Only parents may link children to their account.
     */
    public function authorize(): bool
    {
        $user = $this->user();

        return $user !== null && $user->isParent();
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'student_code' => 'required|string',
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
            'student_code.required' => 'كود الطالب مطلوب',
            'student_code.string' => 'يجب أن يكون كود الطالب نصاً',
        ];
    }
}
