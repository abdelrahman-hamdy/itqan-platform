<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>شهادة - Certificate</title>
    <style>
        @page {
            margin: 0;
            size: A4 landscape;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Cairo', 'DejaVu Sans', sans-serif;
            direction: rtl;
            text-align: center;
            width: 297mm;
            height: 210mm;
            position: relative;
            overflow: hidden;
        }

        .certificate-container {
            width: 100%;
            height: 100%;
            position: relative;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            padding: 40px 60px;
        }

        .certificate-header {
            margin-bottom: 30px;
        }

        .academy-logo {
            width: 100px;
            height: 100px;
            object-fit: contain;
            margin-bottom: 15px;
        }

        .academy-name {
            font-size: 24px;
            font-weight: bold;
            color: #1e3a8a;
            margin-bottom: 10px;
        }

        .certificate-title {
            font-size: 48px;
            font-weight: bold;
            margin-bottom: 40px;
            letter-spacing: 2px;
        }

        .certificate-body {
            max-width: 800px;
            margin: 0 auto 40px;
        }

        .recipient-section {
            margin-bottom: 30px;
        }

        .recipient-label {
            font-size: 18px;
            margin-bottom: 10px;
            color: #4b5563;
        }

        .recipient-name {
            font-size: 36px;
            font-weight: bold;
            margin-bottom: 20px;
            padding-bottom: 10px;
        }

        .certificate-text {
            font-size: 20px;
            line-height: 1.8;
            color: #374151;
            margin-bottom: 30px;
            text-align: center;
        }

        .certificate-footer {
            display: flex;
            justify-content: space-between;
            align-items: flex-end;
            max-width: 800px;
            margin: 0 auto;
            padding-top: 40px;
        }

        .signature-section {
            text-align: center;
            width: 200px;
        }

        .signature-line {
            border-top: 2px solid #000;
            margin-bottom: 10px;
            width: 100%;
        }

        .signature-name {
            font-family: 'Satisfy', 'Dancing Script', cursive;
            font-size: 24px;
            margin-bottom: 5px;
        }

        .signature-title {
            font-size: 14px;
            color: #6b7280;
        }

        .certificate-meta {
            position: absolute;
            bottom: 20px;
            left: 0;
            right: 0;
            display: flex;
            justify-content: space-between;
            padding: 0 60px;
            font-size: 12px;
            color: #9ca3af;
        }

        .certificate-number {
            direction: ltr;
        }

        /* Template-specific styles will be added by child templates */
        @yield('styles')
    </style>
</head>
<body>
    @yield('content')
</body>
</html>
