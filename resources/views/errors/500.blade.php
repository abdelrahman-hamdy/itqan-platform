<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" dir="{{ app()->getLocale() === 'ar' ? 'rtl' : 'ltr' }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ app()->getLocale() === 'ar' ? '500 - خطأ في الخادم' : '500 - Server Error' }}</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Tajawal:wght@400;500;700&display=swap" rel="stylesheet">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Tajawal', sans-serif;
            background: linear-gradient(135deg, #fef2f2 0%, #fee2e2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        .container {
            text-align: center;
            max-width: 500px;
        }
        .error-code {
            font-size: 120px;
            font-weight: 700;
            background: linear-gradient(135deg, #ef4444 0%, #b91c1c 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            line-height: 1;
            margin-bottom: 20px;
        }
        .error-title {
            font-size: 24px;
            color: #1f2937;
            margin-bottom: 12px;
            font-weight: 600;
        }
        .error-message {
            color: #6b7280;
            font-size: 16px;
            margin-bottom: 30px;
            line-height: 1.6;
        }
        .btn {
            display: inline-block;
            padding: 12px 32px;
            background: linear-gradient(135deg, #ef4444 0%, #b91c1c 100%);
            color: white;
            text-decoration: none;
            border-radius: 12px;
            font-weight: 500;
            transition: transform 0.2s, box-shadow 0.2s;
            margin: 0 8px;
        }
        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(239, 68, 68, 0.3);
        }
        .btn-secondary {
            background: #6b7280;
        }
        .btn-secondary:hover {
            box-shadow: 0 10px 20px rgba(107, 114, 128, 0.3);
        }
        .icon {
            font-size: 80px;
            margin-bottom: 20px;
        }
        .buttons {
            display: flex;
            justify-content: center;
            flex-wrap: wrap;
            gap: 12px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="icon">&#9888;&#65039;</div>
        <div class="error-code">500</div>
        <h1 class="error-title">
            {{ app()->getLocale() === 'ar' ? 'خطأ في الخادم' : 'Server Error' }}
        </h1>
        <p class="error-message">
            {{ app()->getLocale() === 'ar'
                ? 'عذراً، حدث خطأ غير متوقع. فريقنا يعمل على حل المشكلة.'
                : 'Sorry, something went wrong on our end. Our team has been notified.' }}
        </p>
        <div class="buttons">
            <a href="{{ url('/') }}" class="btn">
                {{ app()->getLocale() === 'ar' ? 'العودة للرئيسية' : 'Back to Home' }}
            </a>
            <a href="javascript:location.reload()" class="btn btn-secondary">
                {{ app()->getLocale() === 'ar' ? 'إعادة المحاولة' : 'Try Again' }}
            </a>
        </div>
    </div>
</body>
</html>
