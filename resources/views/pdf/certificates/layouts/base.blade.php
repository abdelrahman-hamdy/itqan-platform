<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <title>شهادة - Certificate</title>
    <style>
        /* mPDF optimized styles - using tables for layout */
        @page {
            margin: 0;
            size: A4-L; /* Landscape */
        }

        body {
            font-family: 'xbriyaz', 'dejavusans', sans-serif;
            direction: rtl;
            text-align: center;
            margin: 0;
            padding: 0;
            background: #fff;
        }

        .certificate-container {
            width: 100%;
            height: 100%;
            padding: 15mm 20mm;
        }

        /* Decorative border */
        .outer-border {
            border: 4px solid #1e3a8a;
            padding: 8px;
        }

        .inner-border {
            border: 2px solid #3b82f6;
            padding: 20px 30px;
            background: linear-gradient(180deg, #fefefe 0%, #f8fafc 100%);
        }

        /* Header section */
        .header-section {
            margin-bottom: 15px;
        }

        .academy-logo {
            width: 80px;
            height: 80px;
        }

        .academy-name {
            font-size: 22pt;
            font-weight: bold;
            color: #1e3a8a;
            margin: 10px 0;
        }

        /* Title */
        .certificate-title {
            font-size: 42pt;
            font-weight: bold;
            color: #1e40af;
            margin: 20px 0;
            letter-spacing: 2px;
        }

        .title-decoration {
            width: 200px;
            height: 3px;
            background: linear-gradient(90deg, transparent, #3b82f6, #10b981, transparent);
            margin: 0 auto 20px;
        }

        /* Recipient section */
        .recipient-label {
            font-size: 14pt;
            color: #6b7280;
            margin-bottom: 10px;
        }

        .recipient-name {
            font-size: 32pt;
            font-weight: bold;
            color: #1e3a8a;
            padding-bottom: 8px;
            border-bottom: 3px solid #3b82f6;
            display: inline-block;
            margin-bottom: 20px;
        }

        /* Certificate text */
        .certificate-text {
            font-size: 14pt;
            line-height: 2;
            color: #374151;
            max-width: 600px;
            margin: 0 auto 25px;
            text-align: center;
        }

        /* Footer signatures */
        .signatures-table {
            width: 100%;
            margin-top: 30px;
        }

        .signature-cell {
            width: 33%;
            text-align: center;
            vertical-align: bottom;
            padding: 0 20px;
        }

        .signature-line {
            border-top: 2px solid #1e3a8a;
            width: 150px;
            margin: 0 auto 8px;
        }

        .signature-name {
            font-size: 12pt;
            font-weight: bold;
            color: #1f2937;
        }

        .signature-title {
            font-size: 10pt;
            color: #6b7280;
        }

        /* Certificate meta */
        .meta-section {
            margin-top: 20px;
            font-size: 9pt;
            color: #9ca3af;
        }

        .certificate-number {
            direction: ltr;
            display: inline;
        }

        /* Decorative elements */
        .corner-decoration {
            width: 60px;
            height: 60px;
        }

        .seal-watermark {
            opacity: 0.08;
            width: 120px;
            height: 120px;
        }

        /* Template-specific styles */
        @yield('styles')
    </style>
</head>
<body>
    @yield('content')
</body>
</html>
