<div>
    <x-ui.breadcrumb
        :items="[
            ['label' => __('supervisor.subscriptions.page_title'), 'url' => route('manage.subscriptions.index', ['subdomain' => auth()->user()->academy?->subdomain])],
            ['label' => __('subscriptions.create_full_subscription')],
        ]"
        view-type="supervisor"
    />

    <div class="mb-6">
        <h1 class="text-xl sm:text-2xl font-bold text-gray-900">{{ __('subscriptions.create_full_subscription') }}</h1>
    </div>

    @if (session('error'))
        <div class="mb-4 p-4 bg-red-50 border border-red-200 rounded-lg text-red-700">{{ session('error') }}</div>
    @endif

    {{-- ════════════════════════════════════════════
         Step Indicator (full width with labels)
    ════════════════════════════════════════════ --}}
    <nav class="mb-8" aria-label="Progress">
        <ol class="flex items-start">
            @foreach ($this->stepLabels as $step => $label)
                <li class="relative {{ !$loop->last ? 'flex-1' : '' }}">
                    <div class="flex items-center">
                        <div class="shrink-0 w-10 h-10 rounded-full flex items-center justify-center text-sm font-bold transition-all duration-300
                            @if($currentStep > $step) bg-green-500 text-white shadow-sm
                            @elseif($currentStep === $step) bg-primary-600 text-white ring-4 ring-primary-600/20 shadow-sm
                            @else bg-gray-100 text-gray-400 border-2 border-gray-200
                            @endif">
                            @if($currentStep > $step)
                                <i class="ri-check-line text-lg"></i>
                            @else
                                {{ $step }}
                            @endif
                        </div>
                        @if(!$loop->last)
                            <div class="flex-1 h-0.5 mx-3 rounded transition-colors duration-500 {{ $currentStep > $step ? 'bg-green-500' : 'bg-gray-200' }}"></div>
                        @endif
                    </div>
                    <div class="mt-2 pe-4">
                        <span class="text-xs sm:text-sm font-medium leading-tight transition-colors duration-300
                            {{ $currentStep >= $step ? 'text-gray-900' : 'text-gray-400' }}">
                            {{ $label }}
                        </span>
                    </div>
                </li>
            @endforeach
        </ol>
    </nav>

    {{-- ════════════════════════════════════════════
         Content Card (with loading overlay)
    ════════════════════════════════════════════ --}}
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6 transition-opacity duration-200"
         wire:loading.class="opacity-50 pointer-events-none" wire:target="nextStep,previousStep,submit">

        {{-- ═══ STEP 1: Type + Student + Teacher/Circle ═══ --}}
        @if ($currentStep === 1)
            @php
                $isQuranType = in_array($subscription_type, ['quran_individual', 'quran_group']);
                $isGroupType = $subscription_type === 'quran_group';
                $teacherUserType = $isQuranType ? 'quran_teacher' : 'academic_teacher';
            @endphp
            <h2 class="text-lg font-semibold mb-4">{{ __('subscriptions.wizard_step1_title') }}</h2>
            <div class="space-y-5">

                {{-- Subscription Type --}}
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">{{ __('subscriptions.subscription_type_label') }}</label>
                    <select wire:model.live="subscription_type" class="w-full rounded-lg border-gray-300">
                        <option value="quran_individual">{{ __('subscriptions.type_quran_individual') }}</option>
                        <option value="quran_group">{{ __('subscriptions.type_quran_group') }}</option>
                        <option value="academic">{{ __('subscriptions.type_academic') }}</option>
                    </select>
                </div>

                {{-- Student (Alpine-only: no dual rendering, no flicker) --}}
                <div x-data="{
                    selected: @js($student_id ? ['id' => $student_id, 'name' => $selectedStudentName, 'email' => $selectedStudentEmail] : null),
                    select(id, name, email) {
                        this.selected = { id, name, email };
                        $wire.set('student_id', id);
                        $wire.set('selectedStudentName', name);
                        $wire.set('selectedStudentEmail', email);
                        $wire.set('student_search', '');
                        $wire.set('searchResults', []);
                    },
                    clear() { this.selected = null; $wire.call('clearStudent'); }
                }">
                    <label class="block text-sm font-medium text-gray-700 mb-1">{{ __('subscriptions.student_label') }}</label>

                    {{-- Selected state --}}
                    <template x-if="selected">
                        <div class="flex items-center gap-3 p-3 bg-blue-50/60 border border-blue-200 rounded-lg">
                            <div class="w-8 h-8 rounded-full bg-blue-500 flex items-center justify-center text-white font-bold text-xs uppercase"
                                 x-text="selected.name ? selected.name.charAt(0) : '?'"></div>
                            <div class="flex-1 min-w-0">
                                <div class="font-semibold text-gray-900 truncate text-sm" x-text="selected.name"></div>
                                <div class="text-xs text-gray-500 truncate" x-text="selected.email"></div>
                            </div>
                            <button type="button" @click="clear()" class="p-1.5 text-gray-400 hover:text-red-500 rounded-lg transition-colors">
                                <i class="ri-close-line text-lg"></i>
                            </button>
                        </div>
                    </template>

                    {{-- Search state --}}
                    <template x-if="!selected">
                        <div>
                            <div class="relative">
                                <input type="text" wire:model.live.debounce.300ms="student_search"
                                    class="w-full rounded-lg border-gray-300 pe-10"
                                    placeholder="{{ __('subscriptions.search_student_placeholder') }}">
                                {{-- Search spinner --}}
                                <div wire:loading wire:target="student_search" class="absolute inset-y-0 end-3 flex items-center">
                                    <i class="ri-loader-4-line animate-spin text-gray-400"></i>
                                </div>
                            </div>
                            @if (count($searchResults) > 0)
                                @php $searchUserModels = \App\Models\User::whereIn('id', collect($searchResults)->pluck('id'))->get()->keyBy('id'); @endphp
                                <div class="mt-1 bg-white border rounded-lg shadow-lg max-h-48 overflow-y-auto z-10 relative">
                                    @foreach ($searchResults as $r)
                                        @php $ru = $searchUserModels->get($r['id']); @endphp
                                        @if($ru)
                                            <button type="button" @click="select({{ $r['id'] }}, {{ Js::from($r['name']) }}, {{ Js::from($r['email']) }})"
                                                class="w-full text-start px-3 py-2.5 hover:bg-gray-50 flex items-center gap-3 border-b border-gray-50 last:border-0">
                                                <x-avatar :user="$ru" size="xs" userType="student" />
                                                <div class="min-w-0">
                                                    <div class="text-sm font-medium text-gray-900 truncate">{{ $r['name'] }}</div>
                                                    <div class="text-xs text-gray-500 truncate">{{ $r['email'] }}</div>
                                                </div>
                                            </button>
                                        @endif
                                    @endforeach
                                </div>
                            @endif
                        </div>
                    </template>
                    @error('student_id') <span class="text-red-500 text-sm mt-1 block">{{ $message }}</span> @enderror
                </div>

                {{-- Group Circle selector --}}
                @if($isGroupType)
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">{{ __('subscriptions.select_circle') }}</label>
                        <select wire:model.live="quran_circle_id" class="w-full rounded-lg border-gray-300">
                            <option value="">{{ __('subscriptions.select_circle') }}</option>
                            @foreach ($availableCircles as $circle)
                                <option value="{{ $circle['id'] }}">{{ $circle['name'] }} — {{ $circle['teacher_name'] }} ({{ $circle['spots'] }} {{ __('subscriptions.spots_available') }})</option>
                            @endforeach
                        </select>
                        @error('quran_circle_id') <span class="text-red-500 text-sm mt-1 block">{{ $message }}</span> @enderror
                    </div>
                @endif

                {{-- Teacher (Alpine-only for selection, server avatars in list) --}}
                @if(!$isGroupType)
                    @php
                        $selectedTeacherData = $teacher_id ? collect($availableTeachers)->firstWhere('id', $teacher_id) : null;
                        $profileModel = $isQuranType ? \App\Models\QuranTeacherProfile::class : \App\Models\AcademicTeacherProfile::class;
                        $teacherProfileModels = $teacher_id
                            ? collect()
                            : $profileModel::whereIn('id', collect($availableTeachers)->pluck('id'))->with('user')->get()->keyBy('id');
                    @endphp
                    <div x-data="{
                        selected: @js($selectedTeacherData),
                        q: '',
                        select(t) {
                            this.selected = t; this.q = '';
                            $wire.set('teacher_id', t.id);
                        },
                        clear() { this.selected = null; this.q = ''; $wire.set('teacher_id', null); }
                    }">
                        <label class="block text-sm font-medium text-gray-700 mb-1">{{ __('subscriptions.teacher_label') }}</label>

                        {{-- Selected state --}}
                        <template x-if="selected">
                            <div class="flex items-center gap-3 p-3 {{ $isQuranType ? 'bg-yellow-50/60 border-yellow-200' : 'bg-violet-50/60 border-violet-200' }} border rounded-lg">
                                <div class="w-8 h-8 rounded-full {{ $isQuranType ? 'bg-yellow-500' : 'bg-violet-500' }} flex items-center justify-center text-white font-bold text-xs uppercase"
                                     x-text="selected.name ? selected.name.charAt(0) : '?'"></div>
                                <div class="flex-1 min-w-0">
                                    <div class="font-semibold text-gray-900 truncate text-sm" x-text="selected.name"></div>
                                </div>
                                <button type="button" @click="clear()" class="p-1.5 text-gray-400 hover:text-red-500 rounded-lg transition-colors">
                                    <i class="ri-close-line text-lg"></i>
                                </button>
                            </div>
                        </template>

                        {{-- List state --}}
                        <template x-if="!selected">
                            <div class="border rounded-lg overflow-hidden">
                                <div class="p-2 border-b bg-gray-50">
                                    <input type="text" x-model="q" class="w-full rounded-lg border-gray-300 text-sm"
                                        placeholder="{{ __('subscriptions.search_teacher_placeholder') }}">
                                </div>
                                <div class="max-h-48 overflow-y-auto p-1 space-y-0.5">
                                    @forelse ($availableTeachers as $t)
                                        @php $tUser = $teacherProfileModels->get($t['id'])?->user; @endphp
                                        <button type="button"
                                            x-show="!q || '{{ mb_strtolower($t['name']) }}'.includes(q.toLowerCase())"
                                            @click="select({{ Js::from($t) }})"
                                            class="w-full flex items-center gap-3 p-2.5 rounded-lg hover:bg-gray-50 text-start transition-colors">
                                            @if($tUser)<x-avatar :user="$tUser" size="xs" :userType="$teacherUserType" />@endif
                                            <span class="text-sm font-medium text-gray-900">{{ $t['name'] }}</span>
                                        </button>
                                    @empty
                                        <div class="p-3 text-sm text-gray-500 text-center">{{ __('subscriptions.no_teachers_available') }}</div>
                                    @endforelse
                                </div>
                            </div>
                        </template>
                        @error('teacher_id') <span class="text-red-500 text-sm mt-1 block">{{ $message }}</span> @enderror
                    </div>
                @endif
            </div>
        @endif

        {{-- ═══ STEP 2 (Group Circles): Enrollment & Payment ═══ --}}
        @if ($currentStep === 2 && $subscription_type === 'quran_group')
            <h2 class="text-lg font-semibold mb-4">{{ __('subscriptions.wizard_step3_title') }}</h2>
            <div class="space-y-4">

                {{-- Sponsored/Normal toggle --}}
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">{{ __('subscriptions.enrollment_type_label') }}</label>
                    <div class="flex gap-3">
                        <label class="flex-1 cursor-pointer">
                            <input type="radio" wire:model.live="is_sponsored" value="0" class="peer sr-only">
                            <div class="text-center p-3 rounded-lg border-2 border-gray-200 peer-checked:border-blue-600 peer-checked:bg-blue-50 transition-all">
                                <i class="ri-wallet-line text-lg text-blue-600 mb-1"></i>
                                <div class="text-sm font-medium">{{ __('subscriptions.normal_enrollment') }}</div>
                            </div>
                        </label>
                        <label class="flex-1 cursor-pointer">
                            <input type="radio" wire:model.live="is_sponsored" value="1" class="peer sr-only">
                            <div class="text-center p-3 rounded-lg border-2 border-gray-200 peer-checked:border-green-600 peer-checked:bg-green-50 transition-all">
                                <i class="ri-gift-line text-lg text-green-600 mb-1"></i>
                                <div class="text-sm font-medium">{{ __('subscriptions.sponsored_enrollment') }}</div>
                            </div>
                        </label>
                    </div>
                </div>

                @if(!$is_sponsored)
                    {{-- Price from circle fee --}}
                    <div class="bg-gray-50 border border-gray-200 rounded-lg p-4 space-y-3">
                        <div class="flex items-center justify-between">
                            <span class="text-sm text-gray-600">{{ __('subscriptions.package_price_label') }}</span>
                            <span class="text-lg font-bold text-gray-900">{{ number_format($amount, 2) }} {{ auth()->user()->academy?->currency?->value ?? 'SAR' }}</span>
                        </div>
                        <div>
                            <label class="block text-sm text-gray-600 mb-1">{{ __('subscriptions.discount_label') }}</label>
                            <input type="number" wire:model.live.debounce.300ms="discount" step="1" min="0" max="{{ $amount }}" class="w-full rounded-lg border-gray-300 text-sm" placeholder="0">
                        </div>
                        @if ($discount > 0)
                            <div class="flex items-center justify-between pt-2 border-t border-gray-200">
                                <span class="text-sm font-semibold text-gray-700">{{ __('subscriptions.final_price_label') }}</span>
                                <span class="text-lg font-bold text-green-600">{{ number_format($this->finalPrice, 2) }} {{ auth()->user()->academy?->currency?->value ?? 'SAR' }}</span>
                            </div>
                        @endif
                    </div>

                    {{-- Payment source --}}
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">{{ __('subscriptions.payment_source_label') }}</label>
                        <div class="flex gap-3">
                            <label class="flex-1 cursor-pointer">
                                <input type="radio" wire:model.live="payment_source" value="outside" class="peer sr-only">
                                <div class="text-center p-3 rounded-lg border-2 border-gray-200 peer-checked:border-green-600 peer-checked:bg-green-50 transition-all">
                                    <i class="ri-hand-coin-line text-lg text-green-600 mb-1"></i>
                                    <div class="text-sm font-medium">{{ __('subscriptions.paid_outside') }}</div>
                                    <div class="text-xs text-gray-500 mt-0.5">{{ __('subscriptions.paid_outside_desc') }}</div>
                                </div>
                            </label>
                            <label class="flex-1 cursor-pointer">
                                <input type="radio" wire:model.live="payment_source" value="inside" class="peer sr-only">
                                <div class="text-center p-3 rounded-lg border-2 border-gray-200 peer-checked:border-blue-600 peer-checked:bg-blue-50 transition-all">
                                    <i class="ri-bank-card-line text-lg text-blue-600 mb-1"></i>
                                    <div class="text-sm font-medium">{{ __('subscriptions.paid_inside') }}</div>
                                    <div class="text-xs text-gray-500 mt-0.5">{{ __('subscriptions.paid_inside_desc') }}</div>
                                </div>
                            </label>
                        </div>
                    </div>
                    @if ($payment_source === 'outside')
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">{{ __('subscriptions.payment_reference_label') }}</label>
                            <input type="text" wire:model="payment_reference" class="w-full rounded-lg border-gray-300" placeholder="{{ __('subscriptions.payment_reference_placeholder') }}">
                        </div>
                    @else
                        <div class="p-3 bg-blue-50 border border-blue-200 rounded-lg text-sm text-blue-700">
                            <i class="ri-information-line"></i> {{ __('subscriptions.pending_payment_notice') }}
                        </div>
                    @endif
                @else
                    <div class="p-3 bg-green-50 border border-green-200 rounded-lg text-sm text-green-700">
                        <i class="ri-gift-line"></i> {{ __('subscriptions.sponsored_enrollment_notice') }}
                    </div>
                @endif
            </div>
        @endif

        {{-- ═══ STEP 2 (Individual/Academic): Package & Pricing ═══ --}}
        @if ($currentStep === 2 && $subscription_type !== 'quran_group')
            <h2 class="text-lg font-semibold mb-4">{{ __('subscriptions.wizard_step2_title') }}</h2>
            <div class="space-y-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">{{ __('subscriptions.select_package') }}</label>
                    <select wire:model.live="package_id" class="w-full rounded-lg border-gray-300">
                        <option value="">{{ __('subscriptions.select_package') }}</option>
                        @foreach ($availablePackages as $pkg)
                            <option value="{{ $pkg['id'] }}">{{ $pkg['name'] }} ({{ $pkg['sessions_per_month'] }} {{ __('subscriptions.sessions_per_month') }})</option>
                        @endforeach
                    </select>
                    @error('package_id') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">{{ __('subscriptions.select_billing_cycle') }}</label>
                    <select wire:model.live="billing_cycle" class="w-full rounded-lg border-gray-300">
                        <option value="monthly">{{ __('enums.billing_cycle.monthly') }}</option>
                        <option value="quarterly">{{ __('enums.billing_cycle.quarterly') }}</option>
                        <option value="yearly">{{ __('enums.billing_cycle.yearly') }}</option>
                    </select>
                </div>

                <div class="bg-gray-50 border border-gray-200 rounded-lg p-4 space-y-3">
                    <div class="flex items-center justify-between">
                        <span class="text-sm text-gray-600">{{ __('subscriptions.package_price_label') }}</span>
                        <span class="text-lg font-bold text-gray-900">{{ number_format($amount, 2) }} {{ auth()->user()->academy?->currency?->value ?? 'SAR' }}</span>
                    </div>
                    <div>
                        <label class="block text-sm text-gray-600 mb-1">{{ __('subscriptions.discount_label') }}</label>
                        <input type="number" wire:model.live.debounce.300ms="discount" step="1" min="0" max="{{ $amount }}" class="w-full rounded-lg border-gray-300 text-sm" placeholder="0">
                    </div>
                    @if ($discount > 0)
                        <div class="flex items-center justify-between pt-2 border-t border-gray-200">
                            <span class="text-sm font-semibold text-gray-700">{{ __('subscriptions.final_price_label') }}</span>
                            <span class="text-lg font-bold text-green-600">{{ number_format($this->finalPrice, 2) }} {{ auth()->user()->academy?->currency?->value ?? 'SAR' }}</span>
                        </div>
                    @endif
                </div>
            </div>
        @endif

        {{-- ═══ STEP 3 (Individual/Academic): Payment ═══ --}}
        @if ($currentStep === 3 && $subscription_type !== 'quran_group')
            <h2 class="text-lg font-semibold mb-4">{{ __('subscriptions.wizard_step3_title') }}</h2>
            <div class="space-y-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">{{ __('subscriptions.payment_source_label') }}</label>
                    <div class="flex gap-3">
                        <label class="flex-1 cursor-pointer">
                            <input type="radio" wire:model.live="payment_source" value="outside" class="peer sr-only">
                            <div class="text-center p-3 rounded-lg border-2 border-gray-200 peer-checked:border-green-600 peer-checked:bg-green-50 transition-all">
                                <i class="ri-hand-coin-line text-lg text-green-600 mb-1"></i>
                                <div class="text-sm font-medium">{{ __('subscriptions.paid_outside') }}</div>
                                <div class="text-xs text-gray-500 mt-0.5">{{ __('subscriptions.paid_outside_desc') }}</div>
                            </div>
                        </label>
                        <label class="flex-1 cursor-pointer">
                            <input type="radio" wire:model.live="payment_source" value="inside" class="peer sr-only">
                            <div class="text-center p-3 rounded-lg border-2 border-gray-200 peer-checked:border-blue-600 peer-checked:bg-blue-50 transition-all">
                                <i class="ri-bank-card-line text-lg text-blue-600 mb-1"></i>
                                <div class="text-sm font-medium">{{ __('subscriptions.paid_inside') }}</div>
                                <div class="text-xs text-gray-500 mt-0.5">{{ __('subscriptions.paid_inside_desc') }}</div>
                            </div>
                        </label>
                    </div>
                </div>
                @if ($payment_source === 'outside')
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">{{ __('subscriptions.payment_reference_label') }}</label>
                        <input type="text" wire:model="payment_reference" class="w-full rounded-lg border-gray-300" placeholder="{{ __('subscriptions.payment_reference_placeholder') }}">
                    </div>
                @else
                    <div class="p-3 bg-blue-50 border border-blue-200 rounded-lg text-sm text-blue-700">
                        <i class="ri-information-line"></i> {{ __('subscriptions.pending_payment_notice') }}
                    </div>
                @endif
            </div>
        @endif

        {{-- ═══ STEP 4: Details (individual Quran only) ═══ --}}
        @if ($currentStep === 4 && $subscription_type !== 'quran_group')
            <h2 class="text-lg font-semibold mb-4">{{ __('subscriptions.wizard_step4_title') }}</h2>
            <div class="space-y-4">
                @if (in_array($subscription_type, ['quran_individual']))
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">{{ __('public.booking.quran.form.current_level_label') }}</label>
                        <select wire:model="memorization_level" class="w-full rounded-lg border-gray-300">
                            @foreach (\App\Enums\QuranLearningLevel::cases() as $level)
                                <option value="{{ $level->value }}">{{ $level->label() }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">{{ __('subscriptions.specialization_label') }}</label>
                        <select wire:model="specialization" class="w-full rounded-lg border-gray-300">
                            <option value="memorization">{{ __('subscriptions.specialization_memorization') }}</option>
                            <option value="recitation">{{ __('subscriptions.specialization_recitation') }}</option>
                            <option value="tajweed">{{ __('subscriptions.specialization_tajweed') }}</option>
                            <option value="complete">{{ __('subscriptions.specialization_complete') }}</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">{{ __('public.booking.quran.form.learning_goals_label') }}</label>
                        <div class="space-y-2">
                            @foreach (\App\Enums\LearningGoal::cases() as $goal)
                                <label class="flex items-center gap-2 cursor-pointer">
                                    <input type="checkbox" wire:model="learning_goals" value="{{ $goal->value }}" class="rounded border-gray-300 text-primary-600">
                                    <span class="text-sm text-gray-700">{{ $goal->label() }}</span>
                                </label>
                            @endforeach
                        </div>
                    </div>
                @endif
            </div>
        @endif

        {{-- ════════════════════════════════════════════
             Navigation Buttons (with loading states)
        ════════════════════════════════════════════ --}}
        <div class="flex justify-between mt-6 pt-4 border-t">
            @if ($currentStep > 1)
                <button wire:click="previousStep" wire:loading.attr="disabled" wire:target="nextStep,previousStep,submit"
                    class="flex items-center gap-2 px-5 py-2.5 bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200 transition-colors disabled:opacity-50 disabled:cursor-not-allowed">
                    <span wire:loading.remove wire:target="previousStep">{{ __('subscriptions.previous_step') }}</span>
                    <span wire:loading wire:target="previousStep" class="flex items-center gap-2">
                        <i class="ri-loader-4-line animate-spin"></i>
                        {{ __('subscriptions.previous_step') }}
                    </span>
                </button>
            @else
                <div></div>
            @endif

            @if ($currentStep < $this->totalSteps)
                <button wire:click="nextStep" wire:loading.attr="disabled" wire:target="nextStep,previousStep,submit"
                    class="flex items-center gap-2 px-5 py-2.5 bg-primary-600 text-white rounded-lg hover:bg-primary-700 transition-colors disabled:opacity-50 disabled:cursor-not-allowed">
                    <span wire:loading.remove wire:target="nextStep">{{ __('subscriptions.next_step') }}</span>
                    <span wire:loading wire:target="nextStep" class="flex items-center gap-2">
                        <i class="ri-loader-4-line animate-spin"></i>
                        {{ __('subscriptions.next_step') }}
                    </span>
                </button>
            @else
                <button wire:click="submit" wire:loading.attr="disabled" wire:target="nextStep,previousStep,submit"
                    class="flex items-center gap-2 px-5 py-2.5 bg-green-600 text-white rounded-lg hover:bg-green-700 transition-colors disabled:opacity-50 disabled:cursor-not-allowed">
                    <span wire:loading.remove wire:target="submit">{{ __('subscriptions.create_full_subscription') }}</span>
                    <span wire:loading wire:target="submit" class="flex items-center gap-2">
                        <i class="ri-loader-4-line animate-spin"></i>
                        {{ __('subscriptions.create_full_subscription') }}
                    </span>
                </button>
            @endif
        </div>
    </div>
</div>
