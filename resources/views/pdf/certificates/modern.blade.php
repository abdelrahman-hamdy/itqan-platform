@extends('pdf.certificates.layouts.base')

@section('styles')
<style>
    .outer-border {
        border-color: #3b82f6;
        border-radius: 8px;
        background: linear-gradient(135deg, #f0f9ff 0%, #e0f2fe 50%, #f0fdf4 100%);
    }

    .inner-border {
        border-color: rgba(59, 130, 246, 0.4);
        border-radius: 4px;
        background: rgba(255, 255, 255, 0.95);
    }

    .certificate-title {
        color: #1e40af;
    }

    .recipient-name {
        color: #1e3a8a;
        border-bottom-color: #3b82f6;
    }

    .signature-line {
        border-top-color: #3b82f6;
    }

    /* Decorative star */
    .award-star {
        font-size: 48pt;
        color: #f59e0b;
        margin-bottom: 10px;
    }
</style>
@endsection

@section('content')
<div class="certificate-container">
    <div class="outer-border">
        <div class="inner-border">
            <!-- Header with Logo -->
            <div class="header-section">
                @if($academy_logo)
                    <img src="{{ $academy_logo }}" alt="{{ $academy_name }}" class="academy-logo">
                @endif
                <div class="academy-name">{{ $academy_name }}</div>
            </div>

            <!-- Award Icon -->
            <div class="award-star">★</div>

            <!-- Certificate Title -->
            <h1 class="certificate-title">شهادة تقدير</h1>

            <!-- Decorative Line -->
            <div style="width: 180px; height: 3px; background: linear-gradient(90deg, #3b82f6, #10b981); margin: 0 auto 25px;"></div>

            <!-- Recipient Section -->
            <div class="recipient-label">تُمنح هذه الشهادة إلى</div>
            <div class="recipient-name">{{ $student_name }}</div>

            <!-- Certificate Text -->
            <div class="certificate-text">
                {!! nl2br(e($certificate_text)) !!}
            </div>

            <!-- Signatures -->
            <table class="signatures-table">
                <tr>
                    <td class="signature-cell">
                        <div class="signature-line"></div>
                        <div class="signature-name">{{ $signature_name }}</div>
                        <div class="signature-title">{{ $signature_title }}</div>
                    </td>

                    @if($teacher_name)
                    <td class="signature-cell">
                        <div class="signature-line"></div>
                        <div class="signature-name">{{ $teacher_name }}</div>
                        <div class="signature-title">المعلم</div>
                    </td>
                    @endif

                    <td class="signature-cell">
                        <div class="signature-line"></div>
                        <div class="signature-name">{{ $issued_date_formatted }}</div>
                        <div class="signature-title">تاريخ الإصدار</div>
                    </td>
                </tr>
            </table>

            <!-- Meta Information -->
            <div class="meta-section">
                <span>{{ $academy_name }}</span>
                &nbsp;&nbsp;|&nbsp;&nbsp;
                <span class="certificate-number">{{ $certificate_number }}</span>
            </div>
        </div>
    </div>
</div>
@endsection
