<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ProcessCourseEnrollmentPaymentRequest extends FormRequest
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
            'payment_method' => 'required|in:credit_card,mada,stc_pay,bank_transfer',
            'card_number' => 'required_if:payment_method,credit_card,mada|string',
            'expiry_month' => 'required_if:payment_method,credit_card,mada|integer|min:1|max:12',
            'expiry_year' => 'required_if:payment_method,credit_card,mada|integer|min:2024',
            'cvv' => 'required_if:payment_method,credit_card,mada|string|size:3',
            'cardholder_name' => 'required_if:payment_method,credit_card,mada|string|max:255',
            'payment_gateway' => 'nullable|string',
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
            'payment_method.required' => 'طريقة الدفع مطلوبة',
            'payment_method.in' => 'طريقة الدفع غير صالحة',
            'card_number.required_if' => 'رقم البطاقة مطلوب',
            'card_number.string' => 'رقم البطاقة يجب أن يكون نصاً',
            'expiry_month.required_if' => 'شهر انتهاء الصلاحية مطلوب',
            'expiry_month.integer' => 'شهر انتهاء الصلاحية يجب أن يكون رقماً',
            'expiry_month.min' => 'شهر انتهاء الصلاحية يجب أن يكون بين 1 و 12',
            'expiry_month.max' => 'شهر انتهاء الصلاحية يجب أن يكون بين 1 و 12',
            'expiry_year.required_if' => 'سنة انتهاء الصلاحية مطلوبة',
            'expiry_year.integer' => 'سنة انتهاء الصلاحية يجب أن يكون رقماً',
            'expiry_year.min' => 'سنة انتهاء الصلاحية غير صالحة',
            'cvv.required_if' => 'رمز الأمان مطلوب',
            'cvv.string' => 'رمز الأمان يجب أن يكون نصاً',
            'cvv.size' => 'رمز الأمان يجب أن يكون 3 أرقام',
            'cardholder_name.required_if' => 'اسم حامل البطاقة مطلوب',
            'cardholder_name.string' => 'اسم حامل البطاقة يجب أن يكون نصاً',
            'cardholder_name.max' => 'اسم حامل البطاقة يجب ألا يتجاوز 255 حرفاً',
        ];
    }
}
