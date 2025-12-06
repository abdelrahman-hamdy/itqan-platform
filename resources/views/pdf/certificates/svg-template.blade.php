<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <style>
        @page {
            margin: 0;
            size: 297mm 210mm landscape;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'xbriyaz', 'DejaVu Sans', Arial, sans-serif;
            margin: 0;
            padding: 0;
        }

        .certificate-wrapper {
            width: 297mm;
            height: 210mm;
            background: white;
            position: relative;
        }

        /* Outer decorative border */
        .certificate-border {
            position: absolute;
            top: 8mm;
            left: 8mm;
            right: 8mm;
            bottom: 8mm;
            border: 4px solid {{ $template_style->primaryColor() }};
        }

        /* Inner decorative border */
        .certificate-border-inner {
            position: absolute;
            top: 12mm;
            left: 12mm;
            right: 12mm;
            bottom: 12mm;
            border: 2px solid {{ $template_style->secondaryColor() }};
        }

        /* Content table */
        .content-table {
            width: 100%;
            height: 210mm;
            border-collapse: collapse;
        }

        .content-table td {
            vertical-align: middle;
            text-align: center;
            padding: 0 25mm;
        }

        /* Header row */
        .header-row td {
            height: 45mm;
            vertical-align: bottom;
            padding-bottom: 5mm;
        }

        .logo {
            width: 60px;
            height: 60px;
        }

        /* Title section */
        .certificate-title {
            font-size: 42px;
            font-weight: bold;
            color: {{ $template_style->primaryColor() }};
            margin-bottom: 8px;
        }

        .subtitle {
            font-size: 18px;
            color: #666666;
            margin-top: 10px;
        }

        /* Main content row */
        .main-row td {
            height: 80mm;
            vertical-align: middle;
        }

        .student-name {
            font-size: 34px;
            font-weight: bold;
            color: #1a1a1a;
            padding: 12px 40px;
            border-bottom: 3px solid {{ $template_style->secondaryColor() }};
            display: inline-block;
            margin-bottom: 20px;
        }

        .certificate-text {
            font-size: 18px;
            line-height: 2;
            color: #444444;
            max-width: 600px;
            margin: 0 auto;
            margin-top: 15px;
        }

        /* Footer row */
        .footer-row td {
            height: 55mm;
            vertical-align: top;
            padding-top: 10mm;
        }

        .footer-table {
            width: 100%;
            border-collapse: collapse;
        }

        .footer-table td {
            width: 33.33%;
            text-align: center;
            vertical-align: top;
            padding: 0 20px;
        }

        .signature-line {
            width: 150px;
            height: 1px;
            background-color: #bbbbbb;
            margin: 0 auto 8px auto;
        }

        .signature-label {
            font-size: 13px;
            color: #888888;
            margin-bottom: 4px;
        }

        .signature-value {
            font-size: 15px;
            font-weight: bold;
            color: #333333;
        }

        /* Certificate number */
        .certificate-number-row td {
            height: 30mm;
            vertical-align: bottom;
            padding-bottom: 12mm;
        }

        .certificate-number {
            font-size: 11px;
            color: #999999;
            direction: ltr;
        }
    </style>
</head>
<body>
    <div class="certificate-wrapper">
        <!-- Decorative borders -->
        <div class="certificate-border"></div>
        <div class="certificate-border-inner"></div>

        <!-- Content using tables for mPDF compatibility -->
        <table class="content-table">
            <!-- Header with logo and title -->
            <tr class="header-row">
                <td>
                    @if($academy_logo)
                        <img src="{{ $academy_logo }}" class="logo" alt="Logo" /><br>
                    @endif
                    <div class="certificate-title">شهادة تقدير</div>
                    <div class="subtitle">تُمنح هذه الشهادة إلى</div>
                </td>
            </tr>

            <!-- Main content -->
            <tr class="main-row">
                <td>
                    <div class="student-name">{{ $student_name }}</div>
                    <div class="certificate-text">{{ $certificate_text }}</div>
                </td>
            </tr>

            <!-- Footer with signatures -->
            <tr class="footer-row">
                <td>
                    <table class="footer-table">
                        <tr>
                            <td>
                                <div class="signature-line"></div>
                                <div class="signature-label">المعلم</div>
                                <div class="signature-value">{{ $teacher_name ?? '—' }}</div>
                            </td>
                            <td>
                                <div class="signature-line"></div>
                                <div class="signature-label">التاريخ</div>
                                <div class="signature-value">{{ $issued_date_formatted }}</div>
                            </td>
                            <td>
                                <div class="signature-line"></div>
                                <div class="signature-label">الأكاديمية</div>
                                <div class="signature-value">{{ $academy_name ?? '—' }}</div>
                            </td>
                        </tr>
                    </table>
                </td>
            </tr>

            <!-- Certificate number -->
            <tr class="certificate-number-row">
                <td>
                    <div class="certificate-number">{{ $certificate_number }}</div>
                </td>
            </tr>
        </table>
    </div>
</body>
</html>
