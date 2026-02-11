<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ __('payments.success.page_title') }}</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Tajawal:wght@400;500;700&display=swap" rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Tajawal', sans-serif;
            background: linear-gradient(135deg, #f5f7fa 0%, #e4e8ec 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }

        .container {
            background: white;
            border-radius: 16px;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.1);
            max-width: 600px;
            width: 100%;
            padding: 40px;
            text-align: center;
        }

        .success-icon {
            width: 80px;
            height: 80px;
            margin: 0 auto 24px;
            background: linear-gradient(135deg, #10b981 0%, #059669 100%);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            animation: scaleIn 0.5s ease-out;
        }

        .success-icon svg {
            width: 40px;
            height: 40px;
            stroke: white;
            stroke-width: 3;
            fill: none;
            stroke-linecap: round;
            stroke-linejoin: round;
        }

        @keyframes scaleIn {
            from {
                transform: scale(0);
            }
            to {
                transform: scale(1);
            }
        }

        .title {
            font-size: 28px;
            font-weight: 700;
            color: #1f2937;
            margin-bottom: 12px;
        }

        .message {
            font-size: 16px;
            color: #6b7280;
            margin-bottom: 32px;
            line-height: 1.6;
        }

        .payment-details {
            background: #f9fafb;
            border-radius: 12px;
            padding: 24px;
            margin-bottom: 32px;
            text-align: right;
        }

        .detail-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 12px 0;
            border-bottom: 1px solid #e5e7eb;
        }

        .detail-row:last-child {
            border-bottom: none;
        }

        .detail-label {
            color: #6b7280;
            font-size: 14px;
        }

        .detail-value {
            color: #1f2937;
            font-weight: 600;
            font-size: 16px;
        }

        .amount {
            color: #10b981;
            font-size: 24px;
            font-weight: 700;
        }

        .btn {
            display: inline-block;
            padding: 14px 32px;
            background: linear-gradient(135deg, #0ea5e9 0%, #0369a1 100%);
            color: white;
            text-decoration: none;
            border-radius: 8px;
            font-weight: 600;
            font-size: 16px;
            transition: transform 0.2s, box-shadow 0.2s;
            border: none;
            cursor: pointer;
        }

        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 25px rgba(14, 165, 233, 0.3);
        }

        .btn-secondary {
            background: #f3f4f6;
            color: #4b5563;
            margin-left: 12px;
        }

        .btn-secondary:hover {
            background: #e5e7eb;
            box-shadow: none;
        }

        .actions {
            display: flex;
            justify-content: center;
            gap: 12px;
            flex-wrap: wrap;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="success-icon">
            <svg viewBox="0 0 24 24">
                <polyline points="20 6 9 17 4 12"></polyline>
            </svg>
        </div>

        <h1 class="title">{{ __('payments.success.title') }}</h1>
        <p class="message">{{ __('payments.success.message') }}</p>

        <div class="payment-details">
            <div class="detail-row">
                <span class="detail-label">{{ __('payments.success.payment_id') }}</span>
                <span class="detail-value">#{{ $payment->id }}</span>
            </div>
            <div class="detail-row">
                <span class="detail-label">{{ __('payments.success.date') }}</span>
                <span class="detail-value">{{ $payment->created_at->format('Y-m-d H:i') }}</span>
            </div>
            <div class="detail-row">
                <span class="detail-label">{{ __('payments.success.payment_method') }}</span>
                <span class="detail-value">{{ __('payments.methods.' . $payment->payment_method) }}</span>
            </div>
            @if($payment->gateway_transaction_id)
            <div class="detail-row">
                <span class="detail-label">{{ __('payments.success.transaction_id') }}</span>
                <span class="detail-value">{{ $payment->gateway_transaction_id }}</span>
            </div>
            @endif
            <div class="detail-row">
                <span class="detail-label">{{ __('payments.success.amount') }}</span>
                <span class="detail-value amount">{{ number_format($payment->amount, 2) }} {{ $payment->currency }}</span>
            </div>
        </div>

        <div class="actions">
            @if($payment->payable_type === 'App\\Models\\QuranSubscription' || $payment->payable_type === 'App\\Models\\AcademicSubscription')
                <a href="{{ url('/subscriptions') }}" class="btn">
                    {{ __('payments.success.view_subscriptions') }}
                </a>
            @elseif($payment->payable_type === 'App\\Models\\CourseSubscription')
                <a href="{{ url('/courses') }}" class="btn">
                    {{ __('payments.success.view_courses') }}
                </a>
            @else
                <a href="{{ url('/subscriptions') }}" class="btn">
                    {{ __('payments.success.view_subscriptions') }}
                </a>
            @endif

            @if($payment->receipt_url)
            <a href="{{ $payment->receipt_url }}" class="btn btn-secondary" download>
                {{ __('payments.success.download_receipt') }}
            </a>
            @endif
        </div>
    </div>
</body>
</html>
