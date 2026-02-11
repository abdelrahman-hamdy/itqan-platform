@php
    $academy = auth()->user()->academy;
    $subdomain = request()->route('subdomain') ?? $academy->subdomain ?? 'itqan-academy';
@endphp

<x-layouts.authenticated role="student" title="تجديد الاشتراك يدوياً">
    <div class="max-w-3xl mx-auto py-8">
        {{-- Header --}}
        <div class="mb-8">
            <h1 class="text-2xl font-bold text-gray-900 dark:text-white">
                تجديد الاشتراك يدوياً
            </h1>
            <p class="mt-2 text-gray-600 dark:text-gray-400">
                فشل التجديد التلقائي. يرجى إكمال الدفع يدوياً لتجنب انقطاع الخدمة.
            </p>
        </div>

        {{-- Grace Period Alert --}}
        <div class="mb-6 rounded-lg border-2 border-amber-500 bg-amber-50 p-4 dark:border-amber-600 dark:bg-amber-900/20">
            <div class="flex items-start gap-3">
                <svg class="h-6 w-6 text-amber-600 dark:text-amber-500 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                <div>
                    <h3 class="font-semibold text-amber-800 dark:text-amber-400">
                        فترة السماح تنتهي في: {{ $gracePeriodExpiresAt->diffForHumans() }}
                    </h3>
                    <p class="mt-1 text-sm text-amber-700 dark:text-amber-300">
                        التاريخ: {{ $gracePeriodExpiresAt->format('Y-m-d H:i') }}
                    </p>
                </div>
            </div>
        </div>

        {{-- Subscription Info --}}
        <div class="mb-6 rounded-lg border border-gray-200 bg-white p-6 dark:border-gray-700 dark:bg-gray-800">
            <h2 class="mb-4 text-lg font-semibold text-gray-900 dark:text-white">
                تفاصيل الاشتراك
            </h2>
            <dl class="space-y-2">
                <div class="flex justify-between">
                    <dt class="text-gray-600 dark:text-gray-400">رمز الاشتراك:</dt>
                    <dd class="font-medium text-gray-900 dark:text-white">{{ $subscription->subscription_code }}</dd>
                </div>
                <div class="flex justify-between">
                    <dt class="text-gray-600 dark:text-gray-400">المبلغ المطلوب:</dt>
                    <dd class="text-xl font-bold text-gray-900 dark:text-white">
                        {{ number_format($renewalAmount, 2) }} {{ $currency }}
                    </dd>
                </div>
            </dl>
        </div>

        {{-- Payment Form --}}
        <form method="POST" action="{{ route('student.subscriptions.manual-renewal.process', ['subdomain' => $subdomain, 'type' => $subscription instanceof \App\Models\QuranSubscription ? 'quran' : 'academic', 'id' => $subscription->id]) }}" class="space-y-6">
            @csrf

            {{-- Payment Gateway Selection --}}
            <div class="rounded-lg border border-gray-200 bg-white p-6 dark:border-gray-700 dark:bg-gray-800">
                <h2 class="mb-4 text-lg font-semibold text-gray-900 dark:text-white">
                    اختر طريقة الدفع
                </h2>

                <div class="space-y-3">
                    {{-- Paymob --}}
                    <label class="flex items-center gap-3 rounded-lg border-2 border-gray-200 p-4 cursor-pointer hover:border-blue-500 dark:border-gray-700 dark:hover:border-blue-500">
                        <input type="radio" name="payment_gateway" value="paymob" class="h-4 w-4 text-blue-600" checked required>
                        <div class="flex-1">
                            <div class="font-medium text-gray-900 dark:text-white">Paymob</div>
                            <div class="text-sm text-gray-600 dark:text-gray-400">بطاقة ائتمانية / بطاقة مدى</div>
                        </div>
                    </label>

                    {{-- EasyKash --}}
                    <label class="flex items-center gap-3 rounded-lg border-2 border-gray-200 p-4 cursor-pointer hover:border-blue-500 dark:border-gray-700 dark:hover:border-blue-500">
                        <input type="radio" name="payment_gateway" value="easykash" class="h-4 w-4 text-blue-600" required>
                        <div class="flex-1">
                            <div class="font-medium text-gray-900 dark:text-white">EasyKash</div>
                            <div class="text-sm text-gray-600 dark:text-gray-400">محفظة إلكترونية</div>
                        </div>
                    </label>
                </div>

                @error('payment_gateway')
                    <p class="mt-2 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                @enderror
            </div>

            {{-- Submit Button --}}
            <div class="flex gap-4">
                <button type="submit" class="flex-1 rounded-lg bg-blue-600 px-6 py-3 font-medium text-white hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2">
                    متابعة الدفع
                </button>
                <a href="{{ route('student.subscriptions', ['subdomain' => $subdomain]) }}" class="rounded-lg border border-gray-300 px-6 py-3 font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-600 dark:text-gray-300 dark:hover:bg-gray-800">
                    إلغاء
                </a>
            </div>
        </form>
    </div>
</x-layouts.authenticated>
