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

    {{-- Flash Messages --}}
    @if (session('error'))
        <div class="mb-4 p-4 bg-red-50 border border-red-200 rounded-lg text-red-700">{{ session('error') }}</div>
    @endif

    {{-- Step Indicator --}}
    <div class="flex items-center gap-2 mb-8">
        @for ($i = 1; $i <= $totalSteps; $i++)
            <div class="flex items-center">
                <div class="w-8 h-8 rounded-full flex items-center justify-center text-sm font-bold {{ $currentStep >= $i ? 'bg-primary-600 text-white' : 'bg-gray-200 text-gray-500' }}">
                    {{ $i }}
                </div>
                @if ($i < $totalSteps)
                    <div class="w-8 h-0.5 {{ $currentStep > $i ? 'bg-primary-600' : 'bg-gray-200' }}"></div>
                @endif
            </div>
        @endfor
    </div>

    <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">

        {{-- Step 1: Type & Student --}}
        @if ($currentStep === 1)
            <h2 class="text-lg font-semibold mb-4">{{ __('subscriptions.wizard_step1_title') }}</h2>

            <div class="space-y-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">{{ __('subscriptions.subscription_type_label') }}</label>
                    <select wire:model.live="subscription_type" class="w-full rounded-lg border-gray-300">
                        <option value="quran_individual">{{ __('subscriptions.type_quran_individual') }}</option>
                        <option value="quran_group">{{ __('subscriptions.type_quran_group') }}</option>
                        <option value="academic">{{ __('subscriptions.type_academic') }}</option>
                    </select>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">{{ __('subscriptions.student_label') }}</label>
                    <input type="text" wire:model.live.debounce.300ms="student_search" class="w-full rounded-lg border-gray-300" placeholder="{{ __('subscriptions.search_student_placeholder') }}">
                    @if (count($searchResults) > 0)
                        <div class="mt-1 bg-white border rounded-lg shadow-lg max-h-40 overflow-y-auto">
                            @foreach ($searchResults as $result)
                                <button wire:click="selectStudent({{ $result['id'] }})" class="w-full text-start px-3 py-2 hover:bg-gray-50 text-sm">
                                    {{ $result['name'] }} <span class="text-gray-400">({{ $result['email'] }})</span>
                                </button>
                            @endforeach
                        </div>
                    @endif
                    @error('student_id') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">{{ __('subscriptions.teacher_label') }}</label>
                    <select wire:model="teacher_id" class="w-full rounded-lg border-gray-300">
                        <option value="">{{ __('subscriptions.select_teacher') }}</option>
                        @foreach ($availableTeachers as $teacher)
                            <option value="{{ $teacher['id'] }}">{{ $teacher['name'] }}</option>
                        @endforeach
                    </select>
                    @error('teacher_id') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                </div>
            </div>
        @endif

        {{-- Step 2: Package & Pricing --}}
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

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">{{ __('subscriptions.amount_label') }}</label>
                        <input type="number" wire:model="amount" step="0.01" min="0" class="w-full rounded-lg border-gray-300">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">{{ __('subscriptions.discount_label') }}</label>
                        <input type="number" wire:model="discount" step="0.01" min="0" class="w-full rounded-lg border-gray-300">
                    </div>
                </div>
            </div>
        @endif

        {{-- Step 3: Payment Info --}}
        @if ($currentStep === 3)
            <h2 class="text-lg font-semibold mb-4">{{ __('subscriptions.wizard_step3_title') }}</h2>

            <div class="space-y-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">{{ __('subscriptions.payment_method_label') }}</label>
                    <select wire:model="payment_method" class="w-full rounded-lg border-gray-300">
                        <option value="manual">{{ __('subscriptions.payment_method_manual') }}</option>
                        <option value="bank_transfer">{{ __('subscriptions.payment_method_bank') }}</option>
                        <option value="cash">{{ __('subscriptions.payment_method_cash') }}</option>
                        <option value="other">{{ __('subscriptions.payment_method_other') }}</option>
                    </select>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">{{ __('subscriptions.payment_reference_label') }}</label>
                    <input type="text" wire:model="payment_reference" class="w-full rounded-lg border-gray-300" placeholder="{{ __('subscriptions.payment_reference_placeholder') }}">
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">{{ __('subscriptions.payment_notes_label') }}</label>
                    <textarea wire:model="payment_notes" rows="2" class="w-full rounded-lg border-gray-300"></textarea>
                </div>
            </div>
        @endif

        {{-- Step 4: Initial Progress --}}
        @if ($currentStep === 4)
            <h2 class="text-lg font-semibold mb-4">{{ __('subscriptions.wizard_step4_title') }}</h2>

            <div class="space-y-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">{{ __('subscriptions.consumed_sessions_label') }}</label>
                    <input type="number" wire:model="consumed_sessions" min="0" class="w-full rounded-lg border-gray-300">
                    <p class="text-xs text-gray-500 mt-1">{{ __('subscriptions.consumed_sessions_help') }}</p>
                </div>

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
            </div>
        @endif

        {{-- Navigation Buttons --}}
        <div class="flex justify-between mt-6 pt-4 border-t">
            @if ($currentStep > 1)
                <button wire:click="previousStep" class="px-4 py-2 bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200">
                    {{ __('subscriptions.previous_step') }}
                </button>
            @else
                <div></div>
            @endif

            @if ($currentStep < $totalSteps)
                <button wire:click="nextStep" class="px-4 py-2 bg-primary-600 text-white rounded-lg hover:bg-primary-700">
                    {{ __('subscriptions.next_step') }}
                </button>
            @else
                <button wire:click="submit" class="px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700">
                    {{ __('subscriptions.create_full_subscription') }}
                </button>
            @endif
        </div>
    </div>
</div>
