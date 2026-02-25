<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class UpdateParentProfileRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     * Only the authenticated parent may update their own profile.
     */
    public function authorize(): bool
    {
        $user = $this->user();

        if ($user === null || ! $user->isParent()) {
            return false;
        }

        // Must be updating their own profile
        $profileId = $this->route('id') ?? $this->route('parent');
        if ($profileId !== null && (string) $profileId !== (string) $user->id) {
            return false;
        }

        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'phone' => 'nullable|string|max:20',
            'phone_country_code' => 'nullable|string|max:5',
            'secondary_phone' => 'nullable|string|max:20',
            'address' => 'nullable|string|max:500',
            'occupation' => 'nullable|string|max:255',
            'preferred_contact_method' => 'nullable|in:phone,email,sms,whatsapp',
            'avatar' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
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
            'first_name.required' => 'الاسم الأول مطلوب',
            'first_name.max' => 'الاسم الأول يجب ألا يتجاوز 255 حرفاً',
            'last_name.required' => 'الاسم الأخير مطلوب',
            'last_name.max' => 'الاسم الأخير يجب ألا يتجاوز 255 حرفاً',
            'phone.max' => 'رقم الهاتف يجب ألا يتجاوز 20 رقماً',
            'phone_country_code.max' => 'كود الدولة يجب ألا يتجاوز 5 أحرف',
            'secondary_phone.max' => 'رقم الهاتف الثانوي يجب ألا يتجاوز 20 رقماً',
            'address.max' => 'العنوان يجب ألا يتجاوز 500 حرف',
            'occupation.max' => 'المهنة يجب ألا تتجاوز 255 حرفاً',
            'preferred_contact_method.in' => 'طريقة التواصل المفضلة يجب أن تكون: هاتف، بريد إلكتروني، رسالة نصية، أو واتساب',
            'avatar.image' => 'الصورة يجب أن تكون ملف صورة',
            'avatar.mimes' => 'الصورة يجب أن تكون من نوع: jpeg, png, jpg, gif',
            'avatar.max' => 'حجم الصورة يجب ألا يتجاوز 2 ميجابايت',
        ];
    }
}
