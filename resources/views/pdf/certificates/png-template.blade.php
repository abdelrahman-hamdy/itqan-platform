<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <style>
        body {
            margin: 0;
            padding: 0;
            font-family: 'xbriyaz', Arial, sans-serif;
            direction: rtl;
            text-align: center;
        }

        .certificate-content {
            width: 100%;
            padding-top: 80px;
        }

        .certificate-title {
            font-size: 38pt;
            font-weight: bold;
            color: {{ $template_style->primaryColor() }};
            margin-bottom: 20px;
        }

        .certificate-subtitle {
            font-size: 14pt;
            color: #555555;
            margin-bottom: 30px;
        }

        .student-name {
            font-size: 28pt;
            font-weight: bold;
            color: #1a1a1a;
            margin-bottom: 30px;
            padding-bottom: 10px;
            border-bottom: 2px solid {{ $template_style->secondaryColor() }};
            display: inline-block;
        }

        .certificate-text {
            font-size: 12pt;
            line-height: 1.8;
            color: #333333;
            max-width: 500px;
            margin: 0 auto;
            margin-bottom: 40px;
        }

        .footer-table {
            width: 80%;
            margin: 60px auto 0 auto;
        }

        .footer-table td {
            width: 33%;
            text-align: center;
            vertical-align: top;
            padding: 0 20px;
        }

        .signature-line {
            border-top: 1px solid #999999;
            margin-bottom: 8px;
            width: 100px;
            margin-left: auto;
            margin-right: auto;
        }

        .signature-label {
            font-size: 9pt;
            color: #888888;
        }

        .signature-value {
            font-size: 11pt;
            font-weight: bold;
            color: #333333;
            margin-top: 5px;
        }

        .certificate-number {
            margin-top: 50px;
            font-size: 8pt;
            color: #999999;
            direction: ltr;
        }
    </style>
</head>
<body>
    <div class="certificate-content">
        <div class="certificate-title">شهادة تقدير</div>
        <div class="certificate-subtitle">تُمنح هذه الشهادة إلى</div>
        <div class="student-name">{{ $student_name }}</div>
        <div class="certificate-text">{{ $certificate_text }}</div>

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

        <div class="certificate-number">{{ $certificate_number }}</div>
    </div>
</body>
</html>
