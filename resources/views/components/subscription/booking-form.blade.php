@props([
    'teacher',
    'package',
    'type',                      // 'quran' | 'academic'
    'formAction',
    'academy',
    'subjects' => [],            // academic only
    'gradeLevels' => [],         // academic only
    'selectedPeriod' => 'monthly',
])

@php
    use App\Enums\BillingCycle;
    use App\Enums\WeekDays;
    use App\Enums\QuranLearningLevel;
    use App\Enums\LearningGoal;
    use App\Enums\TimeSlot;

    $isQuran = $type === 'quran';
    $isAcademic = $type === 'academic';

    // Ensure subjects and gradeLevels are arrays
    if (is_string($subjects)) {
        $subjects = json_decode($subjects, true) ?: [];
    }
    if (is_string($gradeLevels)) {
        $gradeLevels = json_decode($gradeLevels, true) ?: [];
    }

    // Always use academy's currency (ignore package's stored currency)
    $currency = getCurrencySymbol(null, $academy);

    // Original prices with fallbacks
    $monthlyPrice = $package->monthly_price ?? 0;
    $quarterlyPrice = $package->quarterly_price ?? ($monthlyPrice * 3 * 0.9);
    $yearlyPrice = $package->yearly_price ?? ($monthlyPrice * 12 * 0.8);

    // Sale prices (null = no sale)
    $saleMonthlyPrice = $package->sale_monthly_price;
    $saleQuarterlyPrice = $package->sale_quarterly_price;
    $saleYearlyPrice = $package->sale_yearly_price;

    // Effective prices via trait
    $effectiveMonthly = $package->getEffectivePriceForBillingCycle('monthly') ?? $monthlyPrice;
    $effectiveQuarterly = $package->getEffectivePriceForBillingCycle('quarterly') ?? $quarterlyPrice;
    $effectiveYearly = $package->getEffectivePriceForBillingCycle('yearly') ?? $yearlyPrice;

    // Savings percentages (quarterly/yearly vs monthly baseline)
    $quarterlyFullPrice = $monthlyPrice * 3;
    $yearlyFullPrice = $monthlyPrice * 12;
    $quarterlySavings = $quarterlyFullPrice > 0 ? round((($quarterlyFullPrice - $quarterlyPrice) / $quarterlyFullPrice) * 100) : 0;
    $yearlySavings = $yearlyFullPrice > 0 ? round((($yearlyFullPrice - $yearlyPrice) / $yearlyFullPrice) * 100) : 0;
@endphp

@livewire('payment.payment-gateway-modal', ['academyId' => $academy->id])

