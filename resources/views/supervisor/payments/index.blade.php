<x-layouts.supervisor>

@php
    $subdomain = request()->route('subdomain') ?? auth()->user()->academy->subdomain ?? 'itqan-academy';

    $hasActiveFilters = request('status')
        || request('payment_method')
        || request('payment_gateway')
        || request('date_from')
        || request('date_to')
        || request('search');

    $filterCount = (request('status') ? 1 : 0)
        + (request('payment_method') ? 1 : 0)
        + (request('payment_gateway') ? 1 : 0)
        + (request('date_from') ? 1 : 0)
        + (request('date_to') ? 1 : 0)
        + (request('search') ? 1 : 0);

    $gatewayLogos = [
        'paymob' => asset('app-design-assets/paymob-logo.png'),
        'easykash' => asset('app-design-assets/easykash-logo.png'),
        'tap' => asset('app-design-assets/tap-logo.png'),
    ];

    $gatewayLabels = [
        'paymob' => __('supervisor.payments.gateway_paymob'),
        'easykash' => __('supervisor.payments.gateway_easykash'),
        'tap' => __('supervisor.payments.gateway_tap'),
        'manual' => __('supervisor.payments.gateway_manual'),
    ];

    $currentSort = request('sort', 'newest');
@endphp

<div>
    <x-ui.breadcrumb
        :items="[['label' => __('supervisor.payments.page_title')]]"
        view-type="supervisor"
    />

    <!-- Page Header -->
    <div class="mb-6 md:mb-8">
        <h1 class="text-xl sm:text-2xl md:text-3xl font-bold text-gray-900">{{ __('supervisor.payments.page_title') }}</h1>
        <p class="mt-1 md:mt-2 text-sm md:text-base text-gray-600">{{ __('supervisor.payments.page_subtitle') }}</p>
    </div>

    <!-- Stats -->
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-3 md:gap-4 mb-6">
        @if($isAdmin)
        {{-- Revenue This Month --}}
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-4 md:p-6">
            <div class="flex items-center gap-3 mb-3">
                <div class="p-2 bg-green-50 rounded-lg">
                    <i class="ri-money-dollar-circle-line text-xl text-green-600"></i>
                </div>
                <span class="text-sm text-gray-500">{{ __('supervisor.payments.revenue_this_month') }}</span>
            </div>
            <div class="text-2xl md:text-3xl font-bold text-gray-900">{{ number_format($revenueThisMonth, 2) }}</div>
        </div>
        @endif

        {{-- Pending Payments --}}
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-4 md:p-6">
            <div class="flex items-center gap-3 mb-3">
                <div class="p-2 bg-yellow-50 rounded-lg">
                    <i class="ri-time-line text-xl text-yellow-600"></i>
                </div>
                <span class="text-sm text-gray-500">{{ __('supervisor.payments.pending_payments') }}</span>
            </div>
            <div class="text-2xl md:text-3xl font-bold text-gray-900">{{ $pendingCount }}</div>
        </div>

        {{-- Completed Today --}}
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-4 md:p-6">
            <div class="flex items-center gap-3 mb-3">
                <div class="p-2 bg-blue-50 rounded-lg">
                    <i class="ri-checkbox-circle-line text-xl text-blue-600"></i>
                </div>
                <span class="text-sm text-gray-500">{{ __('supervisor.payments.completed_today') }}</span>
            </div>
            <div class="text-2xl md:text-3xl font-bold text-gray-900">{{ $completedToday }}</div>
        </div>

        @if($isAdmin)
        {{-- Total Revenue --}}
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-4 md:p-6">
            <div class="flex items-center gap-3 mb-3">
                <div class="p-2 bg-purple-50 rounded-lg">
                    <i class="ri-funds-line text-xl text-purple-600"></i>
                </div>
                <span class="text-sm text-gray-500">{{ __('supervisor.payments.total_revenue') }}</span>
            </div>
            <div class="text-2xl md:text-3xl font-bold text-gray-900">{{ number_format($totalRevenue, 2) }}</div>
        </div>
        @endif
    </div>

    <!-- Revenue by Source -->
    @if($isAdmin)
    @php
        $gatewayCards = [
            'paymob'   => 'border-s-blue-500',
            'easykash' => 'border-s-emerald-500',
            'tap'      => 'border-s-cyan-500',
            'manual'   => 'border-s-orange-500',
        ];
    @endphp
    <div class="mb-6">
        <h3 class="text-sm font-semibold text-gray-500 mb-3">{{ __('supervisor.payments.revenue_by_source') }}</h3>
        <div class="grid grid-cols-2 lg:grid-cols-4 gap-3 md:gap-4">
            @foreach($gatewayCards as $gw => $borderClass)
                <div class="bg-white rounded-xl shadow-sm border border-gray-100 border-s-4 {{ $borderClass }} p-4 md:p-5">
                    <div class="flex items-center gap-3 mb-3">
                        @if(isset($gatewayLogos[$gw]))
                            <img src="{{ $gatewayLogos[$gw] }}" alt="" class="h-7 w-auto object-contain">
                        @else
                            <div class="p-1.5 bg-orange-50 rounded-lg">
                                <i class="ri-admin-line text-lg text-orange-600"></i>
                            </div>
                        @endif
                        <span class="text-sm text-gray-500">{{ $gatewayLabels[$gw] }}</span>
                    </div>
                    <div class="text-xl md:text-2xl font-bold text-gray-900">{{ number_format($gatewayRevenues->get($gw, 0), 2) }}</div>
                </div>
            @endforeach
        </div>
    </div>
    @endif

    <!-- List Card -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-200">
        <!-- List Header with Sort -->
        <div class="px-4 md:px-6 py-3 md:py-4 border-b border-gray-200 flex flex-wrap items-center justify-between gap-3">
            <h2 class="text-base md:text-lg font-semibold text-gray-900">
                {{ __('supervisor.payments.list_title') }} ({{ $payments->total() }})
            </h2>
            <div x-data="{ open: false }" class="relative">
                <button @click="open = !open" type="button"
                    class="cursor-pointer inline-flex items-center gap-2 px-3 py-1.5 text-sm text-gray-600 bg-gray-100 rounded-lg hover:bg-gray-200 transition-colors">
                    <i class="ri-sort-desc"></i>
                    <span>
                        @switch($currentSort)
                            @case('amount_desc') {{ __('supervisor.payments.sort_amount_desc') }} @break
                            @case('amount_asc') {{ __('supervisor.payments.sort_amount_asc') }} @break
                            @case('oldest') {{ __('supervisor.payments.sort_oldest') }} @break
                            @default {{ __('supervisor.payments.sort_newest') }}
                        @endswitch
                    </span>
                    <i class="ri-arrow-down-s-line"></i>
                </button>
                <div x-show="open" @click.away="open = false" x-transition
                    class="absolute start-0 mt-2 w-48 bg-white rounded-lg shadow-lg border border-gray-200 py-1 z-20">
                    @foreach(['newest', 'oldest', 'amount_desc', 'amount_asc'] as $sortOption)
                        <a href="{{ request()->fullUrlWithQuery(['sort' => $sortOption, 'page' => 1]) }}"
                           class="block px-4 py-2 text-sm cursor-pointer {{ $currentSort === $sortOption ? 'bg-emerald-50 text-emerald-700 font-medium' : 'text-gray-700 hover:bg-gray-50' }}">
                            {{ __('supervisor.payments.sort_' . $sortOption) }}
                        </a>
                    @endforeach
                </div>
            </div>
        </div>

        <!-- Collapsible Filters -->
        <div x-data="{ open: {{ $hasActiveFilters ? 'true' : 'false' }} }" class="border-b border-gray-200">
            <button type="button" @click="open = !open"
                class="cursor-pointer w-full flex items-center justify-between px-4 md:px-6 py-3 text-sm font-medium text-gray-700 hover:bg-gray-50 transition-colors">
                <span class="flex items-center gap-2">
                    <i class="ri-filter-3-line text-emerald-500"></i>
                    {{ __('supervisor.payments.filter') }}
                    @if($hasActiveFilters)
                        <span class="inline-flex items-center justify-center w-5 h-5 text-xs font-bold text-white bg-emerald-500 rounded-full">{{ $filterCount }}</span>
                    @endif
                </span>
                <i class="ri-arrow-down-s-line text-gray-400 transition-transform" :class="{ 'rotate-180': open }"></i>
            </button>
            <div x-show="open" x-collapse>
                <form method="GET" action="{{ route('manage.payments.index', ['subdomain' => $subdomain]) }}" class="px-4 md:px-6 pb-4">
                    @if(request('sort'))
                        <input type="hidden" name="sort" value="{{ request('sort') }}">
                    @endif
                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-3 md:gap-4">
                        <div>
                            <label for="search" class="block text-sm font-medium text-gray-700 mb-1">{{ __('supervisor.payments.filter_search') }}</label>
                            <input type="text" name="search" id="search" value="{{ request('search') }}"
                                   placeholder="{{ __('supervisor.payments.search_placeholder') }}"
                                   class="min-h-[44px] w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500">
                        </div>
                        <div>
                            <label for="status" class="block text-sm font-medium text-gray-700 mb-1">{{ __('supervisor.payments.filter_status') }}</label>
                            <select name="status" id="status" class="min-h-[44px] w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500">
                                <option value="">{{ __('supervisor.payments.all_statuses') }}</option>
                                @foreach(\App\Enums\PaymentStatus::cases() as $status)
                                    <option value="{{ $status->value }}" {{ request('status') === $status->value ? 'selected' : '' }}>
                                        {{ $status->label() }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label for="payment_method" class="block text-sm font-medium text-gray-700 mb-1">{{ __('supervisor.payments.filter_method') }}</label>
                            <select name="payment_method" id="payment_method" class="min-h-[44px] w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500">
                                <option value="">{{ __('supervisor.payments.all_methods') }}</option>
                                @foreach($paymentMethods as $method)
                                    <option value="{{ $method }}" {{ request('payment_method') === $method ? 'selected' : '' }}>
                                        {{ $method }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label for="payment_gateway" class="block text-sm font-medium text-gray-700 mb-1">{{ __('supervisor.payments.filter_source') }}</label>
                            <select name="payment_gateway" id="payment_gateway" class="min-h-[44px] w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500">
                                <option value="">{{ __('supervisor.payments.all_sources') }}</option>
                                @foreach(['paymob', 'easykash', 'tap', 'manual'] as $gw)
                                    <option value="{{ $gw }}" {{ request('payment_gateway') === $gw ? 'selected' : '' }}>
                                        {{ $gatewayLabels[$gw] }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label for="date_from" class="block text-sm font-medium text-gray-700 mb-1">{{ __('supervisor.payments.date_from') }}</label>
                            <input type="date" name="date_from" id="date_from" value="{{ request('date_from') }}"
                                   class="min-h-[44px] w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500">
                        </div>
                        <div>
                            <label for="date_to" class="block text-sm font-medium text-gray-700 mb-1">{{ __('supervisor.payments.date_to') }}</label>
                            <input type="date" name="date_to" id="date_to" value="{{ request('date_to') }}"
                                   class="min-h-[44px] w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500">
                        </div>
                    </div>
                    <div class="flex flex-wrap items-center gap-3 mt-4">
                        <button type="submit"
                            class="cursor-pointer min-h-[44px] inline-flex items-center gap-2 px-4 py-2 bg-emerald-600 hover:bg-emerald-700 text-white rounded-lg transition-colors text-sm font-medium">
                            <i class="ri-filter-line"></i>
                            {{ __('supervisor.payments.filter') }}
                        </button>
                        @if($hasActiveFilters)
                            <a href="{{ route('manage.payments.index', ['subdomain' => $subdomain]) }}"
                               class="cursor-pointer min-h-[44px] inline-flex items-center gap-2 px-4 py-2 bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200 transition-colors text-sm font-medium">
                                <i class="ri-close-line"></i>
                                {{ __('supervisor.payments.clear_filters') }}
                            </a>
                        @endif
                    </div>
                </form>
            </div>
        </div>

        <!-- Table -->
        @if($payments->count() > 0)
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead class="bg-gray-50 text-gray-600">
                        <tr>
                            <th class="px-4 md:px-6 py-3 text-start font-medium">{{ __('supervisor.payments.student') }}</th>
                            <th class="px-4 md:px-6 py-3 text-start font-medium">{{ __('supervisor.payments.amount') }}</th>
                            <th class="px-4 md:px-6 py-3 text-start font-medium">{{ __('supervisor.payments.status') }}</th>
                            <th class="px-4 md:px-6 py-3 text-start font-medium hidden md:table-cell">{{ __('supervisor.payments.method') }}</th>
                            <th class="px-4 md:px-6 py-3 text-start font-medium hidden md:table-cell">{{ __('supervisor.payments.source') }}</th>
                            <th class="px-4 md:px-6 py-3 text-start font-medium hidden lg:table-cell">{{ __('supervisor.payments.date') }}</th>
                            <th class="px-4 md:px-6 py-3 text-start font-medium hidden lg:table-cell">{{ __('supervisor.payments.related_to') }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                        @foreach($payments as $payment)
                            @php
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
                                    $payableClass = get_class($payment->payable);
                                    $payableLabel = match (true) {
                                        $payment->payable instanceof \App\Models\QuranSubscription => (
                                            $payment->payable->subscription_type === 'individual'
                                                ? __('supervisor.payments.quran_individual_subscription')
                                                : __('supervisor.payments.quran_group_subscription')
                                        ),
                                        $payment->payable instanceof \App\Models\AcademicSubscription => __('supervisor.payments.academic_subscription'),
                                        $payment->payable instanceof \App\Models\CourseSubscription => __('supervisor.payments.course_subscription'),
                                        default => class_basename($payableClass),
                                    };
                                }
                            @endphp
                            <tr class="hover:bg-gray-50 transition-colors cursor-pointer" onclick="window.location='{{ route('manage.payments.show', ['subdomain' => $subdomain, 'payment' => $payment->id]) }}'">
                                <td class="px-4 md:px-6 py-3">
                                    <div class="flex items-center gap-2">
                                        <x-avatar :user="$payment->user" size="sm" user-type="student" />
                                        <div>
                                            <div class="font-medium text-gray-900">{{ $payment->user?->name ?? __('supervisor.payments.unknown') }}</div>
                                            <div class="text-xs text-gray-500 hidden sm:block">{{ $payment->payment_code }}</div>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-4 md:px-6 py-3 font-semibold text-gray-900">
                                    {{ number_format($payment->amount, 2) }}
                                    <span class="text-xs text-gray-500">{{ $payment->currency ?? 'SAR' }}</span>
                                </td>
                                <td class="px-4 md:px-6 py-3">
                                    <span class="px-2 py-1 text-xs rounded-full {{ $statusColor }}">
                                        {{ $payment->status_text }}
                                    </span>
                                </td>
                                <td class="px-4 md:px-6 py-3 hidden md:table-cell text-gray-600">
                                    {{ $payment->payment_method_text }}
                                </td>
                                <td class="px-4 md:px-6 py-3 hidden md:table-cell">
                                    @if($payment->payment_gateway)
                                        <span class="inline-flex items-center gap-1.5 px-2 py-1 text-xs rounded-full bg-gray-50 text-gray-700 border border-gray-200">
                                            @if(isset($gatewayLogos[$payment->payment_gateway]))
                                                <img src="{{ $gatewayLogos[$payment->payment_gateway] }}" alt="" class="h-4 w-auto object-contain">
                                            @else
                                                <i class="ri-admin-line text-orange-500"></i>
                                            @endif
                                            {{ $gatewayLabels[$payment->payment_gateway] ?? __('supervisor.payments.gateway_unknown') }}
                                        </span>
                                    @else
                                        <span class="text-gray-400">-</span>
                                    @endif
                                </td>
                                <td class="px-4 md:px-6 py-3 hidden lg:table-cell text-gray-600">
                                    {{ $payment->created_at?->format('Y-m-d') }}
                                </td>
                                <td class="px-4 md:px-6 py-3 hidden lg:table-cell">
                                    @if($payableLabel)
                                        <span class="px-2 py-1 text-xs rounded-full bg-blue-50 text-blue-700">{{ $payableLabel }}</span>
                                    @else
                                        <span class="text-gray-400">-</span>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            @if($payments->hasPages())
                <div class="px-4 md:px-6 py-4 border-t border-gray-200">
                    {{ $payments->withQueryString()->links() }}
                </div>
            @endif
        @else
            {{-- Empty State --}}
            <div class="px-4 md:px-6 py-8 md:py-12 text-center">
                <div class="w-14 h-14 md:w-16 md:h-16 bg-gray-100 rounded-full flex items-center justify-center mx-auto mb-3 md:mb-4">
                    <i class="ri-money-dollar-circle-line text-xl md:text-2xl text-gray-400"></i>
                </div>
                @if($hasActiveFilters)
                    <h3 class="text-base md:text-lg font-medium text-gray-900 mb-1 md:mb-2">{{ __('supervisor.payments.no_results') }}</h3>
                    <p class="text-sm md:text-base text-gray-600">{{ __('supervisor.payments.no_results_description') }}</p>
                    <a href="{{ route('manage.payments.index', ['subdomain' => $subdomain]) }}"
                       class="cursor-pointer min-h-[44px] inline-flex items-center justify-center px-4 py-2 bg-emerald-600 hover:bg-emerald-700 text-white text-sm font-medium rounded-lg transition-colors mt-4">
                        {{ __('supervisor.payments.view_all') }}
                    </a>
                @else
                    <h3 class="text-base md:text-lg font-bold text-gray-900 mb-1 md:mb-2">{{ __('supervisor.payments.no_payments') }}</h3>
                    <p class="text-gray-600 text-xs md:text-sm">{{ __('supervisor.payments.no_payments_description') }}</p>
                @endif
            </div>
        @endif
    </div>
</div>
</x-layouts.supervisor>
