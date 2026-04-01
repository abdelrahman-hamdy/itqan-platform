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

    {{-- Step Indicator --}}
    <div class="flex items-center gap-2 mb-8">
        @for ($i = 1; $i <= $totalSteps; $i++)
            <div class="flex items-center">
                <div class="w-8 h-8 rounded-full flex items-center justify-center text-sm font-bold {{ $currentStep >= $i ? 'bg-primary-600 text-white' : 'bg-gray-200 text-gray-500' }}">{{ $i }}</div>
                @if ($i < $totalSteps)<div class="w-8 h-0.5 {{ $currentStep > $i ? 'bg-primary-600' : 'bg-gray-200' }}"></div>@endif
            </div>
        @endfor
    </div>

    <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">

        {{-- ═══ STEP 1 ═══ --}}
        @if ($currentStep === 1)
            <h2 class="text-lg font-semibold mb-4">{{ __('subscriptions.wizard_step1_title') }}</h2>
            <div class="space-y-5">

                {{-- Type --}}
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">{{ __('subscriptions.subscription_type_label') }}</label>
                    <select wire:model.live="subscription_type" class="w-full rounded-lg border-gray-300">
                        <option value="quran_individual">{{ __('subscriptions.type_quran_individual') }}</option>
                        <option value="quran_group">{{ __('subscriptions.type_quran_group') }}</option>
                        <option value="academic">{{ __('subscriptions.type_academic') }}</option>
                    </select>
                </div>

                {{-- Student --}}
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">{{ __('subscriptions.student_label') }}</label>
                    @if ($student_id)
                        @php $studentUser = \App\Models\User::find($student_id); @endphp
                        @if ($studentUser)
                            <div class="flex items-center gap-3 p-3 bg-gray-50 border border-gray-200 rounded-lg">
                                <x-avatar :user="$studentUser" size="xs" userType="student" />
                                <div class="flex-1 min-w-0">
                                    <div class="font-semibold text-gray-900 truncate">{{ $selectedStudentName }}</div>
                                    <div class="text-xs text-gray-500 truncate">{{ $selectedStudentEmail }}</div>
                                </div>
                                <button type="button" wire:click="clearStudent" class="p-1.5 text-gray-400 hover:text-red-500 hover:bg-red-50 rounded-lg"><i class="ri-close-line text-lg"></i></button>
                            </div>
                        @endif
                    @else
                        <div class="relative">
                            <input type="text" wire:model.live.debounce.300ms="student_search" class="w-full rounded-lg border-gray-300 pe-10" placeholder="{{ __('subscriptions.search_student_placeholder') }}">
                            <div class="absolute inset-y-0 end-0 flex items-center pe-3 pointer-events-none"><i class="ri-search-line text-gray-400"></i></div>
                        </div>
                        @if (count($searchResults) > 0)
                            <div class="mt-1 bg-white border rounded-lg shadow-lg max-h-48 overflow-y-auto z-10 relative">
                                @foreach ($searchResults as $rid)
                                    @php $ru = \App\Models\User::find($rid); @endphp
                                    @if ($ru)
                                        <button wire:click="selectStudent({{ $rid }})" class="w-full text-start px-3 py-2.5 hover:bg-gray-50 flex items-center gap-3 border-b border-gray-50 last:border-0">
                                            <x-avatar :user="$ru" size="xs" userType="student" />
                                            <div class="min-w-0">
                                                <div class="text-sm font-medium text-gray-900 truncate">{{ trim($ru->first_name.' '.$ru->last_name) }}</div>
                                                <div class="text-xs text-gray-500 truncate">{{ $ru->email }}</div>
                                            </div>
                                        </button>
                                    @endif
                                @endforeach
                            </div>
                        @endif
                    @endif
                    @error('student_id') <span class="text-red-500 text-sm mt-1 block">{{ $message }}</span> @enderror
                </div>

                {{-- Teacher --}}
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">{{ __('subscriptions.teacher_label') }}</label>
                    @php
                        $teacherUserType = in_array($subscription_type, ['quran_individual', 'quran_group']) ? 'quran_teacher' : 'academic_teacher';
                    @endphp
                    @if ($teacher_id)
                        @php
                            $tp = in_array($subscription_type, ['quran_individual', 'quran_group'])
                                ? \App\Models\QuranTeacherProfile::with('user')->find($teacher_id)
                                : \App\Models\AcademicTeacherProfile::with('user')->find($teacher_id);
                            $tu = $tp?->user;
                        @endphp
                        @if ($tu)
                            <div class="flex items-center gap-3 p-3 bg-gray-50 border border-gray-200 rounded-lg">
                                <x-avatar :user="$tu" size="xs" :userType="$teacherUserType" />
                                <div class="flex-1 min-w-0">
                                    <div class="font-semibold text-gray-900 truncate">{{ trim($tu->first_name.' '.$tu->last_name) }}</div>
                                </div>
                                <button type="button" wire:click="clearTeacher" class="p-1.5 text-gray-400 hover:text-red-500 hover:bg-red-50 rounded-lg"><i class="ri-close-line text-lg"></i></button>
                            </div>
                        @endif
                    @else
                        <div class="border rounded-lg overflow-hidden">
                            <div class="p-2 border-b bg-gray-50">
                                <input type="text" wire:model.live.debounce.200ms="teacher_search" class="w-full rounded-lg border-gray-300 text-sm" placeholder="{{ __('subscriptions.search_teacher_placeholder') }}">
                            </div>
                            <div class="max-h-48 overflow-y-auto p-1 space-y-0.5">
                                @forelse ($filteredTeachers as $t)
                                    @php
                                        $tpModel = in_array($subscription_type, ['quran_individual', 'quran_group'])
                                            ? \App\Models\QuranTeacherProfile::with('user')->find($t['id'])
                                            : \App\Models\AcademicTeacherProfile::with('user')->find($t['id']);
                                        $tUser = $tpModel?->user;
                                    @endphp
                                    <button type="button" wire:click="selectTeacher({{ $t['id'] }})" class="w-full flex items-center gap-3 p-2.5 rounded-lg hover:bg-gray-50 text-start">
                                        @if ($tUser) <x-avatar :user="$tUser" size="xs" :userType="$teacherUserType" /> @endif
                                        <span class="text-sm font-medium text-gray-900">{{ $t['name'] }}</span>
                                    </button>
                                @empty
                                    <div class="p-3 text-sm text-gray-500 text-center">{{ __('subscriptions.no_teachers_available') }}</div>
                                @endforelse
                            </div>
                        </div>
                    @endif
                    @error('teacher_id') <span class="text-red-500 text-sm mt-1 block">{{ $message }}</span> @enderror
                </div>

                {{-- Group Circle (only for quran_group) --}}
                @if ($subscription_type === 'quran_group' && $teacher_id)
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">{{ __('subscriptions.select_circle') }}</label>
                        <select wire:model="quran_circle_id" class="w-full rounded-lg border-gray-300">
                            <option value="">{{ __('subscriptions.select_circle') }}</option>
                            @foreach ($availableCircles as $circle)
                                <option value="{{ $circle['id'] }}">{{ $circle['name'] }} ({{ $circle['spots'] }} {{ __('subscriptions.spots_available') }})</option>
                            @endforeach
                        </select>
                        @error('quran_circle_id') <span class="text-red-500 text-sm mt-1 block">{{ $message }}</span> @enderror
                    </div>
                @endif
            </div>
        @endif

        {{-- ═══ STEP 2 ═══ --}}
        @if ($currentStep === 2)
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

        {{-- ═══ STEP 3: Payment ═══ --}}
        @if ($currentStep === 3)
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
                        <label class="block text-sm font-medium text-gray-700 mb-1">{{ __('subscriptions.payment_method_label') }}</label>
                        <select wire:model="payment_method" class="w-full rounded-lg border-gray-300">
                            <option value="cash">{{ __('subscriptions.payment_method_cash') }}</option>
                            <option value="bank_transfer">{{ __('subscriptions.payment_method_bank') }}</option>
                            <option value="mada">مدى</option>
                            <option value="stc_pay">STC Pay</option>
                            <option value="apple_pay">Apple Pay</option>
                            <option value="urpay">UrPay</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">{{ __('subscriptions.payment_reference_label') }}</label>
                        <input type="text" wire:model="payment_reference" class="w-full rounded-lg border-gray-300" placeholder="{{ __('subscriptions.payment_reference_placeholder') }}">
                    </div>
                @else
                    <div class="p-3 bg-blue-50 border border-blue-200 rounded-lg text-sm text-blue-700">
                        <i class="ri-information-line"></i>
                        {{ __('subscriptions.pending_payment_notice') }}
                    </div>
                @endif
            </div>
        @endif

        {{-- ═══ STEP 4: Details ═══ --}}
        @if ($currentStep === 4)
            <h2 class="text-lg font-semibold mb-4">{{ __('subscriptions.wizard_step4_title') }}</h2>
            <div class="space-y-4">
                @if (in_array($subscription_type, ['quran_individual', 'quran_group']))
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">{{ __('subscriptions.memorization_level_label') }}</label>
                        <select wire:model="memorization_level" class="w-full rounded-lg border-gray-300">
                            <option value="beginner">{{ __('subscriptions.level_beginner') }}</option>
                            <option value="intermediate">{{ __('subscriptions.level_intermediate') }}</option>
                            <option value="advanced">{{ __('subscriptions.level_advanced') }}</option>
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
                @endif
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">{{ __('subscriptions.learning_goals_label') }}</label>
                    <textarea wire:model="learning_goals" rows="3" class="w-full rounded-lg border-gray-300" placeholder="{{ __('subscriptions.learning_goals_placeholder') }}"></textarea>
                </div>
            </div>
        @endif

        {{-- Navigation --}}
        <div class="flex justify-between mt-6 pt-4 border-t">
            @if ($currentStep > 1)
                <button wire:click="previousStep" class="px-4 py-2 bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200">{{ __('subscriptions.previous_step') }}</button>
            @else
                <div></div>
            @endif
            @if ($currentStep < $totalSteps)
                <button wire:click="nextStep" class="px-4 py-2 bg-primary-600 text-white rounded-lg hover:bg-primary-700">{{ __('subscriptions.next_step') }}</button>
            @else
                <button wire:click="submit" class="px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700">{{ __('subscriptions.create_full_subscription') }}</button>
            @endif
        </div>
    </div>
</div>
