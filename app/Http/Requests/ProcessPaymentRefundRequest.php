<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ProcessPaymentRefundRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $payment = $this->route('payment');

        return $payment && $this->user() && $this->user()->can('refund', $payment);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $payment = $this->route('payment');

        return [
            'amount' => 'required|numeric|min:0.01|max:'.($payment ? $payment->refundable_amount : 0),
            'reason' => 'required|string|max:500',
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
            'amount.required' => 'المبلغ المراد استرداده مطلوب',
            'amount.numeric' => 'المبلغ يجب أن يكون رقماً',
            'amount.min' => 'المبلغ يجب أن يكون أكبر من صفر',
            'amount.max' => 'المبلغ يتجاوز المبلغ القابل للاسترداد',
            'reason.required' => 'سبب الاسترداد مطلوب',
            'reason.string' => 'سبب الاسترداد يجب أن يكون نصاً',
            'reason.max' => 'سبب الاسترداد يجب ألا يتجاوز 500 حرف',
        ];
    }
}
