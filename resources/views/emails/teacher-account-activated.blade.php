@extends('emails.layouts.master')

@section('content')
    <p class="greeting">مرحباً {{ $user->first_name }}،</p>

    <div class="content">
        <p>
            يسعدنا إبلاغك بأنه <strong>تم تفعيل حسابك</strong> في <strong>{{ $academy->name }}</strong> بنجاح!
        </p>

        <p>
            يمكنك الآن تسجيل الدخول والبدء في استخدام المنصة.
        </p>
    </div>

    <div class="success-box">
        <p>
            <strong>حسابك جاهز!</strong> تم مراجعة طلب التسجيل الخاص بك والموافقة عليه من قبل إدارة الأكاديمية.
        </p>
    </div>

    <div class="button-container">
        <a href="{{ $loginUrl }}" class="button">
            تسجيل الدخول الآن
        </a>
    </div>

    <div class="info-box">
        <p>
            <strong>ماذا يمكنك فعله الآن؟</strong>
        </p>
        <ul style="margin: 12px 0 0 0; padding-right: 20px; list-style-type: disc;">
            <li style="margin-bottom: 8px;">تسجيل الدخول إلى حسابك</li>
            <li style="margin-bottom: 8px;">استكمال ملفك الشخصي</li>
            <li style="margin-bottom: 8px;">الاطلاع على جدول الحصص</li>
            <li style="margin-bottom: 8px;">التواصل مع إدارة الأكاديمية</li>
        </ul>
    </div>

    <div class="content">
        <p style="margin-top: 32px; color: #64748b; font-size: 15px;">
            إذا واجهت أي مشكلة في تسجيل الدخول، يمكنك التواصل مع إدارة الأكاديمية.
        </p>
        <p style="color: {{ ($academy->brand_color ?? \App\Enums\TailwindColor::SKY)->getHexValue(600) }}; word-break: break-all; font-size: 14px; background: {{ ($academy->brand_color ?? \App\Enums\TailwindColor::SKY)->getHexValue(50) }}; padding: 12px 16px; border-radius: 8px; margin-top: 8px;">
            {{ $loginUrl }}
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
