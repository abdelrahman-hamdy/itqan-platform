<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" dir="{{ app()->getLocale() === 'ar' ? 'rtl' : 'ltr' }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ app()->getLocale() === 'ar' ? '429 - طلبات كثيرة جداً' : '429 - Too Many Requests' }}</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Tajawal:wght@400;500;700&display=swap" rel="stylesheet">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Tajawal', sans-serif;
            background: linear-gradient(135deg, #fce7f3 0%, #fbcfe8 100%);
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
            background: linear-gradient(135deg, #ec4899 0%, #db2777 100%);
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
            background: linear-gradient(135deg, #ec4899 0%, #db2777 100%);
            color: white;
            text-decoration: none;
            border-radius: 12px;
            font-weight: 500;
            transition: transform 0.2s, box-shadow 0.2s;
        }
        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(236, 72, 153, 0.3);
        }
        .icon {
            font-size: 80px;
            margin-bottom: 20px;
        }
        .timer {
            font-size: 14px;
            color: #9ca3af;
            margin-top: 20px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="icon">&#9889;</div>
        <div class="error-code">429</div>
        <h1 class="error-title">
            {{ app()->getLocale() === 'ar' ? 'طلبات كثيرة جداً' : 'Too Many Requests' }}
        </h1>
        <p class="error-message">
            {{ app()->getLocale() === 'ar'
                ? 'لقد تجاوزت الحد المسموح من الطلبات. يرجى الانتظار قليلاً ثم المحاولة مرة أخرى.'
                : 'You have exceeded the allowed number of requests. Please wait a moment and try again.' }}
        </p>
        <a href="javascript:location.reload()" class="btn">
            {{ app()->getLocale() === 'ar' ? 'إعادة المحاولة' : 'Try Again' }}
        </a>
        <p class="timer">
            {{ app()->getLocale() === 'ar' ? 'يمكنك المحاولة بعد دقيقة' : 'You can try again after a minute' }}
        </p>
    </div>
</body>
</html>
