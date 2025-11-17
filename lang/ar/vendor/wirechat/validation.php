<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Default Laravel Validation Language Lines
    |--------------------------------------------------------------------------
    |
    | The following language lines contain the default  error messages used by
    | the Laravel validator class. Some of these rules have multiple versions such
    | as the size rules. Feel free to tweak each of these messages here.
    |
    */
    'file' => 'يجب أن يكون حقل :attribute ملفاً.',
    'image' => 'يجب أن يكون حقل :attribute صورة.',
    'required' => 'حقل :attribute مطلوب.',
    'max' => [
        'array' => 'يجب ألا يحتوي حقل :attribute على أكثر من :max عنصر.',
        'file' => 'يجب ألا يتجاوز حجم حقل :attribute :max كيلوبايت.',
        'numeric' => 'يجب ألا تكون قيمة حقل :attribute أكبر من :max.',
        'string' => 'يجب ألا يتجاوز عدد أحرف حقل :attribute :max حرفاً.',
    ],
    'mimes' => 'يجب أن يكون حقل :attribute ملفاً من نوع: :values.',

    /*
    |--------------------------------------------------------------------------
    | Custom Validation Language Lines
    |--------------------------------------------------------------------------
    |
    | Here you may specify custom validation messages for attributes using the
    | convention "attribute.rule" to name the lines. This makes it quick to
    | specify a specific custom language line for a given attribute rule.
    |
    */

    'custom' => [],

];
