@php
    $subdomain = request()->route('subdomain') ?? auth()->user()->academy?->subdomain ?? 'itqan-academy';
@endphp

<x-layouts.parent-layout title="تفاصيل الدفع">
    <div class="space-y-6">
        <!-- Back Button -->
        <div>
            <a href="{{ route('parent.payments.index', ['subdomain' => $subdomain]) }}" class="inline-flex items-center text-blue-600 hover:text-blue-700 font-bold">
                <i class="ri-arrow-right-line ml-2"></i>
                العودة إلى المدفوعات
            </a>
        </div>

        <!-- Payment Header -->
        <div class="bg-white rounded-lg shadow p-6">
            <div class="flex items-start justify-between">
                <div class="flex items-start space-x-4 space-x-reverse">
                    <div class="bg-{{ $payment->status === 'paid' ? 'green' : ($payment->status === 'pending' ? 'yellow' : 'red') }}-100 rounded-lg p-4">
                        <i class="ri-money-dollar-circle-line text-3xl text-{{ $payment->status === 'paid' ? 'green' : ($payment->status === 'pending' ? 'yellow' : 'red') }}-600"></i>
                    </div>
                    <div>
                        <h1 class="text-3xl font-bold text-gray-900">فاتورة رقم #{{ $payment->transaction_id ?? $payment->id }}</h1>
                        <p class="text-gray-600 mt-1">{{ $payment->description ?? 'دفع اشتراك' }}</p>
                    </div>
                </div>
                <span class="px-4 py-2 text-sm font-bold rounded-full
                    {{ $payment->status === 'paid' ? 'bg-green-100 text-green-800' : '' }}
                    {{ $payment->status === 'pending' ? 'bg-yellow-100 text-yellow-800' : '' }}
                    {{ $payment->status === 'failed' ? 'bg-red-100 text-red-800' : '' }}
                    {{ $payment->status === 'refunded' ? 'bg-gray-100 text-gray-800' : '' }}">
                    {{ $payment->status === 'paid' ? 'مدفوع' : ($payment->status === 'pending' ? 'قيد الانتظار' : ($payment->status === 'failed' ? 'فاشل' : 'مسترد')) }}
                </span>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <!-- Main Content -->
            <div class="lg:col-span-2 space-y-6">
                <!-- Payment Details -->
                <div class="bg-white rounded-lg shadow">
                    <div class="p-6 border-b border-gray-200">
                        <h2 class="text-xl font-bold text-gray-900">تفاصيل الدفع</h2>
                    </div>
                    <div class="p-6 space-y-4">
                        <!-- Amount -->
                        <div class="flex items-center justify-between p-4 bg-gradient-to-r from-blue-50 to-blue-100 rounded-lg">
                            <span class="text-gray-700 font-bold">المبلغ الإجمالي</span>
                            <span class="text-3xl font-bold text-blue-600">
                                {{ number_format($payment->amount, 2) }} {{ $payment->currency ?? 'ر.س' }}
                            </span>
                        </div>

                        <!-- Student -->
                        <div class="flex items-center space-x-3 space-x-reverse">
                            <div class="bg-purple-100 rounded-lg p-3">
                                <i class="ri-user-smile-line text-xl text-purple-600"></i>
                            </div>
                            <div>
                                <p class="text-sm text-gray-500">الطالب</p>
                                <p class="font-bold text-gray-900">{{ $payment->user->name ?? '-' }}</p>
                            </div>
                        </div>

                        <!-- Payment Method -->
                        @if($payment->payment_method)
                            <div class="flex items-center space-x-3 space-x-reverse">
                                <div class="bg-green-100 rounded-lg p-3">
                                    <i class="ri-bank-card-line text-xl text-green-600"></i>
                                </div>
                                <div>
                                    <p class="text-sm text-gray-500">طريقة الدفع</p>
                                    <p class="font-bold text-gray-900">
                                        {{ $payment->payment_method === 'card' ? 'بطاقة ائتمانية' : ($payment->payment_method === 'bank' ? 'تحويل بنكي' : 'نقداً') }}
                                    </p>
                                </div>
                            </div>
                        @endif

                        <!-- Payment Date -->
                        <div class="flex items-center space-x-3 space-x-reverse">
                            <div class="bg-yellow-100 rounded-lg p-3">
                                <i class="ri-calendar-line text-xl text-yellow-600"></i>
                            </div>
                            <div>
                                <p class="text-sm text-gray-500">تاريخ الدفع</p>
                                <p class="font-bold text-gray-900">{{ $payment->created_at->format('l، Y/m/d - h:i A') }}</p>
                            </div>
                        </div>

                        <!-- Transaction ID -->
                        @if($payment->transaction_id)
                            <div class="flex items-center space-x-3 space-x-reverse">
                                <div class="bg-gray-100 rounded-lg p-3">
                                    <i class="ri-hashtag text-xl text-gray-600"></i>
                                </div>
                                <div>
                                    <p class="text-sm text-gray-500">رقم المعاملة</p>
                                    <p class="font-mono text-sm text-gray-900">{{ $payment->transaction_id }}</p>
                                </div>
                            </div>
                        @endif

                        <!-- Reference Number -->
                        @if($payment->reference_number)
                            <div class="flex items-center space-x-3 space-x-reverse">
                                <div class="bg-blue-100 rounded-lg p-3">
                                    <i class="ri-file-list-line text-xl text-blue-600"></i>
                                </div>
                                <div>
                                    <p class="text-sm text-gray-500">الرقم المرجعي</p>
                                    <p class="font-mono text-sm text-gray-900">{{ $payment->reference_number }}</p>
                                </div>
                            </div>
                        @endif
                    </div>
                </div>

                <!-- Related Subscription -->
                @if($payment->payable)
                    <div class="bg-white rounded-lg shadow">
                        <div class="p-6 border-b border-gray-200">
                            <h2 class="text-xl font-bold text-gray-900">الاشتراك المرتبط</h2>
                        </div>
                        <div class="p-6">
                            <div class="flex items-center justify-between p-4 bg-gray-50 rounded-lg">
                                <div class="flex items-center space-x-3 space-x-reverse">
                                    @if($payment->payable_type === 'App\\Models\\QuranSubscription')
                                        <i class="ri-book-read-line text-2xl text-green-600"></i>
                                        <div>
                                            <p class="font-bold text-gray-900">{{ $payment->payable->package->name ?? 'اشتراك قرآن' }}</p>
                                            <p class="text-sm text-gray-600">{{ $payment->payable->subscription_type === 'individual' ? 'فردي' : 'حلقة جماعية' }}</p>
                                        </div>
                                    @elseif($payment->payable_type === 'App\\Models\\AcademicSubscription')
                                        <i class="ri-book-2-line text-2xl text-blue-600"></i>
                                        <div>
                                            <p class="font-bold text-gray-900">{{ $payment->payable->subject_name ?? 'اشتراك أكاديمي' }}</p>
                                            <p class="text-sm text-gray-600">{{ $payment->payable->grade_level_name ?? 'مستوى' }}</p>
                                        </div>
                                    @else
                                        <i class="ri-video-line text-2xl text-purple-600"></i>
                                        <div>
                                            <p class="font-bold text-gray-900">{{ $payment->payable->recordedCourse?->title ?? $payment->payable->interactiveCourse?->title ?? 'دورة' }}</p>
                                            <p class="text-sm text-gray-600">دورة تعليمية</p>
                                        </div>
                                    @endif
                                </div>
                                <a href="{{ route('parent.subscriptions.show', [
                                    'subdomain' => $subdomain,
                                    'type' => $payment->payable_type === 'App\\Models\\QuranSubscription' ? 'quran' : ($payment->payable_type === 'App\\Models\\AcademicSubscription' ? 'academic' : 'course'),
                                    'id' => $payment->payable->id
                                ]) }}" class="text-blue-600 hover:text-blue-700">
                                    <i class="ri-arrow-left-line text-xl"></i>
                                </a>
                            </div>
                        </div>
                    </div>
                @endif

                <!-- Payment Notes -->
                @if($payment->notes)
                    <div class="bg-white rounded-lg shadow">
                        <div class="p-6 border-b border-gray-200">
                            <h2 class="text-xl font-bold text-gray-900">ملاحظات</h2>
                        </div>
                        <div class="p-6">
                            <p class="text-gray-900 whitespace-pre-line">{{ $payment->notes }}</p>
                        </div>
                    </div>
                @endif

                <!-- Failure Reason -->
                @if($payment->status === 'failed' && $payment->failure_reason)
                    <div class="bg-red-50 border border-red-200 rounded-lg p-6">
                        <div class="flex items-start space-x-3 space-x-reverse">
                            <i class="ri-error-warning-line text-2xl text-red-600"></i>
                            <div>
                                <p class="font-bold text-red-900 mb-1">سبب فشل الدفع</p>
                                <p class="text-red-800">{{ $payment->failure_reason }}</p>
                            </div>
                        </div>
                    </div>
                @endif

                <!-- Refund Information -->
                @if($payment->status === 'refunded' && $payment->refund_reason)
                    <div class="bg-gray-50 border border-gray-200 rounded-lg p-6">
                        <div class="flex items-start space-x-3 space-x-reverse">
                            <i class="ri-refund-line text-2xl text-gray-600"></i>
                            <div>
                                <p class="font-bold text-gray-900 mb-1">سبب الاسترداد</p>
                                <p class="text-gray-800">{{ $payment->refund_reason }}</p>
                                @if($payment->refunded_at)
                                    <p class="text-sm text-gray-600 mt-2">تاريخ الاسترداد: {{ $payment->refunded_at->format('Y/m/d h:i A') }}</p>
                                @endif
                            </div>
                        </div>
                    </div>
                @endif
            </div>

            <!-- Sidebar -->
            <div class="space-y-6">
                <!-- Quick Actions -->
                @if($payment->status === 'paid')
                    <div class="bg-white rounded-lg shadow p-6">
                        <h3 class="text-lg font-bold text-gray-900 mb-4">إجراءات سريعة</h3>
                        <div class="space-y-2">
                            <a href="{{ route('parent.payments.download-receipt', ['subdomain' => $subdomain, 'id' => $payment->id]) }}"
                               class="flex items-center justify-between p-3 bg-green-50 hover:bg-green-100 rounded-lg transition-colors">
                                <div class="flex items-center space-x-2 space-x-reverse">
                                    <i class="ri-download-line text-green-600"></i>
                                    <span class="text-gray-900 font-bold">تحميل الإيصال</span>
                                </div>
                                <i class="ri-arrow-left-line text-gray-400"></i>
                            </a>
                        </div>
                    </div>
                @endif

                <!-- Payment Timeline -->
                <div class="bg-white rounded-lg shadow p-6">
                    <h3 class="text-lg font-bold text-gray-900 mb-4">السجل الزمني</h3>
                    <div class="space-y-4">
                        <div class="flex items-start space-x-3 space-x-reverse">
                            <div class="bg-blue-100 rounded-full p-2">
                                <i class="ri-add-line text-blue-600"></i>
                            </div>
                            <div class="flex-1">
                                <p class="text-sm font-bold text-gray-900">تم إنشاء الدفع</p>
                                <p class="text-xs text-gray-500">{{ $payment->created_at->format('Y/m/d h:i A') }}</p>
                            </div>
                        </div>

                        @if($payment->status === 'paid' && $payment->paid_at)
                            <div class="flex items-start space-x-3 space-x-reverse">
                                <div class="bg-green-100 rounded-full p-2">
                                    <i class="ri-check-line text-green-600"></i>
                                </div>
                                <div class="flex-1">
                                    <p class="text-sm font-bold text-gray-900">تم الدفع بنجاح</p>
                                    <p class="text-xs text-gray-500">{{ $payment->paid_at->format('Y/m/d h:i A') }}</p>
                                </div>
                            </div>
                        @endif

                        @if($payment->status === 'failed')
                            <div class="flex items-start space-x-3 space-x-reverse">
                                <div class="bg-red-100 rounded-full p-2">
                                    <i class="ri-close-line text-red-600"></i>
                                </div>
                                <div class="flex-1">
                                    <p class="text-sm font-bold text-gray-900">فشل الدفع</p>
                                    <p class="text-xs text-gray-500">{{ $payment->updated_at->format('Y/m/d h:i A') }}</p>
                                </div>
                            </div>
                        @endif

                        @if($payment->status === 'refunded' && $payment->refunded_at)
                            <div class="flex items-start space-x-3 space-x-reverse">
                                <div class="bg-gray-100 rounded-full p-2">
                                    <i class="ri-refund-line text-gray-600"></i>
                                </div>
                                <div class="flex-1">
                                    <p class="text-sm font-bold text-gray-900">تم الاسترداد</p>
                                    <p class="text-xs text-gray-500">{{ $payment->refunded_at->format('Y/m/d h:i A') }}</p>
                                </div>
                            </div>
                        @endif
                    </div>
                </div>

                <!-- Related Links -->
                <div class="bg-white rounded-lg shadow p-6">
                    <h3 class="text-lg font-bold text-gray-900 mb-4">روابط ذات صلة</h3>
                    <div class="space-y-2">
                        <a href="{{ route('parent.payments.index', ['subdomain' => $subdomain]) }}" class="flex items-center justify-between p-3 bg-gray-50 hover:bg-gray-100 rounded-lg transition-colors">
                            <div class="flex items-center space-x-2 space-x-reverse">
                                <i class="ri-file-list-line text-blue-600"></i>
                                <span class="text-gray-900 font-bold">جميع المدفوعات</span>
                            </div>
                            <i class="ri-arrow-left-line text-gray-400"></i>
                        </a>
                        <a href="{{ route('parent.subscriptions.index', ['subdomain' => $subdomain]) }}" class="flex items-center justify-between p-3 bg-gray-50 hover:bg-gray-100 rounded-lg transition-colors">
                            <div class="flex items-center space-x-2 space-x-reverse">
                                <i class="ri-bookmark-line text-blue-600"></i>
                                <span class="text-gray-900 font-bold">الاشتراكات</span>
                            </div>
                            <i class="ri-arrow-left-line text-gray-400"></i>
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-layouts.parent-layout>
