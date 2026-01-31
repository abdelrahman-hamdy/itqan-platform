<!DOCTYPE html>
<html dir="rtl" lang="ar">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>{{ $subject ?? $academy->name }}</title>
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
        }

        .email-wrapper {
            width: 100%;
            background-color: #f4f7fa;
            padding: 40px 0;
        }

        .email-container {
            max-width: 600px;
            margin: 0 auto;
            background-color: #ffffff;
            border-radius: 16px;
            overflow: hidden;
            box-shadow: 0 4px 24px rgba(0, 0, 0, 0.08);
        }

        /* Header */
        .email-header {
            background: linear-gradient(135deg, #0ea5e9 0%, #0284c7 100%);
            padding: 32px 40px;
            text-align: center;
        }

        .academy-logo {
            width: 64px;
            height: 64px;
            background-color: rgba(255, 255, 255, 0.2);
            border-radius: 12px;
            margin: 0 auto 16px;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .academy-logo img {
            max-width: 48px;
            max-height: 48px;
        }

        .academy-name {
            color: #ffffff;
            font-size: 24px;
            font-weight: 700;
            margin: 0;
            text-shadow: 0 1px 2px rgba(0, 0, 0, 0.1);
        }

        .academy-tagline {
            color: rgba(255, 255, 255, 0.9);
            font-size: 14px;
            margin-top: 8px;
        }

        /* Body */
        .email-body {
            padding: 40px;
        }

        .greeting {
            font-size: 20px;
            font-weight: 600;
            color: #1e293b;
            margin-bottom: 24px;
        }

        .content {
            font-size: 16px;
            line-height: 1.8;
            color: #475569;
        }

        .content p {
            margin: 0 0 16px;
        }

        /* Button */
        .button-container {
            text-align: center;
            margin: 32px 0;
        }

        .button {
            display: inline-block;
            background: linear-gradient(135deg, #0ea5e9 0%, #0284c7 100%);
            color: #ffffff !important;
            text-decoration: none;
            padding: 16px 40px;
            border-radius: 12px;
            font-size: 16px;
            font-weight: 600;
            box-shadow: 0 4px 14px rgba(14, 165, 233, 0.4);
            transition: all 0.3s ease;
        }

        .button:hover {
            box-shadow: 0 6px 20px rgba(14, 165, 233, 0.5);
        }

        /* Info box */
        .info-box {
            background-color: #f0f9ff;
            border-right: 4px solid #0ea5e9;
            padding: 16px 20px;
            border-radius: 8px;
            margin: 24px 0;
        }

        .info-box p {
            margin: 0;
            color: #0369a1;
            font-size: 14px;
        }

        /* Warning box */
        .warning-box {
            background-color: #fef3c7;
            border-right: 4px solid #f59e0b;
            padding: 16px 20px;
            border-radius: 8px;
            margin: 24px 0;
        }

        .warning-box p {
            margin: 0;
            color: #92400e;
            font-size: 14px;
        }

        /* Footer */
        .email-footer {
            background-color: #f8fafc;
            padding: 24px 40px;
            text-align: center;
            border-top: 1px solid #e2e8f0;
        }

        .footer-text {
            font-size: 13px;
            color: #64748b;
            margin: 0 0 8px;
        }

        .footer-links {
            margin-top: 16px;
        }

        .footer-links a {
            color: #0ea5e9;
            text-decoration: none;
            margin: 0 12px;
            font-size: 13px;
        }

        .social-links {
            margin-top: 16px;
        }

        .social-links a {
            display: inline-block;
            margin: 0 8px;
        }

        .copyright {
            font-size: 12px;
            color: #94a3b8;
            margin-top: 16px;
        }

        /* Responsive */
        @media only screen and (max-width: 600px) {
            .email-wrapper {
                padding: 20px 16px;
            }

            .email-header {
                padding: 24px 20px;
            }

            .academy-name {
                font-size: 20px;
            }

            .email-body {
                padding: 24px 20px;
            }

            .button {
                display: block;
                padding: 14px 24px;
            }

            .email-footer {
                padding: 20px;
            }
        }
    </style>
</head>
<body>
    <div class="email-wrapper">
        <table class="email-container" width="600" cellpadding="0" cellspacing="0" role="presentation">
            <!-- Header -->
            <tr>
                <td class="email-header">
                    @if($academy->logo_url)
                    <div class="academy-logo">
                        <img src="{{ $academy->logo_url }}" alt="{{ $academy->name }}">
                    </div>
                    @endif
                    <h1 class="academy-name">{{ $academy->name }}</h1>
                    @if($academy->tagline ?? false)
                    <p class="academy-tagline">{{ $academy->tagline }}</p>
                    @endif
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
                        @if($academy->phone)
                        <span style="color: #94a3b8;">|</span>
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
