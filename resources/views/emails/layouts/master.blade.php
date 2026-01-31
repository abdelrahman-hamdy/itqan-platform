<!DOCTYPE html>
<html dir="rtl" lang="ar">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>{{ $subject ?? $academy->name }}</title>
    @php
        // Get academy brand colors - fallback to sky if not set
        $brandColor = $academy->brand_color ?? \App\Enums\TailwindColor::SKY;

        // Primary colors for gradient and buttons
        $primaryLight = $brandColor->getHexValue(500);
        $primaryDark = $brandColor->getHexValue(600);
        $primaryDarker = $brandColor->getHexValue(700);

        // Background tints
        $primaryBg = $brandColor->getHexValue(50);
        $primaryBgMedium = $brandColor->getHexValue(100);

        // Text color on primary background
        $primaryText = $brandColor->getHexValue(700);
        $primaryTextDark = $brandColor->getHexValue(800);

        // Shadow color with opacity
        $shadowColor = $brandColor->getHexValue(500);
    @endphp
    <style>
        /* Reset */
        body, table, td, p, a, li, blockquote {
            -webkit-text-size-adjust: 100%;
            -ms-text-size-adjust: 100%;
        }
        table, td {
            mso-table-lspace: 0pt;
            mso-table-rspace: 0pt;
        }
        img {
            -ms-interpolation-mode: bicubic;
            border: 0;
            height: auto;
            line-height: 100%;
            outline: none;
            text-decoration: none;
        }

        /* Base styles */
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            margin: 0;
            padding: 0;
            width: 100% !important;
            height: 100% !important;
            background-color: #f4f7fa;
            direction: rtl;
            -webkit-font-smoothing: antialiased;
        }

        .email-wrapper {
            width: 100%;
            background-color: #f4f7fa;
            padding: 40px 20px;
        }

        .email-container {
            max-width: 600px;
            margin: 0 auto;
            background-color: #ffffff;
            border-radius: 20px;
            overflow: hidden;
            box-shadow: 0 8px 40px rgba(0, 0, 0, 0.1);
        }

        /* Header with dynamic gradient */
        .email-header {
            background: linear-gradient(135deg, {{ $primaryLight }} 0%, {{ $primaryDark }} 50%, {{ $primaryDarker }} 100%);
            padding: 40px 40px;
            text-align: center;
            position: relative;
        }

        .email-header::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: url("data:image/svg+xml,%3Csvg width='60' height='60' viewBox='0 0 60 60' xmlns='http://www.w3.org/2000/svg'%3E%3Cg fill='none' fill-rule='evenodd'%3E%3Cg fill='%23ffffff' fill-opacity='0.05'%3E%3Cpath d='M36 34v-4h-2v4h-4v2h4v4h2v-4h4v-2h-4zm0-30V0h-2v4h-4v2h4v4h2V6h4V4h-4zM6 34v-4H4v4H0v2h4v4h2v-4h4v-2H6zM6 4V0H4v4H0v2h4v4h2V6h4V4H6z'/%3E%3C/g%3E%3C/g%3E%3C/svg%3E");
            opacity: 0.3;
        }

        .header-content {
            position: relative;
            z-index: 1;
        }

        .academy-logo {
            width: 80px;
            height: 80px;
            background-color: rgba(255, 255, 255, 0.25);
            border-radius: 16px;
            margin: 0 auto 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            backdrop-filter: blur(10px);
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
        }

        .academy-logo img {
            max-width: 56px;
            max-height: 56px;
            border-radius: 8px;
        }

        .academy-name {
            color: #ffffff;
            font-size: 28px;
            font-weight: 700;
            margin: 0;
            text-shadow: 0 2px 4px rgba(0, 0, 0, 0.15);
            letter-spacing: -0.5px;
        }

        .academy-tagline {
            color: rgba(255, 255, 255, 0.95);
            font-size: 16px;
            margin-top: 10px;
            font-weight: 500;
        }

        /* Body */
        .email-body {
            padding: 48px 40px;
        }

        .greeting {
            font-size: 24px;
            font-weight: 700;
            color: #1e293b;
            margin-bottom: 28px;
            line-height: 1.4;
        }

        .content {
            font-size: 18px;
            line-height: 1.9;
            color: #475569;
        }

        .content p {
            margin: 0 0 20px;
        }

        .content strong {
            color: #1e293b;
            font-weight: 600;
        }

        /* Button with dynamic colors */
        .button-container {
            text-align: center;
            margin: 36px 0;
        }

        .button {
            display: inline-block;
            background: linear-gradient(135deg, {{ $primaryLight }} 0%, {{ $primaryDark }} 100%);
            color: #ffffff !important;
            text-decoration: none;
            padding: 18px 48px;
            border-radius: 14px;
            font-size: 18px;
            font-weight: 700;
            box-shadow: 0 6px 24px {{ $shadowColor }}66;
            transition: all 0.3s ease;
            letter-spacing: 0.3px;
        }

        .button:hover {
            box-shadow: 0 8px 32px {{ $shadowColor }}80;
            transform: translateY(-2px);
        }

        /* Info box with dynamic colors */
        .info-box {
            background-color: {{ $primaryBg }};
            border-right: 5px solid {{ $primaryLight }};
            padding: 20px 24px;
            border-radius: 12px;
            margin: 28px 0;
        }

        .info-box p {
            margin: 0;
            color: {{ $primaryTextDark }};
            font-size: 16px;
            line-height: 1.7;
        }

        .info-box strong {
            color: {{ $primaryDarker }};
        }

        /* Warning box */
        .warning-box {
            background-color: #fef3c7;
            border-right: 5px solid #f59e0b;
            padding: 20px 24px;
            border-radius: 12px;
            margin: 28px 0;
        }

        .warning-box p {
            margin: 0;
            color: #92400e;
            font-size: 16px;
            line-height: 1.7;
        }

        /* Success box */
        .success-box {
            background-color: #ecfdf5;
            border-right: 5px solid #10b981;
            padding: 20px 24px;
            border-radius: 12px;
            margin: 28px 0;
        }

        .success-box p {
            margin: 0;
            color: #065f46;
            font-size: 16px;
            line-height: 1.7;
        }

        /* Code/Key display */
        .code-box {
            background: linear-gradient(135deg, {{ $primaryBg }} 0%, {{ $primaryBgMedium }} 100%);
            border: 2px dashed {{ $primaryLight }};
            border-radius: 14px;
            padding: 24px;
            text-align: center;
            margin: 28px 0;
        }

        .code-box .code {
            font-size: 32px;
            font-weight: 700;
            color: {{ $primaryDark }};
            letter-spacing: 4px;
            font-family: 'Courier New', monospace;
        }

        .code-box .label {
            font-size: 14px;
            color: {{ $primaryText }};
            margin-top: 10px;
        }

        /* Divider */
        .divider {
            height: 1px;
            background: linear-gradient(to left, transparent, #e2e8f0, transparent);
            margin: 32px 0;
        }

        /* Footer */
        .email-footer {
            background-color: #f8fafc;
            padding: 32px 40px;
            text-align: center;
            border-top: 1px solid #e2e8f0;
        }

        .footer-text {
            font-size: 15px;
            color: #64748b;
            margin: 0 0 12px;
            line-height: 1.6;
        }

        .footer-links {
            margin-top: 20px;
        }

        .footer-links a {
            color: {{ $primaryLight }};
            text-decoration: none;
            margin: 0 16px;
            font-size: 15px;
            font-weight: 500;
        }

        .footer-links a:hover {
            color: {{ $primaryDark }};
            text-decoration: underline;
        }

        .copyright {
            font-size: 14px;
            color: #94a3b8;
            margin-top: 20px;
        }

        /* Highlight text */
        .highlight {
            color: {{ $primaryDark }};
            font-weight: 600;
        }

        /* List styles */
        .content ul, .content ol {
            margin: 20px 0;
            padding-right: 24px;
        }

        .content li {
            margin-bottom: 12px;
            font-size: 17px;
        }

        /* Responsive - Mobile First Approach */
        @media only screen and (max-width: 600px) {
            .email-wrapper {
                padding: 16px 12px;
            }

            .email-container {
                border-radius: 16px;
            }

            .email-header {
                padding: 32px 24px;
            }

            .academy-logo {
                width: 72px;
                height: 72px;
            }

            .academy-logo img {
                max-width: 48px;
                max-height: 48px;
            }

            .academy-name {
                font-size: 24px;
            }

            .academy-tagline {
                font-size: 15px;
            }

            .email-body {
                padding: 32px 24px;
            }

            .greeting {
                font-size: 22px;
                margin-bottom: 24px;
            }

            .content {
                font-size: 17px;
                line-height: 1.85;
            }

            .content p {
                margin: 0 0 18px;
            }

            .button {
                display: block;
                padding: 18px 32px;
                font-size: 17px;
                border-radius: 12px;
            }

            .button-container {
                margin: 32px 0;
            }

            .info-box, .warning-box, .success-box {
                padding: 18px 20px;
                margin: 24px 0;
            }

            .info-box p, .warning-box p, .success-box p {
                font-size: 15px;
            }

            .code-box {
                padding: 20px;
            }

            .code-box .code {
                font-size: 26px;
                letter-spacing: 3px;
            }

            .email-footer {
                padding: 28px 24px;
            }

            .footer-text {
                font-size: 14px;
            }

            .footer-links a {
                display: block;
                margin: 8px 0;
                font-size: 14px;
            }

            .footer-links span {
                display: none;
            }

            .copyright {
                font-size: 13px;
            }

            .content ul, .content ol {
                padding-right: 20px;
            }

            .content li {
                font-size: 16px;
            }
        }

        /* Dark mode support for email clients that support it */
        @media (prefers-color-scheme: dark) {
            .email-wrapper {
                background-color: #1e293b !important;
            }

            .email-container {
                background-color: #0f172a !important;
            }

            .email-body {
                background-color: #0f172a !important;
            }

            .greeting {
                color: #f1f5f9 !important;
            }

            .content {
                color: #cbd5e1 !important;
            }

            .content strong {
                color: #f1f5f9 !important;
            }

            .email-footer {
                background-color: #1e293b !important;
                border-top-color: #334155 !important;
            }

            .footer-text {
                color: #94a3b8 !important;
            }

            .copyright {
                color: #64748b !important;
            }
        }
    </style>
</head>
<body>
    <div class="email-wrapper">
        <table class="email-container" width="100%" cellpadding="0" cellspacing="0" role="presentation" style="max-width: 600px; margin: 0 auto;">
            <!-- Header -->
            <tr>
                <td class="email-header">
                    <div class="header-content">
                        @if($academy->logo_url)
                        <div class="academy-logo" style="display: table; margin: 0 auto 20px;">
                            <img src="{{ $academy->logo_url }}" alt="{{ $academy->name }}" style="display: block;">
                        </div>
                        @endif
                        <h1 class="academy-name">{{ $academy->name }}</h1>
                        @if($academy->tagline ?? false)
                        <p class="academy-tagline">{{ $academy->tagline }}</p>
                        @endif
                    </div>
                </td>
            </tr>

            <!-- Body -->
            <tr>
                <td class="email-body">
                    @yield('content')
                </td>
            </tr>

            <!-- Footer -->
            <tr>
                <td class="email-footer">
                    <p class="footer-text">
                        هذا البريد الإلكتروني مرسل من {{ $academy->name }}
                    </p>

                    @if($academy->email || $academy->phone)
                    <div class="footer-links">
                        @if($academy->email)
                        <a href="mailto:{{ $academy->email }}">{{ $academy->email }}</a>
                        @endif
                        @if($academy->email && $academy->phone)
                        <span style="color: #94a3b8;">|</span>
                        @endif
                        @if($academy->phone)
                        <a href="tel:{{ $academy->phone }}">{{ $academy->phone }}</a>
                        @endif
                    </div>
                    @endif

                    <p class="copyright">
                        &copy; {{ date('Y') }} {{ $academy->name }}. جميع الحقوق محفوظة.
                    </p>
                </td>
            </tr>
        </table>
    </div>
</body>
</html>
