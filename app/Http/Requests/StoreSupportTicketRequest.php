<?php

namespace App\Http\Requests;

use App\Enums\SupportTicketReason;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreSupportTicketRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();

        return $user && ($user->isStudent() || $user->isQuranTeacher() || $user->isAcademicTeacher());
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'reason' => ['required', Rule::in(SupportTicketReason::values())],
            'description' => ['required', 'string', 'min:10', 'max:2000'],
            'image' => ['nullable', 'image', 'max:5120', 'mimes:jpg,jpeg,png,webp'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'reason.required' => __('support.validation.reason_required'),
            'description.required' => __('support.validation.description_required'),
            'description.min' => __('support.validation.description_min'),
            'description.max' => __('support.validation.description_max'),
            'image.image' => __('support.validation.image_invalid'),
            'image.max' => __('support.validation.image_max'),
        ];
    }
}
