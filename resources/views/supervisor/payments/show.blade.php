<x-layouts.supervisor>

@php
    $subdomain = request()->route('subdomain') ?? auth()->user()->academy->subdomain ?? 'itqan-academy';

    $statusColors = [
        'pending' => 'bg-yellow-100 text-yellow-700',
        'completed' => 'bg-green-100 text-green-700',
        'failed' => 'bg-red-100 text-red-700',
        'cancelled' => 'bg-gray-100 text-gray-700',
        'expired' => 'bg-gray-100 text-gray-500',
    ];
    $statusValue = $payment->status instanceof \App\Enums\PaymentStatus ? $payment->status->value : $payment->status;
    $statusColor = $statusColors[$statusValue] ?? 'bg-gray-100 text-gray-700';

    $payableLabel = '';
    if ($payment->payable) {
        $payableLabel = match (true) {
            $payment->payable instanceof \App\Models\QuranSubscription => (
                $payment->payable->subscription_type === 'individual'
                    ? __('supervisor.payments.quran_individual_subscription')
                    : __('supervisor.payments.quran_group_subscription')
            ),
            $payment->payable instanceof \App\Models\AcademicSubscription => __('supervisor.payments.academic_subscription'),
            $payment->payable instanceof \App\Models\CourseSubscription => __('supervisor.payments.course_subscription'),
            default => class_basename(get_class($payment->payable)),
        };
    }
@endphp

