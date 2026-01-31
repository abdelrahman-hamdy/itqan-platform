@extends('emails.layouts.master')

@section('content')
    <p class="greeting">مرحباً {{ $user->first_name ?? $user->name }}،</p>

    <div class="content">
        <p>
            تم تغيير كلمة المرور الخاصة بحسابك في <strong>{{ $academy->name }}</strong> بنجاح.
        </p>
    </div>

    <div class="info-box">
        <p><strong>تفاصيل التغيير:</strong></p>
        <p style="margin-top: 8px;">
            التاريخ والوقت: {{ now()->setTimezone('Asia/Riyadh')->format('Y-m-d H:i') }}
            @if($ipAddress)
            <br>
            عنوان IP: {{ $ipAddress }}
            @endif
        </p>
    </div>

    <div class="warning-box">
        <p>
            <strong>إذا لم تقم بهذا التغيير:</strong>
            <br>
            يرجى التواصل مع فريق الدعم فوراً لتأمين حسابك.
        </p>
    </div>

    <div class="button-container">
        <a href="{{ route('contact', ['subdomain' => $academy->subdomain]) }}" class="button" style="background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%); box-shadow: 0 4px 14px rgba(239, 68, 68, 0.4);">
            تواصل مع الدعم
        </a>
    </div>

    <div class="content">
        <p><strong>نصائح لأمان حسابك:</strong></p>
        <ul style="color: #475569; padding-right: 20px;">
            <li>لا تشارك كلمة المرور مع أي شخص</li>
            <li>استخدم كلمة مرور قوية ومختلفة لكل حساب</li>
            <li>قم بتفعيل المصادقة الثنائية إن توفرت</li>
        </ul>
    </div>

    <div class="content" style="margin-top: 32px; padding-top: 24px; border-top: 1px solid #e2e8f0;">
        <p style="margin: 0; color: #64748b;">
            مع أطيب التحيات،
            <br>
            <strong style="color: #1e293b;">فريق {{ $academy->name }}</strong>
        </p>
    </div>
@endsection
