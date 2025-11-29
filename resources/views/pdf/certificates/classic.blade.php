@extends('pdf.certificates.layouts.base')

@section('styles')
<style>
    .outer-border {
        border: 4px solid #44403c;
        background: #fafaf9;
    }

    .inner-border {
        border: 2px solid #78716c;
        background: #ffffff;
    }

    .academy-name {
        color: #44403c;
        font-size: 22pt;
    }

    .certificate-title {
        color: #292524;
        font-size: 44pt;
        border-top: 3px double #44403c;
        border-bottom: 3px double #44403c;
        padding: 12px 40px;
        display: inline-block;
        letter-spacing: 3px;
    }

    .recipient-name {
        color: #1c1917;
        border-bottom: 2px solid #57534e;
    }

    .certificate-text {
        color: #374151;
    }

    .signature-line {
        border-top-color: #57534e;
    }

    .signature-name {
        color: #44403c;
    }

    /* Classic ornament */
    .classic-ornament {
        font-size: 14pt;
        color: #57534e;
        letter-spacing: 8px;
        margin: 10px 0;
    }

    .classic-divider {
        width: 200px;
        height: 1px;
        background: #57534e;
        margin: 10px auto;
    }

    .seal-placeholder {
        width: 80px;
        height: 80px;
        border: 3px solid #44403c;
        border-radius: 50%;
        margin: 15px auto;
        display: flex;
        align-items: center;
        justify-content: center;
        background: #fafaf9;
    }

    .seal-text {
        font-size: 8pt;
        color: #44403c;
        text-align: center;
        font-weight: bold;
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

            <!-- Certificate Title -->
            <h1 class="certificate-title">شهادة تقدير</h1>

            <!-- Classic Divider -->
            <div style="margin: 20px auto; text-align: center;">
                <span style="color: #57534e;">◆ ─────── ◇ ─────── ◆</span>
            </div>

            <!-- Recipient Section -->
            <div class="recipient-label">تُمنح هذه الشهادة إلى</div>
            <div class="recipient-name">{{ $student_name }}</div>

            <!-- Certificate Text -->
            <div class="certificate-text">
                {!! nl2br(e($certificate_text)) !!}
            </div>

            <!-- Seal Placeholder -->
            <div class="seal-placeholder">
                <div class="seal-text">
                    الختم<br>الرسمي
                </div>
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
                        <div class="signature-title">المعلم المشرف</div>
                    </td>
                    @endif

                    <td class="signature-cell">
                        <div class="signature-line"></div>
                        <div class="signature-name">{{ $issued_date_formatted }}</div>
                        <div class="signature-title">التاريخ</div>
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
