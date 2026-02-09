<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CreatePaymentRequest extends FormRequest
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
            'subscription_type' => 'required|string|in:quran,academic,interactive,recorded',
            'subscription_id' => 'required|string',
            'amount' => 'required|numeric|min:0',
            'currency' => 'nullable|string|in:SAR,USD,EGP',
            'payment_method' => 'nullable|string|in:card,wallet,bank_transfer',
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
            'subscription_type.required' => 'نوع الاشتراك مطلوب',
            'subscription_type.in' => 'نوع الاشتراك غير صالح',
            'subscription_id.required' => 'معرف الاشتراك مطلوب',
            'amount.required' => 'المبلغ مطلوب',
            'amount.numeric' => 'المبلغ يجب أن يكون رقماً',
            'amount.min' => 'المبلغ يجب أن يكون أكبر من صفر',
            'currency.in' => 'العملة غير صالحة',
            'payment_method.in' => 'طريقة الدفع غير صالحة',
        ];
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        if (! $this->has('currency')) {
            $this->merge(['currency' => config('currencies.default', 'SAR')]);
        }
    }
}
