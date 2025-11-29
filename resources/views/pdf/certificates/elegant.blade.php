@extends('pdf.certificates.layouts.base')

@section('styles')
<style>
    .outer-border {
        border: 5px solid #92400e;
        background: linear-gradient(180deg, #fffbeb 0%, #fef3c7 100%);
    }

    .inner-border {
        border: 2px solid #d97706;
        background: rgba(255, 255, 255, 0.98);
    }

    .academy-name {
        color: #78350f;
        font-size: 24pt;
    }

    .certificate-title {
        color: #92400e;
        font-size: 48pt;
        font-style: italic;
    }

    .recipient-name {
        color: #78350f;
        border-bottom: 4px double #d97706;
        font-style: italic;
        padding: 5px 30px;
        background: linear-gradient(90deg, transparent 10%, #fef3c7 50%, transparent 90%);
    }

    .certificate-text {
        font-style: italic;
        color: #78350f;
    }

    .signature-line {
        border-top-color: #d97706;
    }

    .signature-name {
        color: #92400e;
        font-style: italic;
    }

    /* Golden ornament */
    .gold-ornament {
        font-size: 20pt;
        color: #d97706;
        letter-spacing: 10px;
    }

    .gold-divider {
        width: 250px;
        height: 2px;
        background: linear-gradient(90deg, transparent, #d97706, #b45309, #d97706, transparent);
        margin: 15px auto;
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
                    <img src="{{ $academy_logo }}" alt="{{ $academy_name }}" class="academy-logo" style="border: 3px solid #d97706; border-radius: 50%; padding: 5px;">
                @endif
                <div class="academy-name">{{ $academy_name }}</div>
            </div>

            <!-- Top Ornament -->
            <div class="gold-ornament">✦ ❖ ✦</div>

            <!-- Certificate Title -->
            <h1 class="certificate-title">شهادة امتياز</h1>

            <!-- Gold Divider -->
            <div class="gold-divider"></div>

            <!-- Recipient Section -->
            <div class="recipient-label" style="color: #92400e; font-style: italic;">
                ✦ يُشرّفنا منح هذه الشهادة إلى ✦
            </div>
            <div class="recipient-name">{{ $student_name }}</div>

            <!-- Certificate Text -->
            <div class="certificate-text">
                {!! nl2br(e($certificate_text)) !!}
            </div>

            <!-- Bottom Ornament -->
            <div class="gold-ornament" style="font-size: 16pt;">❖</div>

            <!-- Signatures -->
            <table class="signatures-table">
                <tr>
                    <td class="signature-cell">
                        <div class="signature-line"></div>
                        <div class="signature-name">{{ $signature_name }}</div>
                        <div class="signature-title" style="color: #92400e;">{{ $signature_title }}</div>
                    </td>

                    @if($teacher_name)
                    <td class="signature-cell">
                        <div class="signature-line"></div>
                        <div class="signature-name">{{ $teacher_name }}</div>
                        <div class="signature-title" style="color: #92400e;">المعلم المشرف</div>
                    </td>
                    @endif

                    <td class="signature-cell">
                        <div class="signature-line"></div>
                        <div class="signature-name">{{ $issued_date_formatted }}</div>
                        <div class="signature-title" style="color: #92400e;">تاريخ الإصدار</div>
                    </td>
                </tr>
            </table>

            <!-- Meta Information -->
            <div class="meta-section" style="color: #92400e; font-style: italic;">
                <span>{{ $academy_name }}</span>
                &nbsp;&nbsp;|&nbsp;&nbsp;
                <span class="certificate-number">{{ $certificate_number }}</span>
            </div>
        </div>
    </div>
</div>
@endsection