<div>
    <x-ui.breadcrumb
        :items="[
            ['label' => __('supervisor.payments.page_title'), 'url' => route('manage.payments.index', ['subdomain' => $subdomain])],
            ['label' => $payment->payment_code ?? __('supervisor.payments.payment_details')],
        ]"
        view-type="supervisor"
    />

    <!-- Page Header -->
    <div class="mb-6 md:mb-8 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
        <div>
            <h1 class="text-xl sm:text-2xl md:text-3xl font-bold text-gray-900">{{ __('supervisor.payments.payment_details') }}</h1>
            <p class="mt-1 md:mt-2 text-sm md:text-base text-gray-600">{{ $payment->payment_code }}</p>
        </div>
        <div class="flex items-center gap-3">
            <a href="{{ route('manage.payments.index', ['subdomain' => $subdomain]) }}"
               class="cursor-pointer min-h-[44px] inline-flex items-center gap-2 px-4 py-2 bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200 transition-colors text-sm font-medium">
                <i class="ri-arrow-right-line"></i>
                {{ __('supervisor.payments.back_to_list') }}
            </a>
            @if($payment->status === \App\Enums\PaymentStatus::PENDING)
                <form method="POST" action="{{ route('manage.payments.mark-completed', ['subdomain' => $subdomain, 'payment' => $payment->id]) }}" id="mark-completed-form">
                    @csrf
                </form>
                <button type="button"
                    onclick="window.confirmAction({
                        title: @js(__('supervisor.payments.confirm_complete_title')),
                        message: @js(__('supervisor.payments.confirm_complete_message')),
                        confirmText: @js(__('supervisor.payments.mark_completed')),
                        isDangerous: false,
                        icon: 'ri-checkbox-circle-line',
                        onConfirm: () => document.getElementById('mark-completed-form').submit()
                    })"
                    class="cursor-pointer min-h-[44px] inline-flex items-center gap-2 px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 transition-colors text-sm font-medium">
                    <i class="ri-checkbox-circle-line"></i>
                    {{ __('supervisor.payments.mark_completed') }}
                </button>
            @endif
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-4 md:gap-6">
        <!-- Main Info -->
        <div class="lg:col-span-2 space-y-4 md:space-y-6">
            {{-- Payment Info Card --}}
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-4 md:p-6">
                <h2 class="text-lg font-semibold text-gray-900 mb-4 flex items-center gap-2">
                    <i class="ri-bank-card-line text-emerald-500"></i>
                    {{ __('supervisor.payments.transaction_info') }}
                </h2>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <span class="text-sm text-gray-500">{{ __('supervisor.payments.payment_code') }}</span>
                        <p class="font-mono font-medium text-gray-900">{{ $payment->payment_code }}</p>
                    </div>
                    <div>
                        <span class="text-sm text-gray-500">{{ __('supervisor.payments.status') }}</span>
                        <p><span class="px-2 py-1 text-xs rounded-full {{ $statusColor }}">{{ $payment->status_text }}</span></p>
                    </div>
                    <div>
                        <span class="text-sm text-gray-500">{{ __('supervisor.payments.amount') }}</span>
                        <p class="text-xl font-bold text-gray-900">{{ number_format($payment->amount, 2) }} <span class="text-sm text-gray-500">{{ $payment->currency ?? 'SAR' }}</span></p>
                    </div>
                    <div>
                        <span class="text-sm text-gray-500">{{ __('supervisor.payments.method') }}</span>
                        <p class="font-medium text-gray-900">{{ $payment->payment_method_text }}</p>
                    </div>
                    @if($payment->payment_gateway)
                        <div>
                            <span class="text-sm text-gray-500">{{ __('supervisor.payments.gateway') }}</span>
                            <p class="font-medium text-gray-900">{{ $payment->payment_gateway }}</p>
                        </div>
                    @endif
                    @if($payment->gateway_transaction_id)
                        <div>
                            <span class="text-sm text-gray-500">{{ __('supervisor.payments.transaction_id') }}</span>
                            <p class="font-mono text-sm text-gray-900">{{ $payment->gateway_transaction_id }}</p>
                        </div>
                    @endif
                    @if($payment->receipt_number)
                        <div>
                            <span class="text-sm text-gray-500">{{ __('supervisor.payments.receipt_number') }}</span>
                            <p class="font-mono text-sm text-gray-900">{{ $payment->receipt_number }}</p>
                        </div>
                    @endif
                    @if($payment->discount_amount && $payment->discount_amount > 0)
                        <div>
                            <span class="text-sm text-gray-500">{{ __('supervisor.payments.discount') }}</span>
                            <p class="font-medium text-green-600">-{{ number_format($payment->discount_amount, 2) }} {{ $payment->currency ?? 'SAR' }}</p>
                        </div>
                    @endif
                    @if($payment->fees && $payment->fees > 0)
                        <div>
                            <span class="text-sm text-gray-500">{{ __('supervisor.payments.fees') }}</span>
                            <p class="font-medium text-gray-900">{{ number_format($payment->fees, 2) }} {{ $payment->currency ?? 'SAR' }}</p>
                        </div>
                    @endif
                    @if($payment->net_amount)
                        <div>
                            <span class="text-sm text-gray-500">{{ __('supervisor.payments.net_amount') }}</span>
                            <p class="font-medium text-gray-900">{{ number_format($payment->net_amount, 2) }} {{ $payment->currency ?? 'SAR' }}</p>
                        </div>
                    @endif
                </div>
            </div>

            {{-- Dates Card --}}
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-4 md:p-6">
                <h2 class="text-lg font-semibold text-gray-900 mb-4 flex items-center gap-2">
                    <i class="ri-calendar-line text-blue-500"></i>
                    {{ __('supervisor.payments.dates') }}
                </h2>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <span class="text-sm text-gray-500">{{ __('supervisor.payments.created_at') }}</span>
                        <p class="font-medium text-gray-900">{{ $payment->created_at?->format('Y-m-d H:i') }}</p>
                    </div>
                    @if($payment->paid_at)
                        <div>
                            <span class="text-sm text-gray-500">{{ __('supervisor.payments.paid_at') }}</span>
                            <p class="font-medium text-gray-900">{{ $payment->paid_at->format('Y-m-d H:i') }}</p>
                        </div>
                    @endif
                    @if($payment->confirmed_at)
                        <div>
                            <span class="text-sm text-gray-500">{{ __('supervisor.payments.confirmed_at') }}</span>
                            <p class="font-medium text-gray-900">{{ $payment->confirmed_at->format('Y-m-d H:i') }}</p>
                        </div>
                    @endif
                    @if($payment->processed_at)
                        <div>
                            <span class="text-sm text-gray-500">{{ __('supervisor.payments.processed_at') }}</span>
                            <p class="font-medium text-gray-900">{{ $payment->processed_at->format('Y-m-d H:i') }}</p>
                        </div>
                    @endif
                </div>
            </div>

            {{-- Failure Info (if failed) --}}
            @if($payment->failure_reason)
                <div class="bg-red-50 rounded-xl border border-red-200 p-4 md:p-6">
                    <h2 class="text-lg font-semibold text-red-900 mb-2 flex items-center gap-2">
                        <i class="ri-error-warning-line text-red-500"></i>
                        {{ __('supervisor.payments.failure_reason') }}
                    </h2>
                    <p class="text-sm text-red-700">{{ $payment->failure_reason }}</p>
                </div>
            @endif

            {{-- Notes --}}
            @if($payment->notes)
                <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-4 md:p-6">
                    <h2 class="text-lg font-semibold text-gray-900 mb-2 flex items-center gap-2">
                        <i class="ri-sticky-note-line text-gray-500"></i>
                        {{ __('supervisor.payments.notes') }}
                    </h2>
                    <p class="text-sm text-gray-700">{{ $payment->notes }}</p>
                </div>
            @endif
        </div>

        <!-- Sidebar -->
        <div class="space-y-4 md:space-y-6">
            {{-- Student Info --}}
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-4 md:p-6">
                <h2 class="text-lg font-semibold text-gray-900 mb-4 flex items-center gap-2">
                    <i class="ri-user-line text-blue-500"></i>
                    {{ __('supervisor.payments.payer_info') }}
                </h2>
                @if($payment->user)
                    <a href="{{ route('manage.students.show', ['subdomain' => $subdomain, 'student' => $payment->user->id]) }}"
                       class="flex items-center gap-3 p-2 -m-2 rounded-lg hover:bg-gray-50 transition-colors group">
                        <x-avatar :user="$payment->user" size="md" user-type="student" />
                        <p class="font-medium text-gray-900 group-hover:text-blue-600 transition-colors">{{ $payment->user->name }}</p>
                        <i class="ri-arrow-left-s-line text-gray-400 group-hover:text-blue-500 ms-auto"></i>
                    </a>
                @else
                    <p class="text-sm text-gray-500">{{ __('supervisor.payments.unknown') }}</p>
                @endif
            </div>

            {{-- Related Entity --}}
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-4 md:p-6">
                <h2 class="text-lg font-semibold text-gray-900 mb-4 flex items-center gap-2">
                    <i class="ri-links-line text-purple-500"></i>
                    {{ __('supervisor.payments.related_entity') }}
                </h2>
                @if($payment->payable)
                    <div class="space-y-2">
                        <div>
                            <span class="text-sm text-gray-500">{{ __('supervisor.payments.type') }}</span>
                            <p class="font-medium text-gray-900">{{ $payableLabel }}</p>
                        </div>
                        <div>
                            <span class="text-sm text-gray-500">{{ __('supervisor.payments.id') }}</span>
                            <p class="font-mono text-sm text-gray-900">#{{ $payment->payable_id }}</p>
                        </div>
                    </div>
                @else
                    <p class="text-sm text-gray-500">{{ __('supervisor.payments.no_related_entity') }}</p>
                @endif
            </div>
        </div>
    </div>
</div>

</x-layouts.supervisor>
