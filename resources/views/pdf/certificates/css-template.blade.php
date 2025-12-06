<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <style>
        @page {
            margin: 0;
            padding: 0;
            size: A4 landscape;
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
            background: #fff;
        }

        .certificate {
            width: 297mm;
            height: 210mm;
            position: relative;
            background: linear-gradient(135deg, #fdfbf7 0%, #f5f0e6 100%);
            overflow: hidden;
        }

        /* Decorative Corner Flourishes */
        .corner {
            position: absolute;
            width: 120px;
            height: 120px;
            border: 4px solid {{ $template_style->primaryColor() }};
        }

        .corner-tl {
            top: 15mm;
            left: 15mm;
            border-right: none;
            border-bottom: none;
        }

        .corner-tr {
            top: 15mm;
            right: 15mm;
            border-left: none;
            border-bottom: none;
        }

        .corner-bl {
            bottom: 15mm;
            left: 15mm;
            border-right: none;
            border-top: none;
        }

        .corner-br {
            bottom: 15mm;
            right: 15mm;
            border-left: none;
            border-top: none;
        }

        /* Outer Border */
        .outer-border {
            position: absolute;
            top: 10mm;
            left: 10mm;
            right: 10mm;
            bottom: 10mm;
            border: 3px solid {{ $template_style->primaryColor() }};
        }

        /* Inner Border */
        .inner-border {
            position: absolute;
            top: 14mm;
            left: 14mm;
            right: 14mm;
            bottom: 14mm;
            border: 1px solid {{ $template_style->secondaryColor() }};
        }

        /* Middle decorative border */
        .middle-border {
            position: absolute;
            top: 12mm;
            left: 12mm;
            right: 12mm;
            bottom: 12mm;
            border: 2px double {{ $template_style->secondaryColor() }};
        }

        /* Content Container */
        .content {
            position: absolute;
            top: 25mm;
            left: 30mm;
            right: 30mm;
            bottom: 25mm;
            display: table;
            width: calc(100% - 60mm);
            height: calc(100% - 50mm);
            text-align: center;
        }

        .content-inner {
            display: table-cell;
            vertical-align: middle;
            text-align: center;
        }

        /* Logo */
        .logo {
            width: 70px;
            height: 70px;
            margin-bottom: 10px;
        }

        /* Header */
        .header-title {
            font-size: 48px;
            font-weight: bold;
            color: {{ $template_style->primaryColor() }};
            margin-bottom: 5px;
            letter-spacing: 2px;
        }

        .header-subtitle {
            font-size: 18px;
            color: #666;
            margin-bottom: 25px;
        }

        /* Decorative Line */
        .decorative-line {
            width: 200px;
            height: 2px;
            background: linear-gradient(to right, transparent, {{ $template_style->secondaryColor() }}, transparent);
            margin: 15px auto;
        }

        /* Student Name */
        .student-name {
            font-size: 38px;
            font-weight: bold;
            color: #1a1a1a;
            padding: 10px 50px;
            border-bottom: 3px solid {{ $template_style->secondaryColor() }};
            display: inline-block;
            margin-bottom: 20px;
        }

        /* Certificate Text */
        .certificate-text {
            font-size: 18px;
            line-height: 2;
            color: #444;
            max-width: 550px;
            margin: 0 auto;
            margin-top: 15px;
        }

        /* Footer Section */
        .footer {
            position: absolute;
            bottom: 35mm;
            left: 40mm;
            right: 40mm;
        }

        .footer-table {
            width: 100%;
            border-collapse: collapse;
        }

        .footer-table td {
            width: 33.33%;
            text-align: center;
            vertical-align: top;
            padding: 0 15px;
        }

        .signature-line {
            width: 130px;
            height: 1px;
            background: #bbb;
            margin: 0 auto 8px auto;
        }

        .signature-label {
            font-size: 12px;
            color: #888;
            margin-bottom: 4px;
        }

        .signature-value {
            font-size: 14px;
            font-weight: bold;
            color: #333;
        }

        /* Certificate Number */
        .certificate-number {
            position: absolute;
            bottom: 18mm;
            left: 0;
            right: 0;
            text-align: center;
            font-size: 10px;
            color: #999;
            direction: ltr;
        }

        /* Decorative Elements */
        .ornament {
            width: 60px;
            height: 2px;
            background: {{ $template_style->secondaryColor() }};
            margin: 8px auto;
        }

        .ornament-small {
            width: 30px;
            height: 1px;
            background: {{ $template_style->secondaryColor() }};
            margin: 4px auto;
        }
    </style>
</head>
<body>
    <div class="certificate">
        <!-- Decorative Borders -->
        <div class="outer-border"></div>
        <div class="middle-border"></div>
        <div class="inner-border"></div>

        <!-- Corner Flourishes -->
        <div class="corner corner-tl"></div>
        <div class="corner corner-tr"></div>
        <div class="corner corner-bl"></div>
        <div class="corner corner-br"></div>

        <!-- Main Content -->
        <div class="content">
            <div class="content-inner">
                @if($academy_logo)
                    <img src="{{ $academy_logo }}" class="logo" alt="Logo" /><br>
                @endif

                <div class="header-title">شهادة تقدير</div>
                <div class="ornament"></div>
                <div class="header-subtitle">تُمنح هذه الشهادة إلى</div>

                <div class="decorative-line"></div>

                <div class="student-name">{{ $student_name }}</div>

                <div class="certificate-text">{{ $certificate_text }}</div>
            </div>
        </div>

        <!-- Footer with Signatures -->
        <div class="footer">
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
        </div>

        <!-- Certificate Number -->
        <div class="certificate-number">{{ $certificate_number }}</div>
    </div>
</body>
</html>
