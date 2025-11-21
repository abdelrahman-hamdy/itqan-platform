@extends('pdf.certificates.layouts.base')

@section('styles')
<style>
    body {
        background: linear-gradient(135deg, #f0f9ff 0%, #ffffff 100%);
    }

    .certificate-container {
        background: white;
        border: 3px solid #3b82f6;
        border-radius: 20px;
        box-shadow: 0 10px 30px rgba(59, 130, 246, 0.1);
        margin: 20px;
    }

    .certificate-title {
        color: #3b82f6;
        text-transform: uppercase;
        background: linear-gradient(90deg, #3b82f6, #10b981);
        -webkit-background-clip: text;
        -webkit-text-fill-color: transparent;
        background-clip: text;
    }

    .recipient-name {
        color: #1e3a8a;
        border-bottom: 3px solid #3b82f6;
    }

    .modern-decoration {
        position: absolute;
        opacity: 0.05;
    }

    .decoration-top-left {
        top: 0;
        left: 0;
        width: 200px;
        height: 200px;
        border-top: 40px solid #3b82f6;
        border-left: 40px solid #3b82f6;
        border-top-left-radius: 20px;
    }

    .decoration-bottom-right {
        bottom: 0;
        right: 0;
        width: 200px;
        height: 200px;
        border-bottom: 40px solid #10b981;
        border-right: 40px solid #10b981;
        border-bottom-right-radius: 20px;
    }

    .modern-icon {
        display: inline-block;
        width: 60px;
        height: 60px;
        background: linear-gradient(135deg, #3b82f6, #10b981);
        border-radius: 50%;
        margin-bottom: 20px;
        position: relative;
    }

    .modern-icon::after {
        content: "✓";
        position: absolute;
        top: 50%;
        left: 50%;
        transform: translate(-50%, -50%);
        color: white;
        font-size: 32px;
        font-weight: bold;
    }
</style>
@endsection

@section('content')
<div class="certificate-container">
    <div class="modern-decoration decoration-top-left"></div>
    <div class="modern-decoration decoration-bottom-right"></div>

    <div class="certificate-header">
        @if($academy_logo)
            <img src="{{ $academy_logo }}" alt="{{ $academy_name }}" class="academy-logo">
        @endif
        <div class="academy-name">{{ $academy_name }}</div>
        <div class="modern-icon"></div>
    </div>

    <h1 class="certificate-title">شهادة إتمام</h1>

    <div class="certificate-body">
        <div class="recipient-section">
            <div class="recipient-label">هذا يشهد بأن</div>
            <div class="recipient-name">{{ $student_name }}</div>
        </div>

        <div class="certificate-text">
            {!! nl2br(e($certificate_text)) !!}
        </div>
    </div>

    <div class="certificate-footer">
        <div class="signature-section">
            <div class="signature-line"></div>
            <div class="signature-name">{{ $signature_name }}</div>
            <div class="signature-title">{{ $signature_title }}</div>
        </div>

        @if($teacher_name)
        <div class="signature-section">
            <div class="signature-line"></div>
            <div class="signature-name">{{ $teacher_name }}</div>
            <div class="signature-title">المعلم</div>
        </div>
        @endif

        <div class="signature-section">
            <div class="signature-line"></div>
            <div style="font-size: 14px; margin-top: 5px;">{{ $issued_date_formatted }}</div>
            <div class="signature-title">تاريخ الإصدار</div>
        </div>
    </div>

    <div class="certificate-meta">
        <div class="certificate-number">Certificate No: {{ $certificate_number }}</div>
        <div>{{ $academy_name }}</div>
    </div>
</div>
@endsection
