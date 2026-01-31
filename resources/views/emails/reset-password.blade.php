@extends('emails.layouts.master')

@section('content')
    <p class="greeting">مرحباً {{ $user->first_name }}،</p>

    <div class="content">
        <p>
            لقد تلقينا طلباً لإعادة تعيين كلمة المرور الخاصة بحسابك في <strong>{{ $academy->name }}</strong>.
        </p>

        <p>
            اضغط على الزر أدناه لإعادة تعيين كلمة المرور:
        </p>
    </div>

    <div class="button-container">
        <a href="{{ $resetUrl }}" class="button">
            إعادة تعيين كلمة المرور
        </a>
    </div>

    <div class="warning-box">
        <p>
            <strong>تنبيه أمني:</strong> هذا الرابط صالح لمدة <strong>60 دقيقة</strong> فقط. لا تشارك هذا الرابط مع أي شخص.
        </p>
    </div>

    <div class="content">
        <p>
            إذا لم تطلب إعادة تعيين كلمة المرور، يمكنك تجاهل هذا البريد الإلكتروني. لن يتم إجراء أي تغييرات على حسابك.
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
