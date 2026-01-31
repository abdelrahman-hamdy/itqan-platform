@extends('emails.layouts.master')

@section('content')
    <p class="greeting">مرحباً {{ $parentProfile->full_name }}،</p>

    <div class="content">
        <p>
            تم إنشاء حساب ولي أمر لك في <strong>{{ $academy->name }}</strong>.
        </p>
    </div>

    <div class="info-box">
        <p><strong>معلومات حسابك:</strong></p>
        <p style="margin-top: 8px;">
            رمز ولي الأمر: <strong class="highlight">{{ $parentProfile->parent_code }}</strong>
            <br>
            البريد الإلكتروني: {{ $user->email }}
            @if($studentsCount > 0)
            <br>
            الطلاب المرتبطون: {{ $studentNames }}{{ $studentsCount > 5 ? ' وآخرون' : '' }}
            @endif
        </p>
    </div>

    <div class="content">
        <p>
            لتفعيل حسابك، يرجى تعيين كلمة مرور جديدة بالضغط على الزر أدناه:
        </p>
    </div>

    <div class="button-container">
        <a href="{{ $resetUrl }}" class="button">
            تعيين كلمة المرور
        </a>
    </div>

    <div class="warning-box">
        <p>
            <strong>ملاحظة:</strong> رابط تعيين كلمة المرور صالح لمدة <strong>48 ساعة</strong>.
        </p>
    </div>

    <div class="content">
        <p>
            بعد تعيين كلمة المرور، يمكنك تسجيل الدخول باستخدام بريدك الإلكتروني وكلمة المرور الجديدة لمتابعة تقدم أبنائك.
        </p>

        <p style="margin-top: 32px; color: #64748b; font-size: 15px;">
            إذا واجهت مشكلة في الضغط على الزر، انسخ الرابط التالي والصقه في متصفحك:
        </p>
        <p style="color: {{ ($academy->brand_color ?? \App\Enums\TailwindColor::SKY)->getHexValue(600) }}; word-break: break-all; font-size: 14px; background: {{ ($academy->brand_color ?? \App\Enums\TailwindColor::SKY)->getHexValue(50) }}; padding: 12px 16px; border-radius: 8px; margin-top: 8px;">
            {{ $resetUrl }}
        </p>
    </div>

    <div class="content" style="margin-top: 32px; padding-top: 24px; border-top: 1px solid #e2e8f0;">
        <p style="margin: 0; color: #64748b;">
            مع أطيب التحيات،
            <br>
            <strong style="color: #1e293b;">فريق {{ $academy->name }}</strong>
        </p>
    </div>
@endsection