<div class="px-6 py-6">
    <form id="subscription-booking-form" action="{{ $formAction }}" method="POST" class="space-y-6"
          x-data="{
              billingCycle: '{{ old('billing_cycle', $selectedPeriod) ?: 'monthly' }}',
              prices: {
                  monthly: {{ (float) $effectiveMonthly }},
                  quarterly: {{ (float) $effectiveQuarterly }},
                  yearly: {{ (float) $effectiveYearly }}
              },
              originalPrices: {
                  monthly: {{ (float) $monthlyPrice }},
                  quarterly: {{ (float) $quarterlyPrice }},
                  yearly: {{ (float) $yearlyPrice }}
              },
              hasSale: {
                  monthly: {{ $saleMonthlyPrice ? 'true' : 'false' }},
                  quarterly: {{ $saleQuarterlyPrice ? 'true' : 'false' }},
                  yearly: {{ $saleYearlyPrice ? 'true' : 'false' }}
              },
              currency: '{{ $currency }}',
              get currentPrice() {
                  return this.prices[this.billingCycle] || this.prices.monthly || 0;
              },
              get currentOriginalPrice() {
                  return this.originalPrices[this.billingCycle] || this.originalPrices.monthly || 0;
              },
              get currentHasSale() {
                  return this.hasSale[this.billingCycle] || false;
              },
              get periodLabel() {
                  const labels = {
                      monthly: '{{ __('public.booking.quran.form.monthly') }}',
                      quarterly: '{{ __('public.booking.quran.form.quarterly') }}',
                      yearly: '{{ __('public.booking.quran.form.yearly') }}'
                  };
                  return labels[this.billingCycle] || labels.monthly;
              }
          }"
    >
        @csrf
        <input type="hidden" name="teacher_id" value="{{ $teacher->id }}">
        <input type="hidden" name="package_id" value="{{ $package->id }}">
        <input type="hidden" name="academy_id" value="{{ $academy->id }}">
        <input type="hidden" name="payment_gateway" id="payment_gateway_input">

        {{-- Billing Cycle --}}
        <div>
            <label class="block text-sm font-semibold text-gray-700 mb-3">
                {{ __('public.booking.quran.form.billing_cycle') }}
            </label>
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                {{-- Monthly --}}
                <label class="cursor-pointer h-full">
                    <input type="radio" name="billing_cycle" value="monthly"
                           x-model="billingCycle"
                           class="peer sr-only">
                    <div class="text-center p-4 rounded-xl border-2 border-gray-200 peer-checked:border-indigo-600 peer-checked:bg-indigo-50 transition-all h-full flex flex-col items-center justify-center">
                        <div class="text-sm font-medium text-gray-600">{{ BillingCycle::MONTHLY->label() }}</div>
                        <div class="text-xl font-bold text-gray-900 mt-1" dir="ltr">
                            {{ number_format($saleMonthlyPrice ?? $monthlyPrice) }} {{ $currency }}
                        </div>
                        <div class="text-xs text-gray-500">{{ __('public.booking.quran.package_info.per_month') }}</div>
                        <div class="h-4 mt-1">
                            @if($saleMonthlyPrice)
                                <span class="text-[11px] text-gray-400">{{ __('components.packages.instead_of', ['price' => number_format($monthlyPrice) . ' ' . $currency]) }}</span>
                            @endif
                        </div>
                    </div>
                </label>

                {{-- Quarterly --}}
                @if($package->quarterly_price)
                    <label class="cursor-pointer h-full">
                        <input type="radio" name="billing_cycle" value="quarterly"
                               x-model="billingCycle"
                               class="peer sr-only">
                        <div class="text-center p-4 rounded-xl border-2 border-gray-200 peer-checked:border-indigo-600 peer-checked:bg-indigo-50 transition-all h-full flex flex-col items-center justify-center">
                            <div class="text-sm font-medium text-gray-600">{{ BillingCycle::QUARTERLY->label() }}</div>
                            <div class="text-xl font-bold text-gray-900 mt-1" dir="ltr">
                                {{ number_format($saleQuarterlyPrice ?? $quarterlyPrice) }} {{ $currency }}
                            </div>
                            <div class="text-xs text-gray-500">{{ __('public.booking.quran.package_info.per_quarter') }}</div>
                            <div class="h-4 mt-1">
                                @if($saleQuarterlyPrice)
                                    <span class="text-[11px] text-gray-400">{{ __('components.packages.instead_of', ['price' => number_format($quarterlyPrice) . ' ' . $currency]) }}</span>
                                @elseif($quarterlySavings > 0)
                                    <span class="text-xs text-green-600 font-medium">{{ __('public.booking.quran.package_info.save_percent', ['percent' => $quarterlySavings]) }}</span>
                                @endif
                            </div>
                        </div>
                    </label>
                @endif

                {{-- Yearly --}}
                @if($package->yearly_price)
                    <label class="cursor-pointer h-full">
                        <input type="radio" name="billing_cycle" value="yearly"
                               x-model="billingCycle"
                               class="peer sr-only">
                        <div class="text-center p-4 rounded-xl border-2 border-gray-200 peer-checked:border-indigo-600 peer-checked:bg-indigo-50 transition-all h-full flex flex-col items-center justify-center">
                            <div class="text-sm font-medium text-gray-600">{{ BillingCycle::YEARLY->label() }}</div>
                            <div class="text-xl font-bold text-gray-900 mt-1" dir="ltr">
                                {{ number_format($saleYearlyPrice ?? $yearlyPrice) }} {{ $currency }}
                            </div>
                            <div class="text-xs text-gray-500">{{ __('public.booking.quran.package_info.per_year') }}</div>
                            <div class="h-4 mt-1">
                                @if($saleYearlyPrice)
                                    <span class="text-[11px] text-gray-400">{{ __('components.packages.instead_of', ['price' => number_format($yearlyPrice) . ' ' . $currency]) }}</span>
                                @elseif($yearlySavings > 0)
                                    <span class="text-xs text-green-600 font-medium">{{ __('public.booking.quran.package_info.save_percent', ['percent' => $yearlySavings]) }}</span>
                                @endif
                            </div>
                        </div>
                    </label>
                @endif
            </div>
            @error('billing_cycle')
                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
            @enderror
        </div>

        {{-- Type-Specific Fields --}}
        @if($isQuran)
            {{-- Quran: Current Level --}}
            <div>
                <label for="current_level" class="block text-sm font-semibold text-gray-700 mb-2">
                    {{ __('public.booking.quran.form.current_level_label') }} *
                </label>
                <select id="current_level" name="current_level" required
                        class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-indigo-500 focus:border-indigo-500 @error('current_level') border-red-500 @enderror">
                    <option value="">{{ __('public.booking.quran.form.select_level') }}</option>
                    @foreach(QuranLearningLevel::cases() as $level)
                        <option value="{{ $level->value }}" {{ old('current_level') == $level->value ? 'selected' : '' }}>
                            {{ $level->label() }}
                        </option>
                    @endforeach
                </select>
                @error('current_level')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            {{-- Quran: Learning Goals --}}
            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-2">
                    {{ __('public.booking.quran.form.learning_goals_label') }} *
                </label>
                <div class="space-y-2">
                    @foreach(LearningGoal::cases() as $goal)
                        <label class="flex items-center gap-2 cursor-pointer">
                            <input type="checkbox" name="learning_goals[]" value="{{ $goal->value }}"
                                   {{ in_array($goal->value, old('learning_goals', [])) ? 'checked' : '' }}
                                   class="text-indigo-600 focus:ring-indigo-500 border-gray-300 rounded">
                            <span class="text-sm">{{ $goal->label() }}</span>
                        </label>
                    @endforeach
                </div>
                @error('learning_goals')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>
        @else
            {{-- Academic: Subject & Grade Level --}}
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label for="subject_id" class="block text-sm font-semibold text-gray-700 mb-2 flex items-center gap-1">
                        <i class="ri-book-line text-indigo-500"></i>
                        {{ __('public.booking.academic.subject') }} *
                    </label>
                    <select id="subject_id" name="subject_id" required
                            class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-indigo-500 focus:border-indigo-500 @error('subject_id') border-red-500 @enderror">
                        <option value="">{{ __('public.booking.academic.select_subject') }}</option>
                        @foreach($subjects as $subjectId => $subjectName)
                            <option value="{{ $subjectId }}" {{ old('subject_id') == $subjectId ? 'selected' : '' }}>
                                {{ $subjectName }}
                            </option>
                        @endforeach
                    </select>
                    @error('subject_id')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="grade_level_id" class="block text-sm font-semibold text-gray-700 mb-2 flex items-center gap-1">
                        <i class="ri-school-line text-green-500"></i>
                        {{ __('public.booking.academic.grade_level') }} *
                    </label>
                    <select id="grade_level_id" name="grade_level_id" required
                            class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-indigo-500 focus:border-indigo-500 @error('grade_level_id') border-red-500 @enderror">
                        <option value="">{{ __('public.booking.academic.select_grade_level') }}</option>
                        @foreach($gradeLevels as $gradeLevelId => $gradeLevelName)
                            <option value="{{ $gradeLevelId }}" {{ old('grade_level_id') == $gradeLevelId ? 'selected' : '' }}>
                                {{ $gradeLevelName }}
                            </option>
                        @endforeach
                    </select>
                    @error('grade_level_id')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>
            </div>
        @endif

        {{-- Preferred Schedule --}}
        <div>
            <label class="block text-sm font-semibold text-gray-700 mb-3">
                {{ __('public.booking.quran.form.preferred_schedule') }}
            </label>
            <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 mb-4">
                @foreach(WeekDays::cases() as $day)
                    <label class="flex items-center gap-2 cursor-pointer">
                        <input type="checkbox" name="preferred_days[]" value="{{ $day->value }}"
                               {{ in_array($day->value, old('preferred_days', [])) ? 'checked' : '' }}
                               class="text-indigo-600 focus:ring-indigo-500 border-gray-300 rounded">
                        <span class="text-sm">{{ $day->label() }}</span>
                    </label>
                @endforeach
            </div>

            <select name="preferred_time" class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-indigo-500 focus:border-indigo-500">
                <option value="">{{ __('public.booking.quran.form.preferred_time') }}</option>
                @foreach(TimeSlot::cases() as $slot)
                    <option value="{{ $slot->value }}" {{ old('preferred_time') == $slot->value ? 'selected' : '' }}>
                        {{ $slot->label() }}
                    </option>
                @endforeach
            </select>
        </div>

        {{-- Additional Notes --}}
        <div>
            <label for="notes" class="block text-sm font-semibold text-gray-700 mb-2">
                {{ __('public.booking.quran.form.notes') }}
            </label>
            <textarea id="notes" name="notes" rows="3"
                      placeholder="{{ __('public.booking.quran.form.notes_placeholder') }}"
                      class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-indigo-500 focus:border-indigo-500">{{ old('notes') }}</textarea>
        </div>

        {{-- Pricing Summary --}}
        <div class="bg-gray-50 border border-gray-200 rounded-xl p-5">
            <h4 class="font-semibold text-gray-900 mb-3 flex items-center gap-2 text-sm">
                <i class="ri-price-tag-3-line text-indigo-600"></i>
                {{ __('public.booking.quran.form.cost_summary') }}
            </h4>
            <div class="space-y-2 text-sm">
                <div x-show="currentHasSale" x-cloak>
                    <div class="flex justify-between items-center text-gray-500">
                        <span>{{ __('components.packages.original_price') }}</span>
                        <span dir="ltr" class="line-through">
                            <span x-text="currentOriginalPrice.toLocaleString()"></span>
                            {{ $currency }}
                        </span>
                    </div>
                    <div class="flex justify-between items-center text-green-600 mt-1">
                        <span>{{ __('public.booking.quran.form.package_price') }} (<span x-text="periodLabel"></span>)</span>
                        <span dir="ltr">
                            <span x-text="currentPrice.toLocaleString()"></span>
                            {{ $currency }}
                        </span>
                    </div>
                </div>
                <div class="flex justify-between items-center font-bold pt-2" :class="currentHasSale && 'border-t border-gray-200'">
                    <span class="text-gray-900">{{ __('public.booking.quran.form.total') }}</span>
                    <span class="text-indigo-600 text-lg" dir="ltr">
                        <span x-text="currentPrice.toLocaleString()"></span>
                        {{ $currency }}
                    </span>
                </div>
            </div>
        </div>

        {{-- Submit Button --}}
        <button type="submit"
                class="w-full min-h-[48px] px-6 py-3 bg-indigo-600 text-white rounded-xl text-base font-semibold hover:bg-indigo-700 transition-colors shadow-sm">
            <i class="ri-secure-payment-line ms-2"></i>
            {{ __('public.booking.quran.form.submit') }}
        </button>
    </form>
