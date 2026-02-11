@php
    $academy = auth()->user()->academy;
    $subdomain = request()->route('subdomain') ?? $academy->subdomain ?? 'itqan-academy';
@endphp

<x-layouts.authenticated role="student" title="انتهت فترة السماح">
    <div class="max-w-3xl mx-auto py-8">
        {{-- Header --}}
        <div class="mb-8 text-center">
            <div class="mx-auto mb-4 flex h-16 w-16 items-center justify-center rounded-full bg-red-100 dark:bg-red-900/20">
                <svg class="h-8 w-8 text-red-600 dark:text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
            </div>
            <h1 class="text-2xl font-bold text-gray-900 dark:text-white">
                انتهت فترة السماح
            </h1>
            <p class="mt-2 text-gray-600 dark:text-gray-400">
                لقد انتهت فترة السماح لهذا الاشتراك ولم يعد بالإمكان تجديده
            </p>
        </div>

        {{-- Expiry Alert --}}
        <div class="mb-6 rounded-lg border-2 border-red-500 bg-red-50 p-6 dark:border-red-600 dark:bg-red-900/20">
            <div class="flex items-start gap-3">
                <svg class="h-6 w-6 text-red-600 dark:text-red-500 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                <div class="flex-1">
                    <h3 class="font-semibold text-red-800 dark:text-red-400">
                        انتهت فترة السماح في: {{ $expiredAt->format('Y-m-d H:i') }}
                    </h3>
                    <p class="mt-2 text-sm text-red-700 dark:text-red-300">
                        {{ $expiredAt->diffForHumans() }}
                    </p>
                    <p class="mt-3 text-sm text-red-700 dark:text-red-300">
                        تم إلغاء الاشتراك تلقائياً بعد انتهاء فترة السماح. لاستئناف الخدمة، يرجى الاشتراك مرة أخرى.
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
                    <dt class="text-gray-600 dark:text-gray-400">الحالة:</dt>
                    <dd class="font-medium text-red-600 dark:text-red-400">
                        ملغي
                    </dd>
                </div>
                <div class="flex justify-between">
                    <dt class="text-gray-600 dark:text-gray-400">انتهى في:</dt>
                    <dd class="font-medium text-gray-900 dark:text-white">
                        {{ $subscription->ends_at?->format('Y-m-d') ?? 'غير محدد' }}
                    </dd>
                </div>
            </dl>
        </div>

        {{-- What Next Section --}}
        <div class="mb-6 rounded-lg border border-blue-200 bg-blue-50 p-6 dark:border-blue-800 dark:bg-blue-900/20">
            <h2 class="mb-3 text-lg font-semibold text-blue-900 dark:text-blue-300">
                ماذا يمكنك أن تفعل الآن؟
            </h2>
            <ul class="space-y-2 text-sm text-blue-800 dark:text-blue-300">
                <li class="flex items-start gap-2">
                    <svg class="h-5 w-5 flex-shrink-0 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                    </svg>
                    <span>اشترك في باقة جديدة لاستئناف الدروس</span>
                </li>
                <li class="flex items-start gap-2">
                    <svg class="h-5 w-5 flex-shrink-0 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                    </svg>
                    <span>راجع اشتراكاتك الأخرى إن وجدت</span>
                </li>
                <li class="flex items-start gap-2">
                    <svg class="h-5 w-5 flex-shrink-0 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                    </svg>
                    <span>تواصل مع الدعم إذا كنت تعتقد أن هذا خطأ</span>
                </li>
                <li class="flex items-start gap-2">
                    <svg class="h-5 w-5 flex-shrink-0 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                    </svg>
                    <span>أضف بطاقة دفع محفوظة لتجنب هذا الموقف في المستقبل</span>
                </li>
            </ul>
        </div>

        {{-- Actions --}}
        <div class="flex gap-4">
            <a href="{{ route('student.subscriptions', ['subdomain' => $subdomain]) }}" class="flex-1 rounded-lg bg-blue-600 px-6 py-3 text-center font-medium text-white hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2">
                عرض الاشتراكات
            </a>
            <a href="{{ route('student.payments', ['subdomain' => $subdomain]) }}" class="rounded-lg border border-gray-300 px-6 py-3 font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-600 dark:text-gray-300 dark:hover:bg-gray-800">
                إدارة طرق الدفع
            </a>
        </div>

        {{-- Help Section --}}
        <div class="mt-8 border-t border-gray-200 pt-6 dark:border-gray-700">
            <h3 class="mb-2 text-sm font-semibold text-gray-900 dark:text-white">
                لماذا تم إلغاء الاشتراك؟
            </h3>
            <p class="text-sm text-gray-600 dark:text-gray-400">
                فشل التجديد التلقائي للاشتراك بعد عدة محاولات، وتم منحك فترة سماح {{ config('payments.renewal.grace_period_days', 3) }} أيام للدفع يدوياً. لم يتم استكمال الدفع خلال هذه الفترة، لذا تم إلغاء الاشتراك تلقائياً.
            </p>
            <p class="mt-2 text-sm text-gray-600 dark:text-gray-400">
                لتجنب هذا الموقف في المستقبل، يرجى التأكد من:
            </p>
            <ul class="mt-2 list-inside list-disc space-y-1 text-sm text-gray-600 dark:text-gray-400">
                <li>إضافة بطاقة دفع محفوظة صالحة</li>
                <li>التأكد من وجود رصيد كافٍ في بطاقتك</li>
                <li>تحديث معلومات بطاقتك قبل انتهاء صلاحيتها</li>
                <li>الانتباه للإشعارات التي ترسل قبل موعد التجديد</li>
            </ul>
        </div>
    </div>
</x-layouts.authenticated>
