<x-layouts.student>

@php
    $subdomain = request()->route('subdomain') ?? auth()->user()->academy->subdomain ?? 'itqan-academy';
    $sessionsRemaining = method_exists($subscription, 'getSessionsRemaining') ? $subscription->getSessionsRemaining() : 0;
    $teacherName = $type === 'quran'
        ? ($subscription->quranTeacherUser?->name ?? '-')
        : ($subscription->teacher?->user?->name ?? '-');
    $packageName = $subscription->package_name_ar ?? $subscription->package?->name ?? '-';
    $isResubscribe = $mode === 'resubscribe';
    $currentPackageId = $options['current']['package_id'] ?? null;
    $currentBillingCycle = $options['current']['billing_cycle'] ?? 'monthly';
@endphp

<div class="max-w-2xl mx-auto px-4 py-8">
    <x-ui.breadcrumb
        :items="[
            ['label' => __('student.subscriptions.page_title'), 'url' => route('student.subscriptions', ['subdomain' => $subdomain])],
            ['label' => $isResubscribe ? __('student.subscriptions.resubscribe') : __('student.subscriptions.renew')],
        ]"
        view-type="student"
    />

    <div class="bg-white rounded-2xl shadow-sm border border-gray-200 overflow-hidden mt-6"
         x-data="{
            packages: {{ Js::from($options['packages']) }},
            selectedPackageId: {{ $currentPackageId ?? 'null' }},
            selectedCycle: '{{ $currentBillingCycle }}',
            get selectedPackage() {
                return this.packages.find(p => p.id === this.selectedPackageId) || this.packages[0] || {};
            },
            getPrice(cycle) {
                const pkg = this.selectedPackage;
                return pkg[`effective_${cycle}_price`] ?? pkg[`${cycle}_price`] ?? 0;
            },
            getOriginalPrice(cycle) {
                const pkg = this.selectedPackage;
                return pkg[`${cycle}_price`] ?? 0;
            },
            hasSaleFor(cycle) {
                const pkg = this.selectedPackage;
                return pkg[`sale_${cycle}_price`] != null;
            },
            formatPrice(price) {
                return new Intl.NumberFormat('en', { minimumFractionDigits: 2, maximumFractionDigits: 2 }).format(price);
            }
         }">

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
                    <span class="text-gray-500">{{ __('student.subscriptions.current_package_label') }}</span>
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

            {{-- Package Selection --}}
            @if(count($options['packages']) > 1)
                <div class="mb-6">
                    <label class="block text-sm font-semibold text-gray-700 mb-3">{{ __('student.subscriptions.select_package') }}</label>
                    <div class="grid grid-cols-1 gap-3">
                        @foreach($options['packages'] as $pkg)
                            <label class="cursor-pointer" @click="selectedPackageId = {{ $pkg['id'] }}">
                                <input type="radio" name="package_id" value="{{ $pkg['id'] }}"
                                       {{ ($currentPackageId == $pkg['id']) ? 'checked' : '' }}
                                       x-model.number="selectedPackageId"
                                       class="peer sr-only">
                                <div class="flex items-center justify-between p-4 rounded-xl border-2 border-gray-200 peer-checked:border-indigo-600 peer-checked:bg-indigo-50 transition-all">
                                    <div>
                                        <div class="font-semibold text-gray-900">{{ $pkg['name'] }}</div>
                                        <div class="text-sm text-gray-500 mt-0.5">
                                            {{ $pkg['sessions_per_month'] }} {{ __('student.subscriptions.sessions_per_month') }}
                                            &middot; {{ $pkg['session_duration_minutes'] }} {{ __('student.subscriptions.minutes_per_session') }}
                                        </div>
                                    </div>
                                    <div class="text-left">
                                        <div class="text-lg font-bold text-gray-900">
                                            {{ number_format($pkg['sale_monthly_price'] ?? $pkg['monthly_price'], 2) }}
                                        </div>
                                        @if(!empty($pkg['sale_monthly_price']))
                                            <div class="text-[11px] text-gray-400 line-through">
                                                {{ number_format($pkg['monthly_price'], 2) }}
                                            </div>
                                        @endif
                                        <div class="text-xs text-gray-500">{{ $pkg['currency'] ?? 'SAR' }}/{{ __('student.subscriptions.billing_monthly') }}</div>
                                    </div>
                                    @if($currentPackageId == $pkg['id'])
                                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-indigo-100 text-indigo-700">
                                            {{ __('student.subscriptions.current_package') }}
                                        </span>
                                    @endif
                                </div>
                            </label>
                        @endforeach
                    </div>
                </div>
            @else
                <input type="hidden" name="package_id" value="{{ $currentPackageId }}">
            @endif

            {{-- Billing Cycle --}}
            <div class="mb-6">
                <label class="block text-sm font-semibold text-gray-700 mb-3">{{ __('student.subscriptions.select_billing_cycle') }}</label>
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                    @foreach(['monthly', 'quarterly', 'yearly'] as $cycle)
                        @php
                            $cycleLabel = match($cycle) {
                                'monthly' => __('student.subscriptions.billing_monthly'),
                                'quarterly' => __('student.subscriptions.billing_quarterly'),
                                'yearly' => __('student.subscriptions.billing_yearly'),
                            };
                            $isCurrentCycle = $currentBillingCycle === $cycle;
                        @endphp
                        <label class="cursor-pointer h-full" @click="selectedCycle = '{{ $cycle }}'">
                            <input type="radio" name="billing_cycle" value="{{ $cycle }}"
                                   {{ $isCurrentCycle ? 'checked' : '' }}
                                   x-model="selectedCycle"
                                   class="peer sr-only">
                            <div class="text-center p-4 rounded-xl border-2 border-gray-200 peer-checked:border-indigo-600 peer-checked:bg-indigo-50 transition-all h-full flex flex-col justify-center">
                                <div class="text-sm font-medium text-gray-600">{{ $cycleLabel }}</div>
                                <div class="text-xl font-bold text-gray-900 mt-1" x-text="formatPrice(getPrice('{{ $cycle }}'))">
                                    {{ number_format($options['packages'][0]['effective_'.$cycle.'_price'] ?? 0, 2) }}
                                </div>
                                <div class="text-[11px] text-gray-400 line-through h-4"
                                     x-text="hasSaleFor('{{ $cycle }}') ? formatPrice(getOriginalPrice('{{ $cycle }}')) : ''"></div>
                                <div class="text-xs text-gray-500" x-text="selectedPackage.currency || 'SAR'">SAR</div>
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
