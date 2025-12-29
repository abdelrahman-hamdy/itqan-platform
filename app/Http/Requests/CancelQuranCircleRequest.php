<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CancelQuranCircleRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $user = $this->user();
        return $user && ($user->isAdmin() || $user->isSupervisor());
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'cancellation_reason' => 'required|string|max:500',
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
            'cancellation_reason.required' => 'سبب الإلغاء مطلوب',
            'cancellation_reason.string' => 'سبب الإلغاء يجب أن يكون نصاً',
            'cancellation_reason.max' => 'سبب الإلغاء يجب ألا يتجاوز 500 حرف',
        ];
    }
}
