@extends('pdf.certificates.layouts.base')

@section('styles')
<style>
    body {
        background: linear-gradient(135deg, #fef3c7 0%, #fefce8 100%);
    }

    .certificate-container {
        background: white;
        border: 8px solid #d97706;
        border-radius: 15px;
        box-shadow: 0 0 0 3px #92400e, 0 20px 50px rgba(217, 119, 6, 0.3);
        position: relative;
    }

    .elegant-decoration-top {
        position: absolute;
        top: 0;
        left: 50%;
        transform: translateX(-50%);
        width: 300px;
        height: 80px;
        background: linear-gradient(90deg, transparent 0%, #d97706 50%, transparent 100%);
        opacity: 0.2;
    }

    .elegant-decoration-bottom {
        position: absolute;
        bottom: 0;
        left: 50%;
        transform: translateX(-50%);
        width: 300px;
        height: 80px;
        background: linear-gradient(90deg, transparent 0%, #d97706 50%, transparent 100%);
        opacity: 0.2;
    }

    .elegant-corner {
        position: absolute;
        width: 60px;
        height: 60px;
        border: 4px solid #d97706;
    }

    .corner-tl {
        top: 30px;
        left: 30px;
        border-right: none;
        border-bottom: none;
        border-top-left-radius: 10px;
    }

    .corner-tr {
        top: 30px;
        right: 30px;
        border-left: none;
        border-bottom: none;
        border-top-right-radius: 10px;
    }

    .corner-bl {
        bottom: 30px;
        left: 30px;
        border-right: none;
        border-top: none;
        border-bottom-left-radius: 10px;
    }

    .corner-br {
        bottom: 30px;
        right: 30px;
        border-left: none;
        border-top: none;
        border-bottom-right-radius: 10px;
    }

    .certificate-title {
        color: #92400e;
        font-style: italic;
        text-shadow: 2px 2px 4px rgba(217, 119, 6, 0.2);
        position: relative;
    }

    .certificate-title::before,
    .certificate-title::after {
        content: "✦";
        color: #d97706;
        font-size: 32px;
        margin: 0 20px;
    }

    .recipient-name {
        color: #92400e;
        background: linear-gradient(90deg, transparent 0%, #fef3c7 20%, #fef3c7 80%, transparent 100%);
        padding: 10px 0;
        border-bottom: 2px solid #d97706;
        font-style: italic;
    }

    .elegant-ornament {
        display: inline-block;
        color: #d97706;
        font-size: 24px;
        margin: 0 10px;
    }

    .elegant-badge {
        position: absolute;
        top: 50px;
        right: 80px;
        width: 120px;
        height: 120px;
        border: 4px solid #d97706;
        border-radius: 50%;
        background: radial-gradient(circle, #fef3c7, #fde68a);
        display: flex;
        align-items: center;
        justify-content: center;
        box-shadow: 0 4px 15px rgba(217, 119, 6, 0.3);
    }

    .elegant-badge-inner {
        width: 90%;
        height: 90%;
        border: 2px dashed #d97706;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        flex-direction: column;
        font-weight: bold;
        color: #92400e;
        font-size: 14px;
    }

    .certificate-text {
        font-style: italic;
        color: #78350f;
    }

    .signature-name {
        color: #92400e;
    }

    .gold-divider {
        height: 3px;
        background: linear-gradient(90deg, transparent 0%, #d97706 20%, #d97706 80%, transparent 100%);
        margin: 20px auto;
        width: 60%;
    }
</style>
@endsection

@section('content')
<div class="certificate-container">
    <div class="elegant-decoration-top"></div>
    <div class="elegant-decoration-bottom"></div>
    <div class="elegant-corner corner-tl"></div>
    <div class="elegant-corner corner-tr"></div>
    <div class="elegant-corner corner-bl"></div>
    <div class="elegant-corner corner-br"></div>

    <div class="elegant-badge">
        <div class="elegant-badge-inner">
            <div>★</div>
            <div style="font-size: 10px; margin-top: 5px;">EXCELLENCE</div>
        </div>
    </div>

    <div class="certificate-header">
        @if($academy_logo)
            <img src="{{ $academy_logo }}" alt="{{ $academy_name }}" class="academy-logo" style="border: 3px solid #d97706; border-radius: 50%; padding: 10px;">
        @endif
        <div class="academy-name" style="color: #92400e;">{{ $academy_name }}</div>
        <div class="elegant-ornament">❖</div>
    </div>

    <h1 class="certificate-title">شهادة امتياز</h1>
    <div class="gold-divider"></div>

    <div class="certificate-body">
        <div class="recipient-section">
            <div class="recipient-label" style="color: #92400e; font-style: italic;">
                <span class="elegant-ornament" style="font-size: 18px;">✦</span>
                يُشرّفنا منح هذه الشهادة إلى
                <span class="elegant-ornament" style="font-size: 18px;">✦</span>
            </div>
            <div class="recipient-name">{{ $student_name }}</div>
        </div>

        <div class="certificate-text" style="margin-top: 25px;">
            {!! nl2br(e($certificate_text)) !!}
        </div>
    </div>

    <div class="gold-divider" style="margin-top: 30px;"></div>

    <div class="certificate-footer">
        <div class="signature-section">
            <div class="signature-line" style="border-color: #d97706;"></div>
            <div class="signature-name">{{ $signature_name }}</div>
            <div class="signature-title" style="color: #92400e;">{{ $signature_title }}</div>
        </div>

        @if($teacher_name)
        <div class="signature-section">
            <div class="signature-line" style="border-color: #d97706;"></div>
            <div class="signature-name">{{ $teacher_name }}</div>
            <div class="signature-title" style="color: #92400e;">المعلم المشرف</div>
        </div>
        @endif

        <div class="signature-section">
            <div class="signature-line" style="border-color: #d97706;"></div>
            <div style="font-size: 14px; margin-top: 5px; color: #92400e;">{{ $issued_date_formatted }}</div>
            <div class="signature-title" style="color: #92400e;">تاريخ الإصدار</div>
        </div>
    </div>

    <div class="certificate-meta" style="color: #92400e;">
        <div class="certificate-number" style="font-style: italic;">{{ $certificate_number }}</div>
        <div style="font-style: italic;">{{ $academy_name }}</div>
    </div>
</div>
@endsection
