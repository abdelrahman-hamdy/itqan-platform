@php
    $subdomain = request()->route('subdomain') ?? auth()->user()->academy?->subdomain ?? 'itqan-academy';
@endphp

<x-layouts.parent-layout :title="__('parent.payments.title')">
    <div class="space-y-4 md:space-y-6">
        <!-- Back Button -->
        <div>
            <a href="{{ route('parent.payments.index', ['subdomain' => $subdomain]) }}" class="min-h-[44px] inline-flex items-center text-blue-600 hover:text-blue-700 font-bold text-sm md:text-base">
                <i class="ri-arrow-right-line ms-1.5 md:ms-2"></i>
                {{ __('parent.payments.back_to_payments') }}
            </a>
        </div>

        <!-- Payment Header -->
        <div class="bg-white rounded-lg md:rounded-xl shadow p-4 md:p-6">
            <div class="flex flex-col sm:flex-row sm:items-start justify-between gap-3 md:gap-4">
                <div class="flex items-start gap-3 md:gap-4">
                    <div class="bg-{{ $payment->status === \App\Enums\PaymentStatus::COMPLETED ? 'green' : ($payment->status === \App\Enums\PaymentStatus::PENDING ? 'yellow' : 'red') }}-100 rounded-lg p-3 md:p-4 flex-shrink-0">
                        <i class="ri-money-dollar-circle-line text-2xl md:text-3xl text-{{ $payment->status === \App\Enums\PaymentStatus::COMPLETED ? 'green' : ($payment->status === \App\Enums\PaymentStatus::PENDING ? 'yellow' : 'red') }}-600"></i>
                    </div>
                    <div class="min-w-0">
                        <h1 class="text-lg sm:text-xl md:text-3xl font-bold text-gray-900 break-words">{{ __('parent.payments.invoice_number', ['number' => $payment->transaction_id ?? $payment->id]) }}</h1>
                        <p class="text-sm md:text-base text-gray-600 mt-0.5 md:mt-1">{{ $payment->description ?? __('parent.payments.subscription_payment') }}</p>
                    </div>
                </div>
                <span class="self-start px-3 md:px-4 py-1.5 md:py-2 text-xs md:text-sm font-bold rounded-full flex-shrink-0
                    {{ $payment->status === \App\Enums\PaymentStatus::COMPLETED ? 'bg-green-100 text-green-800' : '' }}
                    {{ $payment->status === \App\Enums\PaymentStatus::PENDING ? 'bg-yellow-100 text-yellow-800' : '' }}
                    {{ $payment->status === \App\Enums\PaymentStatus::FAILED ? 'bg-red-100 text-red-800' : '' }}
                    {{ $payment->status === \App\Enums\PaymentStatus::REFUNDED ? 'bg-gray-100 text-gray-800' : '' }}">
                    {{ $payment->status->label() }}
                </span>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-4 md:gap-6">
            <!-- Main Content -->
            <div class="lg:col-span-2 space-y-4 md:space-y-6">
                <!-- Payment Details -->
                <div class="bg-white rounded-lg md:rounded-xl shadow">
                    <div class="p-4 md:p-6 border-b border-gray-200">
                        <h2 class="text-base md:text-xl font-bold text-gray-900">{{ __('parent.payments.payment_details') }}</h2>
                    </div>
                    <div class="p-4 md:p-6 space-y-3 md:space-y-4">
                        <!-- Amount -->
                        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2 p-3 md:p-4 bg-gradient-to-r from-blue-50 to-blue-100 rounded-lg">
                            <span class="text-sm md:text-base text-gray-700 font-bold">{{ __('parent.payments.total_amount') }}</span>
                            <span class="text-xl md:text-3xl font-bold text-blue-600">
                                {{ number_format($payment->amount, 2) }} {{ $payment->currency ?? 'ر.س' }}
                            </span>
                        </div>

                        <!-- Student -->
                        <div class="flex items-center gap-2.5 md:gap-3">
                            <div class="bg-purple-100 rounded-lg p-2.5 md:p-3 flex-shrink-0">
                                <i class="ri-user-smile-line text-lg md:text-xl text-purple-600"></i>
                            </div>
                            <div class="min-w-0">
                                <p class="text-xs md:text-sm text-gray-500">{{ __('parent.payments.student') }}</p>
                                <p class="font-bold text-sm md:text-base text-gray-900 truncate">{{ $payment->user->name ?? '-' }}</p>
                            </div>
                        </div>

                        <!-- Payment Method -->
                        @if($payment->payment_method)
                            <div class="flex items-center gap-2.5 md:gap-3">
                                <div class="bg-green-100 rounded-lg p-2.5 md:p-3 flex-shrink-0">
                                    <i class="ri-bank-card-line text-lg md:text-xl text-green-600"></i>
                                </div>
                                <div class="min-w-0">
                                    <p class="text-xs md:text-sm text-gray-500">{{ __('parent.payments.payment_method') }}</p>
                                    <p class="font-bold text-sm md:text-base text-gray-900">
                                        {{ __('parent.payments.payment_methods.' . $payment->payment_method) }}
                                    </p>
                                </div>
                            </div>
                        @endif

                        <!-- Payment Date -->
                        <div class="flex items-center gap-2.5 md:gap-3">
                            <div class="bg-yellow-100 rounded-lg p-2.5 md:p-3 flex-shrink-0">
                                <i class="ri-calendar-line text-lg md:text-xl text-yellow-600"></i>
                            </div>
                            <div class="min-w-0">
                                <p class="text-xs md:text-sm text-gray-500">{{ __('parent.payments.payment_date') }}</p>
                                <p class="font-bold text-sm md:text-base text-gray-900">{{ $payment->created_at->format('l، Y/m/d - h:i A') }}</p>
                            </div>
                        </div>

                        <!-- Transaction ID -->
                        @if($payment->transaction_id)
                            <div class="flex items-center gap-2.5 md:gap-3">
                                <div class="bg-gray-100 rounded-lg p-2.5 md:p-3 flex-shrink-0">
                                    <i class="ri-hashtag text-lg md:text-xl text-gray-600"></i>
                                </div>
                                <div class="min-w-0">
                                    <p class="text-xs md:text-sm text-gray-500">{{ __('parent.payments.transaction_id') }}</p>
                                    <p class="font-mono text-xs md:text-sm text-gray-900 break-all">{{ $payment->transaction_id }}</p>
                                </div>
                            </div>
                        @endif

                        <!-- Reference Number -->
                        @if($payment->reference_number)
                            <div class="flex items-center gap-2.5 md:gap-3">
                                <div class="bg-blue-100 rounded-lg p-2.5 md:p-3 flex-shrink-0">
                                    <i class="ri-file-list-line text-lg md:text-xl text-blue-600"></i>
                                </div>
                                <div class="min-w-0">
                                    <p class="text-xs md:text-sm text-gray-500">{{ __('parent.payments.reference_number') }}</p>
                                    <p class="font-mono text-xs md:text-sm text-gray-900 break-all">{{ $payment->reference_number }}</p>
                                </div>
                            </div>
                        @endif
                    </div>
                </div>

                <!-- Related Subscription -->
                @if($payment->payable)
                    <div class="bg-white rounded-lg md:rounded-xl shadow">
                        <div class="p-4 md:p-6 border-b border-gray-200">
                            <h2 class="text-base md:text-xl font-bold text-gray-900">{{ __('parent.payments.related_subscription') }}</h2>
                        </div>
                        <div class="p-4 md:p-6">
                            <div class="flex items-center justify-between gap-3 p-3 md:p-4 bg-gray-50 rounded-lg">
                                <div class="flex items-center gap-2.5 md:gap-3 min-w-0">
                                    @if($payment->payable_type === 'App\\Models\\QuranSubscription')
                                        <i class="ri-book-read-line text-xl md:text-2xl text-green-600 flex-shrink-0"></i>
                                        <div class="min-w-0">
                                            <p class="font-bold text-sm md:text-base text-gray-900 truncate">{{ $payment->payable->package->name ?? __('parent.payments.quran_subscription') }}</p>
                                            <p class="text-xs md:text-sm text-gray-600">{{ $payment->payable->subscription_type === 'individual' ? __('parent.payments.individual') : __('parent.payments.group_circle') }}</p>
                                        </div>
                                    @elseif($payment->payable_type === 'App\\Models\\AcademicSubscription')
                                        <i class="ri-book-2-line text-xl md:text-2xl text-blue-600 flex-shrink-0"></i>
                                        <div class="min-w-0">
                                            <p class="font-bold text-sm md:text-base text-gray-900 truncate">{{ $payment->payable->subject_name ?? __('parent.payments.academic_subscription') }}</p>
                                            <p class="text-xs md:text-sm text-gray-600">{{ $payment->payable->grade_level_name ?? __('parent.common.level') }}</p>
                                        </div>
                                    @else
                                        <i class="ri-video-line text-xl md:text-2xl text-purple-600 flex-shrink-0"></i>
                                        <div class="min-w-0">
                                            <p class="font-bold text-sm md:text-base text-gray-900 truncate">{{ $payment->payable->recordedCourse?->title ?? $payment->payable->interactiveCourse?->title ?? __('parent.common.course') }}</p>
                                            <p class="text-xs md:text-sm text-gray-600">{{ __('parent.payments.educational_course') }}</p>
                                        </div>
                                    @endif
                                </div>
                                <a href="{{ route('parent.subscriptions.show', [
                                    'subdomain' => $subdomain,
                                    'type' => $payment->payable_type === 'App\\Models\\QuranSubscription' ? 'quran' : ($payment->payable_type === 'App\\Models\\AcademicSubscription' ? 'academic' : 'course'),
                                    'id' => $payment->payable->id
                                ]) }}" class="min-h-[44px] min-w-[44px] flex items-center justify-center text-blue-600 hover:text-blue-700 flex-shrink-0">
                                    <i class="ri-arrow-left-line text-xl"></i>
                                </a>
                            </div>
                        </div>
                    </div>
                @endif

                <!-- Payment Notes -->
                @if($payment->notes)
                    <div class="bg-white rounded-lg md:rounded-xl shadow">
                        <div class="p-4 md:p-6 border-b border-gray-200">
                            <h2 class="text-base md:text-xl font-bold text-gray-900">{{ __('parent.payments.notes_title') }}</h2>
                        </div>
                        <div class="p-4 md:p-6">
                            <p class="text-sm md:text-base text-gray-900 whitespace-pre-line">{{ $payment->notes }}</p>
                        </div>
                    </div>
                @endif

                <!-- Failure Reason -->
                @if($payment->status === \App\Enums\PaymentStatus::FAILED && $payment->failure_reason)
                    <div class="bg-red-50 border border-red-200 rounded-lg p-4 md:p-6">
                        <div class="flex items-start gap-2.5 md:gap-3">
                            <i class="ri-error-warning-line text-xl md:text-2xl text-red-600 flex-shrink-0"></i>
                            <div class="min-w-0">
                                <p class="font-bold text-sm md:text-base text-red-900 mb-0.5 md:mb-1">{{ __('parent.payments.failure_reason') }}</p>
                                <p class="text-sm md:text-base text-red-800">{{ $payment->failure_reason }}</p>
                            </div>
                        </div>
                    </div>
                @endif

                <!-- Refund Information -->
                @if($payment->status === \App\Enums\PaymentStatus::REFUNDED && $payment->refund_reason)
                    <div class="bg-gray-50 border border-gray-200 rounded-lg p-4 md:p-6">
                        <div class="flex items-start gap-2.5 md:gap-3">
                            <i class="ri-refund-line text-xl md:text-2xl text-gray-600 flex-shrink-0"></i>
                            <div class="min-w-0">
                                <p class="font-bold text-sm md:text-base text-gray-900 mb-0.5 md:mb-1">{{ __('parent.payments.refund_reason') }}</p>
                                <p class="text-sm md:text-base text-gray-800">{{ $payment->refund_reason }}</p>
                                @if($payment->refunded_at)
                                    <p class="text-xs md:text-sm text-gray-600 mt-1 md:mt-2">{{ __('parent.payments.refund_date', ['date' => $payment->refunded_at->format('Y/m/d h:i A')]) }}</p>
                                @endif
                            </div>
                        </div>
                    </div>
                @endif
            </div>

            <!-- Sidebar -->
            <div class="space-y-4 md:space-y-6">
                <!-- Quick Actions -->
                @if($payment->status === \App\Enums\PaymentStatus::COMPLETED)
                    <div class="bg-white rounded-lg md:rounded-xl shadow p-4 md:p-6">
                        <h3 class="text-sm md:text-lg font-bold text-gray-900 mb-3 md:mb-4">{{ __('parent.payments.quick_actions') }}</h3>
                        <div class="space-y-2">
                            <a href="{{ route('parent.payments.download-receipt', ['subdomain' => $subdomain, 'id' => $payment->id]) }}"
                               class="min-h-[44px] flex items-center justify-between p-3 bg-green-50 hover:bg-green-100 rounded-lg transition-colors">
                                <div class="flex items-center gap-2">
                                    <i class="ri-download-line text-green-600"></i>
                                    <span class="text-sm md:text-base text-gray-900 font-bold">{{ __('parent.payments.download_receipt') }}</span>
                                </div>
                                <i class="ri-arrow-left-line text-gray-400"></i>
                            </a>
                        </div>
                    </div>
                @endif

                <!-- Payment Timeline -->
                <div class="bg-white rounded-lg md:rounded-xl shadow p-4 md:p-6">
                    <h3 class="text-sm md:text-lg font-bold text-gray-900 mb-3 md:mb-4">{{ __('parent.payments.timeline_title') }}</h3>
                    <div class="space-y-3 md:space-y-4">
                        <div class="flex items-start gap-2.5 md:gap-3">
                            <div class="bg-blue-100 rounded-full p-1.5 md:p-2 flex-shrink-0">
                                <i class="ri-add-line text-sm md:text-base text-blue-600"></i>
                            </div>
                            <div class="flex-1 min-w-0">
                                <p class="text-xs md:text-sm font-bold text-gray-900">{{ __('parent.payments.payment_created') }}</p>
                                <p class="text-[10px] md:text-xs text-gray-500">{{ $payment->created_at->format('Y/m/d h:i A') }}</p>
                            </div>
                        </div>

                        @if($payment->status === \App\Enums\PaymentStatus::COMPLETED && $payment->paid_at)
                            <div class="flex items-start gap-2.5 md:gap-3">
                                <div class="bg-green-100 rounded-full p-1.5 md:p-2 flex-shrink-0">
                                    <i class="ri-check-line text-sm md:text-base text-green-600"></i>
                                </div>
                                <div class="flex-1 min-w-0">
                                    <p class="text-xs md:text-sm font-bold text-gray-900">{{ __('parent.payments.payment_completed') }}</p>
                                    <p class="text-[10px] md:text-xs text-gray-500">{{ $payment->paid_at->format('Y/m/d h:i A') }}</p>
                                </div>
                            </div>
                        @endif

                        @if($payment->status === \App\Enums\PaymentStatus::FAILED)
                            <div class="flex items-start gap-2.5 md:gap-3">
                                <div class="bg-red-100 rounded-full p-1.5 md:p-2 flex-shrink-0">
                                    <i class="ri-close-line text-sm md:text-base text-red-600"></i>
                                </div>
                                <div class="flex-1 min-w-0">
                                    <p class="text-xs md:text-sm font-bold text-gray-900">{{ __('parent.payments.payment_failed') }}</p>
                                    <p class="text-[10px] md:text-xs text-gray-500">{{ $payment->updated_at->format('Y/m/d h:i A') }}</p>
                                </div>
                            </div>
                        @endif

                        @if($payment->status === \App\Enums\PaymentStatus::REFUNDED && $payment->refunded_at)
                            <div class="flex items-start gap-2.5 md:gap-3">
                                <div class="bg-gray-100 rounded-full p-1.5 md:p-2 flex-shrink-0">
                                    <i class="ri-refund-line text-sm md:text-base text-gray-600"></i>
                                </div>
                                <div class="flex-1 min-w-0">
                                    <p class="text-xs md:text-sm font-bold text-gray-900">{{ __('parent.payments.payment_refunded') }}</p>
                                    <p class="text-[10px] md:text-xs text-gray-500">{{ $payment->refunded_at->format('Y/m/d h:i A') }}</p>
                                </div>
                            </div>
                        @endif
                    </div>
                </div>

                <!-- Related Links -->
                <div class="bg-white rounded-lg md:rounded-xl shadow p-4 md:p-6">
                    <h3 class="text-sm md:text-lg font-bold text-gray-900 mb-3 md:mb-4">{{ __('parent.payments.related_links') }}</h3>
                    <div class="space-y-2">
                        <a href="{{ route('parent.payments.index', ['subdomain' => $subdomain]) }}" class="min-h-[44px] flex items-center justify-between p-3 bg-gray-50 hover:bg-gray-100 rounded-lg transition-colors">
                            <div class="flex items-center gap-2">
                                <i class="ri-file-list-line text-blue-600"></i>
                                <span class="text-sm md:text-base text-gray-900 font-bold">{{ __('parent.payments.all_payments') }}</span>
                            </div>
                            <i class="ri-arrow-left-line text-gray-400"></i>
                        </a>
                        <a href="{{ route('parent.subscriptions.index', ['subdomain' => $subdomain]) }}" class="min-h-[44px] flex items-center justify-between p-3 bg-gray-50 hover:bg-gray-100 rounded-lg transition-colors">
                            <div class="flex items-center gap-2">
                                <i class="ri-bookmark-line text-blue-600"></i>
                                <span class="text-sm md:text-base text-gray-900 font-bold">{{ __('parent.payments.subscriptions') }}</span>
                            </div>
                            <i class="ri-arrow-left-line text-gray-400"></i>
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-layouts.parent-layout>