</div>

{{-- Client-side Validation + Gateway Selection --}}
<script>
document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('subscription-booking-form');
    if (!form) return;

    let gatewaySelected = false;

    function showError(message) {
        const existingError = document.querySelector('.validation-error');
        if (existingError) existingError.remove();

        const errorDiv = document.createElement('div');
        errorDiv.className = 'validation-error bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-xl mb-6';
        errorDiv.innerHTML = `
            <div class="flex gap-2">
                <i class="ri-error-warning-line text-red-500 mt-0.5"></i>
                <div>
                    <h4 class="font-medium mb-1">{{ __('public.booking.quran.errors.form_error') }}</h4>
                    <p class="text-sm">${message}</p>
                </div>
            </div>
        `;

        form.insertBefore(errorDiv, form.firstChild);
        errorDiv.scrollIntoView({ behavior: 'smooth', block: 'center' });
    }

    // Listen for gateway selection from Livewire modal
    if (typeof Livewire !== 'undefined') {
        Livewire.on('gatewaySelected', ({ gateway }) => {
            document.getElementById('payment_gateway_input').value = gateway;
            gatewaySelected = true;
            form.submit();
        });

        Livewire.on('gatewaySelectionError', ({ message }) => {
            showError(message);
        });
    }

    form.addEventListener('submit', function(e) {
        // If gateway already selected, allow form submission
        if (gatewaySelected) return;

        const errors = [];
        const billingCycleInput = form.querySelector('[name="billing_cycle"]');

        if (!billingCycleInput || !billingCycleInput.value) {
            errors.push('{{ __('public.booking.quran.errors.billing_cycle') }}');
        }

        @if($isQuran)
        const currentLevel = form.querySelector('[name="current_level"]');
        const learningGoals = form.querySelectorAll('[name="learning_goals[]"]:checked');

        if (!currentLevel?.value) {
            errors.push('{{ __('public.booking.quran.errors.current_level') }}');
        }
        if (learningGoals.length === 0) {
            errors.push('{{ __('public.booking.quran.errors.learning_goals') }}');
        }
        @else
        const subjectId = form.querySelector('[name="subject_id"]');
        const gradeLevelId = form.querySelector('[name="grade_level_id"]');

        if (!subjectId?.value) {
            errors.push('{{ __('public.booking.academic.select_subject') }}');
        }
        if (!gradeLevelId?.value) {
            errors.push('{{ __('public.booking.academic.select_grade_level') }}');
        }
        @endif

        if (errors.length > 0) {
            e.preventDefault();
            showError(errors.join('<br>• '));
            return false;
        }

        // Validation passed - show gateway selection modal instead of submitting
        e.preventDefault();
        const existingError = document.querySelector('.validation-error');
        if (existingError) existingError.remove();

        if (typeof Livewire === 'undefined') {
            showError('{{ __('public.booking.errors.payment_unavailable') }}');
            return false;
        }

        Livewire.dispatch('openGatewaySelection');
    });
});
</script>
