@extends('emails.layouts.master')

@section('content')
    <p class="greeting">مرحباً {{ $user->first_name }}،</p>

    <div class="content">
        <p>
            شكراً لتسجيلك في <strong>{{ $academy->name }}</strong>.
        </p>

        <p>
            لإكمال عملية التسجيل وتفعيل حسابك، يرجى تأكيد بريدك الإلكتروني بالضغط على الزر أدناه:
        </p>
    </div>

    <div class="button-container">
        <a href="{{ $verificationUrl }}" class="button">
            تأكيد البريد الإلكتروني
        </a>
    </div>

    <div class="info-box">
        <p>
            <strong>ملاحظة:</strong> هذا الرابط صالح لمدة <strong>60 دقيقة</strong> فقط.
        </p>
    </div>

    <div class="content">
        <p>
            إذا لم تقم بإنشاء حساب في {{ $academy->name }}، يمكنك تجاهل هذا البريد الإلكتروني بأمان.
        </p>

        <p style="margin-top: 32px; color: #64748b; font-size: 14px;">
            إذا واجهت مشكلة في الضغط على الزر، انسخ الرابط التالي والصقه في متصفحك:
            <br>
            <span style="color: #0ea5e9; word-break: break-all;">{{ $verificationUrl }}</span>
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
