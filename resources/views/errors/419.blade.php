<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" dir="{{ app()->getLocale() === 'ar' ? 'rtl' : 'ltr' }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ app()->getLocale() === 'ar' ? '419 - انتهت صلاحية الجلسة' : '419 - Session Expired' }}</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Tajawal:wght@400;500;700&display=swap" rel="stylesheet">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Tajawal', sans-serif;
            background: linear-gradient(135deg, #ede9fe 0%, #ddd6fe 100%);
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
            background: linear-gradient(135deg, #8b5cf6 0%, #7c3aed 100%);
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
        .buttons {
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 12px;
        }
        .btn {
            display: inline-block;
            padding: 12px 32px;
            background: linear-gradient(135deg, #8b5cf6 0%, #7c3aed 100%);
            color: white;
            text-decoration: none;
            border-radius: 12px;
            font-weight: 500;
            transition: transform 0.2s, box-shadow 0.2s;
            border: none;
            cursor: pointer;
            font-family: 'Tajawal', sans-serif;
            font-size: 16px;
        }
        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(139, 92, 246, 0.3);
        }
        .btn-secondary {
            background: transparent;
            color: #7c3aed;
            border: 2px solid #7c3aed;
            padding: 10px 30px;
        }
        .btn-secondary:hover {
            background: rgba(139, 92, 246, 0.1);
            box-shadow: none;
            transform: none;
        }
        .icon {
            font-size: 80px;
            margin-bottom: 20px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="icon">&#9203;</div>
        <div class="error-code">419</div>
        <h1 class="error-title">
            {{ app()->getLocale() === 'ar' ? 'انتهت صلاحية الجلسة' : 'Session Expired' }}
        </h1>
        <p class="error-message">
            {{ app()->getLocale() === 'ar'
                ? 'تم تحديث جلستك لأسباب أمنية. يرجى العودة للصفحة السابقة والمحاولة مرة أخرى.'
                : 'Your session was refreshed for security. Please go back and try again.' }}
        </p>
        <div class="buttons">
            <button onclick="history.length > 1 ? history.back() : (window.location.href = '/')" class="btn">
                {{ app()->getLocale() === 'ar' ? 'العودة والمحاولة مرة أخرى' : 'Go Back & Try Again' }}
            </button>
            @auth
                <a href="{{ url(\App\Enums\UserType::from(auth()->user()->user_type)->getDashboardRoute()) }}" class="btn btn-secondary">
                    {{ app()->getLocale() === 'ar' ? 'الذهاب للوحة التحكم' : 'Go to Dashboard' }}
                </a>
            @else
                <a href="{{ url('/') }}" class="btn btn-secondary">
                    {{ app()->getLocale() === 'ar' ? 'العودة للرئيسية' : 'Back to Home' }}
                </a>
            @endauth
        </div>
    </div>
</body>
</html>
