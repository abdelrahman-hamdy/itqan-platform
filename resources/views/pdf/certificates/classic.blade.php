@extends('pdf.certificates.layouts.base')

@section('styles')
<style>
    body {
        background: #f9fafb;
    }

    .certificate-container {
        background: white;
        border: 15px solid #1f2937;
        border-image: repeating-linear-gradient(
            45deg,
            #1f2937,
            #1f2937 10px,
            #4b5563 10px,
            #4b5563 20px
        ) 15;
        position: relative;
    }

    .classic-border-inner {
        position: absolute;
        top: 25px;
        left: 25px;
        right: 25px;
        bottom: 25px;
        border: 2px solid #6b7280;
        pointer-events: none;
    }

    .classic-border-decoration {
        position: absolute;
        width: 40px;
        height: 40px;
        border: 3px solid #1f2937;
    }

    .decoration-tl {
        top: 15px;
        left: 15px;
        border-right: none;
        border-bottom: none;
    }

    .decoration-tr {
        top: 15px;
        right: 15px;
        border-left: none;
        border-bottom: none;
    }

    .decoration-bl {
        bottom: 15px;
        left: 15px;
        border-right: none;
        border-top: none;
    }

    .decoration-br {
        bottom: 15px;
        right: 15px;
        border-left: none;
        border-top: none;
    }

    .certificate-title {
        color: #1f2937;
        font-family: 'Times New Roman', serif;
        text-transform: uppercase;
        border-top: 4px double #1f2937;
        border-bottom: 4px double #1f2937;
        padding: 20px 0;
        letter-spacing: 4px;
    }

    .recipient-name {
        color: #000;
        font-family: 'Times New Roman', serif;
        text-decoration: underline;
        text-decoration-color: #6b7280;
        text-underline-offset: 8px;
    }

    .classic-seal {
        position: absolute;
        bottom: 100px;
        left: 80px;
        width: 100px;
        height: 100px;
        border: 3px solid #1f2937;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        background: white;
        font-size: 12px;
        text-align: center;
        color: #1f2937;
        font-weight: bold;
    }

    .certificate-text {
        font-family: 'Times New Roman', serif;
        font-size: 18px;
    }

    .classic-ribbon {
        position: absolute;
        top: -15px;
        left: 50%;
        transform: translateX(-50%);
        background: #1f2937;
        color: white;
        padding: 8px 40px;
        font-size: 14px;
        letter-spacing: 2px;
    }
</style>
@endsection

@section('content')
<div class="certificate-container">
    <div class="classic-border-inner"></div>
    <div class="classic-border-decoration decoration-tl"></div>
    <div class="classic-border-decoration decoration-tr"></div>
    <div class="classic-border-decoration decoration-bl"></div>
    <div class="classic-border-decoration decoration-br"></div>

    <div class="classic-ribbon">CERTIFICATE OF ACHIEVEMENT</div>

    <div class="certificate-header" style="margin-top: 40px;">
        @if($academy_logo)
            <img src="{{ $academy_logo }}" alt="{{ $academy_name }}" class="academy-logo">
        @endif
        <div class="academy-name" style="color: #1f2937;">{{ $academy_name }}</div>
    </div>

    <h1 class="certificate-title">شهادة تقدير</h1>

    <div class="certificate-body">
        <div class="recipient-section">
            <div class="recipient-label" style="font-family: 'Times New Roman', serif;">تُمنح هذه الشهادة إلى</div>
            <div class="recipient-name">{{ $student_name }}</div>
        </div>

        <div class="certificate-text">
            {!! nl2br(e($certificate_text)) !!}
        </div>
    </div>

    <div class="certificate-footer">
        <div class="signature-section">
            <div class="signature-line"></div>
            <div class="signature-name" style="font-family: 'Times New Roman', serif;">{{ $signature_name }}</div>
            <div class="signature-title">{{ $signature_title }}</div>
        </div>

        @if($teacher_name)
        <div class="signature-section">
            <div class="signature-line"></div>
            <div class="signature-name" style="font-family: 'Times New Roman', serif;">{{ $teacher_name }}</div>
            <div class="signature-title">المعلم المشرف</div>
        </div>
        @endif

        <div class="signature-section">
            <div class="signature-line"></div>
            <div style="font-size: 14px; margin-top: 5px;">{{ $issued_date_formatted }}</div>
            <div class="signature-title">التاريخ</div>
        </div>
    </div>

    <div class="classic-seal">
        <div>
            <div>OFFICIAL</div>
            <div>SEAL</div>
        </div>
    </div>

    <div class="certificate-meta">
        <div class="certificate-number">{{ $certificate_number }}</div>
        <div style="font-family: 'Times New Roman', serif;">{{ $academy_name }}</div>
    </div>
</div>
@endsection
