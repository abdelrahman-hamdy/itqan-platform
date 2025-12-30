@props([
    'teacher',
    'package',
    'type',                      // 'quran' | 'academic'
    'formAction',
    'cancelUrl',
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

    // Get currency from package or academy
    $currency = $package->currency ?? $academy->currency ?? 'SAR';

    // Get prices with fallbacks
    $monthlyPrice = $package->monthly_price ?? 0;
    $quarterlyPrice = $package->quarterly_price ?? ($monthlyPrice * 3 * 0.9);
    $yearlyPrice = $package->yearly_price ?? ($monthlyPrice * 12 * 0.8);

    // Calculate actual savings percentages
    $quarterlyFullPrice = $monthlyPrice * 3;
    $yearlyfullPrice = $monthlyPrice * 12;
    $quarterlySavings = $quarterlyFullPrice > 0 ? round((($quarterlyFullPrice - $quarterlyPrice) / $quarterlyFullPrice) * 100) : 0;
    $yearlySavings = $yearlyfullPrice > 0 ? round((($yearlyfullPrice - $yearlyPrice) / $yearlyfullPrice) * 100) : 0;
@endphp

<div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
    <div class="mb-6">
        <h3 class="text-2xl font-bold text-gray-900 mb-2 flex items-center gap-2">
            @if($isQuran)
                <i class="ri-vip-crown-line text-primary"></i>
                {{ __('public.booking.quran.form.title') }}
            @else
                <i class="ri-graduation-cap-line text-primary"></i>
                {{ __('public.booking.academic.title') }}
            @endif
        </h3>
        <p class="text-gray-600">
            @if($isQuran)
                {{ __('public.booking.quran.form.subtitle') }}
            @else
                {{ __('public.booking.academic.subtitle') }}
            @endif
        </p>
    </div>

    <form action="{{ $formAction }}" method="POST" class="space-y-6"
          x-data="{
              billingCycle: '{{ old('billing_cycle', $selectedPeriod) ?: 'monthly' }}',
              prices: {
                  monthly: {{ (float) $monthlyPrice }},
                  quarterly: {{ (float) $quarterlyPrice }},
                  yearly: {{ (float) $yearlyPrice }}
              },
              currency: '{{ $currency }}',
              get currentPrice() {
                  return this.prices[this.billingCycle] || this.prices.monthly || 0;
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

        {{-- Error/Success Messages --}}
        <x-subscription.messages />

        {{-- Student Info Display --}}
        <x-subscription.student-info :user="auth()->user()" />

        {{-- Billing Cycle --}}
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-3">
                {{ __('public.booking.quran.form.billing_cycle') }}
            </label>
            {{-- Hidden input for form submission --}}
            <input type="hidden" name="billing_cycle" :value="billingCycle">

            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                {{-- Monthly --}}
                <div @click="billingCycle = 'monthly'"
                     class="cursor-pointer h-full p-4 border-2 rounded-lg transition-all"
                     :class="billingCycle === 'monthly' ? 'border-primary bg-primary/5 ring-2 ring-primary' : 'border-gray-200 hover:border-gray-300'">
                    <div class="h-full flex flex-col items-center justify-between text-center">
                        <div class="text-base font-semibold text-gray-900">{{ BillingCycle::MONTHLY->label() }}</div>
                        <div class="my-2 flex items-baseline gap-1" dir="ltr">
                            <span class="text-xl font-bold text-primary">{{ number_format($monthlyPrice) }} {{ $currency }}</span>
                            <span class="text-sm text-gray-500">{{ __('public.booking.quran.package_info.per_month') }}</span>
                        </div>
                        <div class="h-5"></div>
                    </div>
                </div>

                {{-- Quarterly --}}
                @if($package->quarterly_price)
                    <div @click="billingCycle = 'quarterly'"
                         class="cursor-pointer h-full p-4 border-2 rounded-lg transition-all"
                         :class="billingCycle === 'quarterly' ? 'border-primary bg-primary/5 ring-2 ring-primary' : 'border-gray-200 hover:border-gray-300'">
                        <div class="h-full flex flex-col items-center justify-between text-center">
                            <div class="text-base font-semibold text-gray-900">{{ BillingCycle::QUARTERLY->label() }}</div>
                            <div class="my-2 flex items-baseline gap-1" dir="ltr">
                                <span class="text-xl font-bold text-primary">{{ number_format($quarterlyPrice) }} {{ $currency }}</span>
                                <span class="text-sm text-gray-500">{{ __('public.booking.quran.package_info.per_quarter') }}</span>
                            </div>
                            <div class="h-5">
                                @if($quarterlySavings > 0)
                                    <span class="text-xs text-green-600 font-medium">{{ __('public.booking.quran.package_info.save_percent', ['percent' => $quarterlySavings]) }}</span>
                                @endif
                            </div>
                        </div>
                    </div>
                @endif

                {{-- Yearly --}}
                @if($package->yearly_price)
                    <div @click="billingCycle = 'yearly'"
                         class="cursor-pointer h-full p-4 border-2 rounded-lg transition-all"
                         :class="billingCycle === 'yearly' ? 'border-primary bg-primary/5 ring-2 ring-primary' : 'border-gray-200 hover:border-gray-300'">
                        <div class="h-full flex flex-col items-center justify-between text-center">
                            <div class="text-base font-semibold text-gray-900">{{ BillingCycle::YEARLY->label() }}</div>
                            <div class="my-2 flex items-baseline gap-1" dir="ltr">
                                <span class="text-xl font-bold text-primary">{{ number_format($yearlyPrice) }} {{ $currency }}</span>
                                <span class="text-sm text-gray-500">{{ __('public.booking.quran.package_info.per_year') }}</span>
                            </div>
                            <div class="h-5">
                                @if($yearlySavings > 0)
                                    <span class="text-xs text-green-600 font-medium">{{ __('public.booking.quran.package_info.save_percent', ['percent' => $yearlySavings]) }}</span>
                                @endif
                            </div>
                        </div>
                    </div>
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
                <label for="current_level" class="block text-sm font-medium text-gray-700 mb-2">
                    {{ __('public.booking.quran.form.current_level_label') }} *
                </label>
                <select id="current_level" name="current_level" required
                        class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-primary focus:border-primary @error('current_level') border-red-500 @enderror">
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
                <label class="block text-sm font-medium text-gray-700 mb-2">
                    {{ __('public.booking.quran.form.learning_goals_label') }} *
                </label>
                <div class="space-y-2">
                    @foreach(LearningGoal::cases() as $goal)
                        <label class="flex items-center gap-2 cursor-pointer">
                            <input type="checkbox" name="learning_goals[]" value="{{ $goal->value }}"
                                   {{ in_array($goal->value, old('learning_goals', [])) ? 'checked' : '' }}
                                   class="text-primary focus:ring-primary border-gray-300 rounded">
                            <span>{{ $goal->label() }}</span>
                        </label>
                    @endforeach
                </div>
                @error('learning_goals')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>
        @else
            {{-- Academic: Subject & Grade Level --}}
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                {{-- Subject Selection --}}
                <div>
                    <label for="subject_id" class="block text-sm font-medium text-gray-700 mb-2 flex items-center gap-1">
                        <i class="ri-book-line text-primary"></i>
                        {{ __('public.booking.academic.subject') }} *
                    </label>
                    <select id="subject_id" name="subject_id" required
                            class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-primary focus:border-primary @error('subject_id') border-red-500 @enderror">
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

                {{-- Grade Level Selection --}}
                <div>
                    <label for="grade_level_id" class="block text-sm font-medium text-gray-700 mb-2 flex items-center gap-1">
                        <i class="ri-school-line text-green-500"></i>
                        {{ __('public.booking.academic.grade_level') }} *
                    </label>
                    <select id="grade_level_id" name="grade_level_id" required
                            class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-primary focus:border-primary @error('grade_level_id') border-red-500 @enderror">
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

        {{-- Preferred Schedule (Shared) --}}
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-3">
                {{ __('public.booking.quran.form.preferred_schedule') }}
            </label>
            <div class="grid grid-cols-2 md:grid-cols-4 gap-3 mb-4">
                @foreach(WeekDays::cases() as $day)
                    <label class="flex items-center gap-2 cursor-pointer">
                        <input type="checkbox" name="preferred_days[]" value="{{ $day->value }}"
                               {{ in_array($day->value, old('preferred_days', [])) ? 'checked' : '' }}
                               class="text-primary focus:ring-primary border-gray-300 rounded">
                        <span class="text-sm">{{ $day->label() }}</span>
                    </label>
                @endforeach
            </div>

            <select name="preferred_time" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-primary focus:border-primary">
                <option value="">{{ __('public.booking.quran.form.preferred_time') }}</option>
                @foreach(TimeSlot::cases() as $slot)
                    <option value="{{ $slot->value }}" {{ old('preferred_time') == $slot->value ? 'selected' : '' }}>
                        {{ $slot->label() }}
                    </option>
                @endforeach
            </select>
        </div>

        {{-- Additional Notes (Shared) --}}
        <div>
            <label for="notes" class="block text-sm font-medium text-gray-700 mb-2">
                {{ __('public.booking.quran.form.notes') }}
            </label>
            <textarea id="notes" name="notes" rows="4"
                      placeholder="{{ __('public.booking.quran.form.notes_placeholder') }}"
                      class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-primary focus:border-primary">{{ old('notes') }}</textarea>
        </div>

        {{-- Pricing Summary (Shared) --}}
        <div class="bg-gray-50 border border-gray-200 rounded-lg p-6">
            <h4 class="font-semibold text-gray-900 mb-4 flex items-center gap-2">
                <i class="ri-calculator-line text-primary"></i>
                {{ __('public.booking.quran.form.cost_summary') }}
            </h4>
            <div class="space-y-2">
                <div class="flex justify-between items-center">
                    <span>
                        {{ __('public.booking.quran.form.package_price') }}
                        (<span x-text="periodLabel"></span>)
                    </span>
                    <span dir="ltr">
                        <span x-text="currentPrice.toLocaleString()"></span>
                        {{ $currency }}
                    </span>
                </div>
                <div class="flex justify-between items-center">
                    <span>{{ __('public.booking.quran.form.service_fee') }}</span>
                    <span dir="ltr">0 {{ $currency }}</span>
                </div>
                <div class="border-t border-gray-300 pt-2 flex justify-between items-center font-bold text-lg">
                    <span>{{ __('public.booking.quran.form.total') }}</span>
                    <span class="text-primary" dir="ltr">
                        <span x-text="currentPrice.toLocaleString()"></span>
                        {{ $currency }}
                    </span>
                </div>
            </div>
        </div>

        {{-- Submit Buttons (Shared) --}}
        <div class="flex flex-col sm:flex-row gap-4">
            <button type="submit"
                    class="flex-1 bg-primary text-white py-3 px-6 rounded-lg font-medium hover:opacity-90 transition-colors flex items-center justify-center gap-2">
                <i class="ri-secure-payment-line"></i>
                {{ __('public.booking.quran.form.submit') }}
            </button>

            <a href="{{ $cancelUrl }}"
               class="flex items-center justify-center gap-2 px-6 py-3 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 transition-colors">
                <i class="ri-arrow-go-back-line"></i>
                <span>{{ __('public.booking.quran.form.cancel') }}</span>
            </a>
        </div>
    </form>
</div>

{{-- Client-side Validation --}}
<script>
document.addEventListener('DOMContentLoaded', function() {
    const form = document.querySelector('form');
    if (!form) return;

    function showError(message) {
        const existingError = document.querySelector('.validation-error');
        if (existingError) existingError.remove();

        const errorDiv = document.createElement('div');
        errorDiv.className = 'validation-error bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-lg mb-6';
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

    form.addEventListener('submit', function(e) {
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

        const existingError = document.querySelector('.validation-error');
        if (existingError) existingError.remove();
    });
});
</script>
