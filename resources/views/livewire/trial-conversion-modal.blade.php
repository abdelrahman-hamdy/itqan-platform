<div x-data="{
        handleSuccess(event) {
            // Show success notification
            if (window.showToast) {
                window.showToast(event.detail.message, 'success');
            }
        },
        handleError(event) {
            // Show error notification
            if (window.showToast) {
                window.showToast(event.detail.message, 'error');
            }
        }
     }"
     x-on:trial-converted-success.window="handleSuccess($event)"
     x-on:trial-converted-error.window="handleError($event)">

    @if($showModal)
    <!-- Modal Overlay -->
    <div class="fixed inset-0 z-50 overflow-y-auto" wire:key="trial-conversion-modal-{{ $trialRequestId }}">
        <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:block sm:p-0">
            <!-- Background Overlay -->
            <div class="fixed inset-0 transition-opacity bg-gray-900 bg-opacity-75" wire:click="closeModal"></div>

            <!-- Modal Container -->
            <div class="inline-block w-full max-w-2xl overflow-hidden text-start align-middle transition-all transform bg-white rounded-2xl shadow-2xl sm:my-8">

                @if($showSuccess)
                    <!-- Success State -->
                    <div class="p-8 text-center">
                        <div class="w-20 h-20 mx-auto mb-6 bg-green-100 rounded-full flex items-center justify-center">
                            <i class="ri-check-line text-4xl text-green-600"></i>
                        </div>
                        <h3 class="text-2xl font-bold text-gray-900 mb-2">{{ __('components.trial_conversion.success_title') }}</h3>
                        <p class="text-gray-600 mb-6">{{ __('components.trial_conversion.success_message') }}</p>

                        <div class="flex justify-center gap-4">
                            <button wire:click="goToPayment"
                                    class="px-6 py-3 bg-green-600 hover:bg-green-700 text-white font-medium rounded-xl transition flex items-center gap-2">
                                <i class="ri-bank-card-line"></i>
                                {{ __('components.trial_conversion.complete_payment') }}
                            </button>
                            <button wire:click="closeModal"
                                    class="px-6 py-3 bg-gray-200 hover:bg-gray-300 text-gray-700 font-medium rounded-xl transition">
                                {{ __('components.trial_conversion.later') }}
                            </button>
                        </div>
                    </div>

                @elseif($errorMessage && !$trialRequest)
                    <!-- Error State -->
                    <div class="p-8 text-center">
                        <div class="w-20 h-20 mx-auto mb-6 bg-red-100 rounded-full flex items-center justify-center">
                            <i class="ri-error-warning-line text-4xl text-red-600"></i>
                        </div>
                        <h3 class="text-xl font-bold text-gray-900 mb-2">{{ __('components.trial_conversion.not_available') }}</h3>
                        <p class="text-gray-600 mb-6">{{ $errorMessage }}</p>
                        <button wire:click="closeModal"
                                class="px-6 py-3 bg-gray-200 hover:bg-gray-300 text-gray-700 font-medium rounded-xl transition">
                            {{ __('components.trial_conversion.close') }}
                        </button>
                    </div>

                @else
                    <!-- Modal Header -->
                    <div class="bg-gradient-to-r from-emerald-500 to-teal-500 px-6 py-4">
                        <div class="flex items-center justify-between">
                            <h3 class="text-xl font-bold text-white flex items-center gap-2">
                                <i class="ri-vip-crown-line text-2xl"></i>
                                <span>{{ __('components.trial_conversion.modal_title') }}</span>
                            </h3>
                            <button wire:click="closeModal" class="text-white hover:text-gray-200 transition">
                                <i class="ri-close-line text-2xl"></i>
                            </button>
                        </div>
                    </div>

                    <!-- Modal Body -->
                    <div class="px-6 py-6 max-h-[70vh] overflow-y-auto">
                        <!-- Trial Info Summary -->
                        @if($trialRequest)
                        <div class="bg-gray-50 rounded-xl p-4 mb-6">
                            <div class="flex items-center gap-4">
                                <div class="w-12 h-12 bg-emerald-100 rounded-full flex items-center justify-center">
                                    <i class="ri-user-star-line text-xl text-emerald-600"></i>
                                </div>
                                <div class="flex-1">
                                    <p class="font-bold text-gray-900">{{ $trialRequest->teacher?->user?->name ?? __('components.trial_conversion.teacher_default') }}</p>
                                    <p class="text-sm text-gray-600">{{ __('components.trial_conversion.quran_teacher') }}</p>
                                </div>
                                @if($trialRequest->rating)
                                <div class="text-center">
                                    <div class="flex items-center gap-1 text-amber-500">
                                        @for($i = 1; $i <= 5; $i++)
                                            <i class="ri-star-{{ $i <= $trialRequest->rating ? 'fill' : 'line' }}"></i>
                                        @endfor
                                    </div>
                                    <p class="text-xs text-gray-500">{{ __('components.trial_conversion.session_rating') }}</p>
                                </div>
                                @endif
                            </div>
                        </div>
                        @endif

                        <!-- Error Message -->
                        @if($errorMessage)
                        <div class="bg-red-50 border border-red-200 rounded-xl p-4 mb-6">
                            <div class="flex items-center gap-3">
                                <i class="ri-error-warning-line text-xl text-red-500"></i>
                                <p class="text-red-700">{{ $errorMessage }}</p>
                            </div>
                        </div>
                        @endif

                        <!-- Package Selection -->
                        <div class="mb-6">
                            <label class="block text-sm font-bold text-gray-900 mb-3">
                                <i class="ri-gift-line me-1 text-emerald-500"></i>
                                {{ __('components.trial_conversion.select_package') }}
                            </label>

                            @if(count($packages) > 0)
                            <div class="space-y-3">
                                @foreach($packages as $package)
                                <label class="block cursor-pointer">
                                    <div class="border-2 rounded-xl p-4 transition-all
                                        {{ $selectedPackageId == $package['id'] ? 'border-emerald-500 bg-emerald-50' : 'border-gray-200 hover:border-gray-300' }}">
                                        <div class="flex items-start gap-4">
                                            <input type="radio"
                                                   wire:model.live="selectedPackageId"
                                                   value="{{ $package['id'] }}"
                                                   class="mt-1 w-5 h-5 text-emerald-600 border-gray-300 focus:ring-emerald-500">
                                            <div class="flex-1">
                                                <div class="flex items-center justify-between mb-2">
                                                    <h4 class="font-bold text-gray-900">{{ $package['name'] }}</h4>
                                                    <span class="text-emerald-600 font-bold">
                                                        {{ number_format($package['monthly_price'], 0) }} {{ $package['currency'] }}/{{ __('components.trial_conversion.per_month') }}
                                                    </span>
                                                </div>
                                                <p class="text-sm text-gray-600 mb-2">{{ $package['description'] }}</p>
                                                <div class="flex items-center gap-4 text-sm text-gray-500">
                                                    <span class="flex items-center gap-1">
                                                        <i class="ri-calendar-check-line"></i>
                                                        {{ $package['sessions_per_month'] }} {{ __('components.trial_conversion.sessions_per_month') }}
                                                    </span>
                                                    <span class="flex items-center gap-1">
                                                        <i class="ri-time-line"></i>
                                                        {{ $package['session_duration'] }} {{ __('components.trial_conversion.minutes_per_session') }}
                                                    </span>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </label>
                                @endforeach
                            </div>
                            @else
                            <div class="text-center py-8 text-gray-500">
                                <i class="ri-inbox-line text-4xl mb-2"></i>
                                <p>{{ __('components.trial_conversion.no_packages') }}</p>
                            </div>
                            @endif

                            @error('selectedPackageId')
                                <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <!-- Billing Cycle Selection -->
                        @if($selectedPackageId)
                        <div class="mb-6">
                            <label class="block text-sm font-bold text-gray-900 mb-3">
                                <i class="ri-calendar-2-line me-1 text-emerald-500"></i>
                                {{ __('components.trial_conversion.subscription_period') }}
                            </label>

                            <div class="grid grid-cols-3 gap-3">
                                @foreach($billingCycleOptions as $value => $option)
                                <label class="cursor-pointer">
                                    <input type="radio"
                                           wire:model.live="selectedBillingCycle"
                                           value="{{ $value }}"
                                           class="sr-only peer">
                                    <div class="border-2 rounded-xl p-3 text-center transition-all
                                        peer-checked:border-emerald-500 peer-checked:bg-emerald-50 border-gray-200 hover:border-gray-300">
                                        <p class="font-bold text-gray-900">{{ $option['label'] }}</p>
                                        <p class="text-xs text-gray-500">{{ $option['description'] }}</p>
                                    </div>
                                </label>
                                @endforeach
                            </div>

                            @error('selectedBillingCycle')
                                <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>
                        @endif

                        <!-- Price Summary -->
                        @if($selectedPackage && $selectedPrice)
                        <div class="bg-gradient-to-r from-emerald-500 to-teal-500 rounded-xl p-4 text-white">
                            <div class="flex items-center justify-between mb-3">
                                <span class="font-medium">{{ __('components.trial_conversion.package_label') }}</span>
                                <span>{{ $selectedPackage['name'] }}</span>
                            </div>
                            <div class="flex items-center justify-between mb-3">
                                <span class="font-medium">{{ __('components.trial_conversion.sessions_count') }}</span>
                                <span>{{ $totalSessions }} {{ __('components.trial_conversion.session_unit') }}</span>
                            </div>
                            <div class="border-t border-white/20 my-3"></div>
                            <div class="flex items-center justify-between text-lg">
                                <span class="font-bold">{{ __('components.trial_conversion.total_label') }}</span>
                                <span class="font-bold text-xl">{{ number_format($selectedPrice, 0) }} {{ $selectedPackage['currency'] }}</span>
                            </div>
                        </div>
                        @endif
                    </div>

                    <!-- Modal Footer -->
                    <div class="bg-gray-50 px-6 py-4 flex items-center justify-end gap-3">
                        <button wire:click="closeModal"
                                class="px-6 py-2.5 bg-gray-200 hover:bg-gray-300 text-gray-700 font-medium rounded-xl transition">
                            {{ __('components.trial_conversion.cancel') }}
                        </button>
                        <button wire:click="convert"
                                wire:loading.attr="disabled"
                                wire:loading.class="opacity-50 cursor-not-allowed"
                                {{ !$selectedPackageId ? 'disabled' : '' }}
                                class="px-6 py-2.5 bg-emerald-600 hover:bg-emerald-700 disabled:bg-gray-400 disabled:cursor-not-allowed text-white font-medium rounded-xl transition flex items-center gap-2">
                            <span wire:loading.remove wire:target="convert">
                                <i class="ri-arrow-left-right-line"></i>
                                {{ __('components.trial_conversion.convert_to_subscription') }}
                            </span>
                            <span wire:loading wire:target="convert" class="flex items-center gap-2">
                                <i class="ri-loader-4-line animate-spin"></i>
                                {{ __('components.trial_conversion.converting') }}
                            </span>
                        </button>
                    </div>
                @endif
            </div>
        </div>
    </div>
    @endif
</div>
