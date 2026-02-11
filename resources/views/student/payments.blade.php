@php
    $academy = auth()->user()->academy;
    $subdomain = request()->route('subdomain') ?? $academy->subdomain ?? 'itqan-academy';
    $isParent = ($layout ?? 'student') === 'parent';
    $routePrefix = $isParent ? 'parent.payments' : 'student.payments';
@endphp

<x-layouts.authenticated
    :role="$layout ?? 'student'"
    title="{{ $academy->name ?? __('student.common.academy_default') }} - {{ $isParent ? __('student.payments.parent_title') : __('student.payments.title') }}">
    <x-slot name="description">{{ $isParent ? __('student.payments.parent_description') : __('student.payments.description') }} - {{ $academy->name ?? __('student.common.academy_default') }}</x-slot>

    <!-- Header Section -->
    <x-student-page.header
        title="{{ $isParent ? __('student.payments.parent_title') : __('student.payments.title') }}"
        description="{{ $isParent ? __('student.payments.parent_description') : __('student.payments.description') }}"
        :count="$stats['total_payments']"
        countLabel="{{ __('student.payments.total_payments') }}"
        countColor="blue"
        :secondaryCount="$stats['successful_payments']"
        secondaryCountLabel="{{ __('student.payments.successful_payments') }}"
        secondaryCountColor="green"
    />

    <!-- Saved Payment Methods Section -->
    <livewire:payment.saved-payment-methods-list />

    <!-- Filters Section -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-4 md:p-6 mb-6 md:mb-8">
        <form method="GET" action="{{ route($isParent ? 'parent.payments.index' : 'student.payments', ['subdomain' => $subdomain]) }}" class="space-y-4">
            <div class="mb-4">
                <h3 class="text-base md:text-lg font-semibold text-gray-900">
                    <i class="ri-filter-3-line ms-2"></i>
                    {{ __('student.payments.filters_title') }}
                </h3>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                <!-- Status Filter -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">
                        <i class="ri-checkbox-circle-line ms-1"></i>
                        {{ __('student.payments.status_label') }}
                    </label>
                    <div class="relative">
                        <select name="status"
                                style="appearance: none; -webkit-appearance: none; -moz-appearance: none; background-image: none;"
                                class="w-full border border-gray-300 rounded-lg px-4 py-2.5 pe-10 text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors bg-white">
                            <option value="all">{{ __('student.payments.status_all') }}</option>
                            @foreach(\App\Enums\PaymentStatus::cases() as $status)
                                <option value="{{ $status->value }}" {{ request('status') === $status->value ? 'selected' : '' }}>
                                    {{ $status->label() }}
                                </option>
                            @endforeach
                        </select>
                        <div class="pointer-events-none absolute inset-y-0 end-0 flex items-center px-3 text-gray-500">
                            <i class="ri-arrow-down-s-line text-lg"></i>
                        </div>
                    </div>
                </div>

                <!-- Date From -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">
                        <i class="ri-calendar-line ms-1"></i>
                        {{ __('student.payments.date_from') }}
                    </label>
                    <input
                        type="date"
                        name="date_from"
                        value="{{ request('date_from') }}"
                        class="w-full border border-gray-300 rounded-lg px-4 py-2.5 text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors bg-white"
                    >
                </div>

                <!-- Date To -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">
                        <i class="ri-calendar-line ms-1"></i>
                        {{ __('student.payments.date_to') }}
                    </label>
                    <input
                        type="date"
                        name="date_to"
                        value="{{ request('date_to') }}"
                        class="w-full border border-gray-300 rounded-lg px-4 py-2.5 text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors bg-white"
                    >
                </div>

                <!-- Empty space for alignment -->
                <div class="hidden lg:block"></div>
            </div>

            <!-- Buttons Row -->
            <div class="flex flex-col sm:flex-row items-stretch sm:items-center gap-3 pt-2">
                <button type="submit"
                        class="inline-flex items-center justify-center min-h-[44px] bg-blue-600 text-white px-6 py-2.5 rounded-xl md:rounded-lg text-sm font-medium hover:bg-blue-700 transition-colors">
                    <i class="ri-search-line ms-1"></i>
                    {{ __('student.payments.apply_filters') }}
                </button>

                @if(request()->hasAny(['status', 'date_from', 'date_to']))
                <a href="{{ route($isParent ? 'parent.payments.index' : 'student.payments', ['subdomain' => $subdomain]) }}"
                   class="inline-flex items-center justify-center min-h-[44px] bg-gray-100 text-gray-700 px-6 py-2.5 rounded-xl md:rounded-lg text-sm font-medium hover:bg-gray-200 transition-colors">
                    <i class="ri-close-circle-line ms-1"></i>
                    {{ __('student.payments.reset_filters') }}
                </a>
                @endif
            </div>
        </form>
    </div>

    <!-- Payments List -->
    <div class="space-y-4">
        @forelse($payments as $payment)
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden hover:shadow-md transition-all">
                <div class="p-4 md:p-6">
                    <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4">
                        <!-- Payment Info -->
                        <div class="flex-1">
                            <div class="flex items-start gap-4">
                                <!-- Icon -->
                                <div class="p-3 bg-{{ $payment->status_badge_color }}-50 rounded-xl shrink-0">
                                    @if($payment->is_successful)
                                        <i class="ri-checkbox-circle-line text-2xl text-green-600"></i>
                                    @elseif($payment->is_pending)
                                        <i class="ri-time-line text-2xl text-yellow-600"></i>
                                    @elseif($payment->is_failed)
                                        <i class="ri-close-circle-line text-2xl text-red-600"></i>
                                    @else
                                        <i class="ri-wallet-line text-2xl text-gray-600"></i>
                                    @endif
                                </div>

                                <!-- Details -->
                                <div class="flex-1">
                                    <div class="flex items-center gap-3 mb-2">
                                        <h3 class="text-lg font-semibold text-gray-900">
                                            {{ $payment->payment_code }}
                                        </h3>
                                        <span class="px-3 py-1 bg-{{ $payment->status_badge_color }}-100 text-{{ $payment->status_badge_color }}-700 text-xs font-medium rounded-full">
                                            {{ $payment->status_text }}
                                        </span>
                                    </div>

                                    <!-- Subscription Info -->
                                    @if($payment->subscription)
                                        <p class="text-sm text-gray-600 mb-1">
                                            <i class="ri-bookmark-line"></i>
                                            {{ $payment->subscription->getSubscriptionTitle() }}
                                        </p>
                                    @endif

                                    <div class="flex flex-wrap items-center gap-4 text-sm text-gray-500">
                                        <span>
                                            <i class="ri-calendar-line"></i>
                                            {{ $payment->payment_date?->format('Y-m-d H:i') ?? __('student.payments.date_not_available') }}
                                        </span>
                                        <span>
                                            <i class="ri-bank-card-line"></i>
                                            {{ $payment->payment_method_text }}
                                        </span>
                                        @if($payment->receipt_number)
                                            <span>
                                                <i class="ri-file-list-line"></i>
                                                {{ $payment->receipt_number }}
                                            </span>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Amount & Actions -->
                        <div class="flex flex-col lg:items-end gap-3">
                            <!-- Amount -->
                            <div class="text-end">
                                <div class="text-2xl font-bold text-gray-900">
                                    {{ number_format($payment->amount, 2) }} {{ $payment->currency }}
                                </div>
                                @if($payment->fees > 0)
                                    <div class="text-xs text-gray-500">
                                        {{ __('student.payments.fees_label') }} {{ number_format($payment->fees, 2) }} {{ $payment->currency }}
                                    </div>
                                @endif
                            </div>

                            <!-- Actions -->
                            <div class="flex flex-wrap gap-2">
                                @if($payment->receipt_url)
                                    <a
                                        href="{{ $payment->receipt_url }}"
                                        target="_blank"
                                        class="inline-flex items-center justify-center min-h-[44px] px-4 py-2 bg-blue-50 text-blue-600 rounded-xl md:rounded-lg hover:bg-blue-100 transition-colors text-sm font-medium"
                                    >
                                        <i class="ri-download-line ms-1"></i>
                                        {{ __('student.payments.download_receipt') }}
                                    </a>
                                @endif

                                @if($payment->subscription)
                                    <a
                                        href="{{ route($isParent ? 'parent.subscriptions.index' : 'student.subscriptions', ['subdomain' => $subdomain]) }}"
                                        class="inline-flex items-center justify-center min-h-[44px] px-4 py-2 bg-gray-100 text-gray-700 rounded-xl md:rounded-lg hover:bg-gray-200 transition-colors text-sm font-medium"
                                    >
                                        <i class="ri-eye-line ms-1"></i>
                                        {{ __('student.payments.view_subscription') }}
                                    </a>
                                @endif
                            </div>
                        </div>
                    </div>

                    <!-- Additional Details -->
                    @if($payment->gateway_transaction_id || $payment->tax_amount > 0 || $payment->discount_amount > 0)
                        <div class="mt-4 pt-4 border-t border-gray-100">
                            <div class="grid grid-cols-2 md:grid-cols-4 gap-4 text-sm">
                                @if($payment->gateway_transaction_id)
                                    <div>
                                        <span class="text-gray-500">{{ __('student.payments.transaction_id') }}</span>
                                        <span class="block font-medium text-gray-900">{{ $payment->gateway_transaction_id }}</span>
                                    </div>
                                @endif
                                @if($payment->tax_amount > 0)
                                    <div>
                                        <span class="text-gray-500">{{ __('student.payments.tax_label') }}</span>
                                        <span class="block font-medium text-gray-900">{{ number_format($payment->tax_amount, 2) }} {{ $payment->currency }}</span>
                                    </div>
                                @endif
                                @if($payment->discount_amount > 0)
                                    <div>
                                        <span class="text-gray-500">{{ __('student.payments.discount_label') }}</span>
                                        <span class="block font-medium text-green-600">-{{ number_format($payment->discount_amount, 2) }} {{ $payment->currency }}</span>
                                    </div>
                                @endif
                                <div>
                                    <span class="text-gray-500">{{ __('student.payments.net_amount') }}</span>
                                    <span class="block font-medium text-gray-900">{{ number_format($payment->net_amount, 2) }} {{ $payment->currency }}</span>
                                </div>
                            </div>
                        </div>
                    @endif
                </div>
            </div>
        @empty
            <!-- Empty State -->
            <x-ui.empty-state
                icon="ri-wallet-3-line"
                title="{{ $isParent ? __('student.payments.no_payments_parent_title') : __('student.payments.no_payments_title') }}"
                description="{{ $isParent ? __('student.payments.no_payments_parent_description') : __('student.payments.no_payments_description') }}"
            />
        @endforelse
    </div>

    <!-- Pagination -->
    @if($payments->hasPages())
        <div class="mt-8">
            {{ $payments->links() }}
        </div>
    @endif
</x-layouts.authenticated>
