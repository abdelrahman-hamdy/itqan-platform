<x-layouts.student>

@php
    $subdomain = request()->route('subdomain') ?? auth()->user()->academy->subdomain ?? 'itqan-academy';
    $sessionsRemaining = method_exists($subscription, 'getSessionsRemaining') ? $subscription->getSessionsRemaining() : 0;
    $teacherName = $type === 'quran'
        ? ($subscription->quranTeacherUser?->name ?? '-')
        : ($subscription->teacher?->user?->name ?? '-');
    $packageName = $subscription->package_name_ar ?? $subscription->package?->name ?? '-';
    $isResubscribe = $mode === 'resubscribe';
@endphp

<div class="max-w-2xl mx-auto px-4 py-8">
    <x-ui.breadcrumb
        :items="[
            ['label' => __('student.subscriptions.page_title'), 'url' => route('student.subscriptions', ['subdomain' => $subdomain])],
            ['label' => $isResubscribe ? __('student.subscriptions.resubscribe') : __('student.subscriptions.renew')],
        ]"
        view-type="student"
    />

    <div class="bg-white rounded-2xl shadow-sm border border-gray-200 overflow-hidden mt-6">
        {{-- Header --}}
        <div class="px-6 py-5 border-b border-gray-100 bg-gradient-to-l from-indigo-50 to-white">
            <h1 class="text-xl font-bold text-gray-900">
                {{ $isResubscribe ? __('student.subscriptions.resubscribe_title') : __('student.subscriptions.renew_title') }}
            </h1>
            <p class="text-sm text-gray-600 mt-1">
                {{ $isResubscribe ? __('student.subscriptions.resubscribe_desc') : __('student.subscriptions.renew_desc') }}
            </p>
        </div>

        {{-- Current Subscription Info --}}
        <div class="px-6 py-4 bg-gray-50 border-b border-gray-100">
            <div class="grid grid-cols-2 gap-4 text-sm">
                <div>
                    <span class="text-gray-500">{{ __('student.subscriptions.teacher_label') }}</span>
                    <p class="font-semibold text-gray-900">{{ $teacherName }}</p>
                </div>
                <div>
                    <span class="text-gray-500">{{ __('student.subscriptions.package_label') }}</span>
                    <p class="font-semibold text-gray-900">{{ $packageName }}</p>
                </div>
            </div>
            @if($sessionsRemaining > 0)
                <div class="mt-3 p-3 bg-blue-50 border border-blue-200 rounded-lg text-sm text-blue-700">
                    <i class="ri-information-line"></i>
                    {{ __('student.subscriptions.sessions_carryover', ['count' => $sessionsRemaining]) }}
                </div>
            @endif
        </div>

        {{-- Renewal Form --}}
        <form method="POST" action="{{ route('student.subscriptions.renew.process', ['subdomain' => $subdomain, 'type' => $type, 'id' => $subscription->id]) }}" class="px-6 py-6">
            @csrf
            <input type="hidden" name="mode" value="{{ $mode }}">

            {{-- Billing Cycle --}}
            <div class="mb-6">
                <label class="block text-sm font-semibold text-gray-700 mb-3">{{ __('student.subscriptions.select_billing_cycle') }}</label>
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                    @foreach($options['packages'] as $pkg)
                        @php $isCurrentPkg = $pkg['id'] === ($options['current']['package_id'] ?? null); @endphp
                    @endforeach

                    @foreach(['monthly', 'quarterly', 'yearly'] as $cycle)
                        @php
                            $currentPkg = collect($options['packages'])->firstWhere('id', $options['current']['package_id'] ?? null)
                                ?? ($options['packages'][0] ?? null);
                            $price = match($cycle) {
                                'monthly' => $currentPkg['monthly_price'] ?? 0,
                                'quarterly' => $currentPkg['quarterly_price'] ?? 0,
                                'yearly' => $currentPkg['yearly_price'] ?? 0,
                            };
                            $cycleLabel = match($cycle) {
                                'monthly' => __('student.subscriptions.billing_monthly'),
                                'quarterly' => __('student.subscriptions.billing_quarterly'),
                                'yearly' => __('student.subscriptions.billing_yearly'),
                            };
                            $isCurrentCycle = ($options['current']['billing_cycle'] ?? 'monthly') === $cycle;
                        @endphp
                        <label class="cursor-pointer">
                            <input type="radio" name="billing_cycle" value="{{ $cycle }}" {{ $isCurrentCycle ? 'checked' : '' }} class="peer sr-only">
                            <div class="text-center p-4 rounded-xl border-2 border-gray-200 peer-checked:border-indigo-600 peer-checked:bg-indigo-50 transition-all">
                                <div class="text-sm font-medium text-gray-600 peer-checked:text-indigo-700">{{ $cycleLabel }}</div>
                                <div class="text-xl font-bold text-gray-900 mt-1">{{ number_format($price, 2) }}</div>
                                <div class="text-xs text-gray-500">{{ $currentPkg['currency'] ?? 'SAR' }}</div>
                                @if($isCurrentCycle)
                                    <span class="inline-block mt-1 text-xs text-indigo-600 font-medium">{{ __('student.subscriptions.current_cycle') }}</span>
                                @endif
                            </div>
                        </label>
                    @endforeach
                </div>
            </div>

            @if (session('error'))
                <div class="mb-4 p-3 bg-red-50 border border-red-200 rounded-lg text-sm text-red-700">{{ session('error') }}</div>
            @endif

            {{-- Submit --}}
            <button type="submit"
                    class="w-full min-h-[48px] px-6 py-3 bg-indigo-600 text-white rounded-xl text-base font-semibold hover:bg-indigo-700 transition-colors shadow-sm">
                <i class="ri-secure-payment-line ms-2"></i>
                {{ __('student.subscriptions.proceed_to_payment') }}
            </button>
        </form>
    </div>
</div>

</x-layouts.student>
