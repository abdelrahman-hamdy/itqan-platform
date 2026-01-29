<div>
    <!-- Messages -->
    @if($successMessage)
        <div class="mb-4 p-4 bg-green-50 border border-green-200 rounded-xl">
            <div class="flex items-center gap-3">
                <i class="ri-checkbox-circle-line text-xl text-green-600"></i>
                <span class="text-green-800">{{ $successMessage }}</span>
            </div>
        </div>
    @endif

    @if($errorMessage)
        <div class="mb-4 p-4 bg-red-50 border border-red-200 rounded-xl">
            <div class="flex items-center gap-3">
                <i class="ri-error-warning-line text-xl text-red-600"></i>
                <span class="text-red-800">{{ $errorMessage }}</span>
            </div>
        </div>
    @endif

    <!-- Section Header -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden mb-6">
        <div class="p-4 md:p-6 border-b border-gray-100">
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                <div>
                    <h3 class="text-lg font-semibold text-gray-900 flex items-center gap-2">
                        <i class="ri-bank-card-line text-blue-600"></i>
                        {{ __('student.saved_payment_methods.title') }}
                    </h3>
                    <p class="text-sm text-gray-500 mt-1">{{ __('student.saved_payment_methods.description') }}</p>
                </div>

                <button
                    wire:click="openAddModal"
                    class="inline-flex items-center justify-center gap-2 px-4 py-2.5 bg-blue-600 text-white rounded-xl hover:bg-blue-700 transition-colors text-sm font-medium"
                >
                    <i class="ri-add-line"></i>
                    {{ __('student.saved_payment_methods.add_new_card') }}
                </button>
            </div>
        </div>

        <!-- Payment Methods List -->
        <div class="divide-y divide-gray-100">
            @forelse($this->paymentMethods as $method)
                <div class="p-4 md:p-6 hover:bg-gray-50 transition-colors">
                    <div class="flex flex-col sm:flex-row sm:items-center gap-4">
                        <!-- Card Icon & Info -->
                        <div class="flex items-center gap-4 flex-1">
                            <!-- Brand Icon -->
                            <div class="w-14 h-10 bg-gray-100 rounded-lg flex items-center justify-center">
                                @if($method->brand === 'visa')
                                    <i class="ri-visa-line text-2xl text-blue-600"></i>
                                @elseif($method->brand === 'mastercard')
                                    <i class="ri-mastercard-line text-2xl text-orange-600"></i>
                                @else
                                    <i class="{{ $method->getBrandIcon() }} text-2xl text-gray-600"></i>
                                @endif
                            </div>

                            <!-- Card Details -->
                            <div class="flex-1">
                                <div class="flex items-center gap-2 mb-1">
                                    <span class="font-semibold text-gray-900">
                                        {{ $method->getBrandDisplayName() }}
                                    </span>
                                    <span class="text-gray-600">
                                        {{ $method->getMaskedNumber() }}
                                    </span>
                                    @if($method->is_default)
                                        <span class="px-2 py-0.5 bg-blue-100 text-blue-700 text-xs font-medium rounded-full">
                                            {{ __('student.saved_payment_methods.default_badge') }}
                                        </span>
                                    @endif
                                </div>

                                <div class="flex flex-wrap items-center gap-3 text-sm text-gray-500">
                                    @if($method->getExpiryDisplay())
                                        <span class="flex items-center gap-1">
                                            <i class="ri-calendar-line"></i>
                                            {{ __('student.saved_payment_methods.expires_at') }} {{ $method->getExpiryDisplay() }}
                                        </span>
                                    @endif

                                    @if($method->holder_name)
                                        <span class="flex items-center gap-1">
                                            <i class="ri-user-line"></i>
                                            {{ $method->holder_name }}
                                        </span>
                                    @endif

                                    @if($method->last_used_at)
                                        <span class="flex items-center gap-1">
                                            <i class="ri-time-line"></i>
                                            {{ __('student.saved_payment_methods.last_used') }} {{ $method->last_used_at->diffForHumans() }}
                                        </span>
                                    @endif
                                </div>
                            </div>
                        </div>

                        <!-- Actions -->
                        <div class="flex items-center gap-2">
                            @if(!$method->is_default)
                                <button
                                    wire:click="setAsDefault({{ $method->id }})"
                                    wire:loading.attr="disabled"
                                    class="inline-flex items-center gap-1 px-3 py-2 text-sm font-medium text-blue-600 hover:bg-blue-50 rounded-lg transition-colors"
                                >
                                    <i class="ri-star-line"></i>
                                    {{ __('student.saved_payment_methods.set_default') }}
                                </button>
                            @endif

                            <button
                                wire:click="confirmDelete({{ $method->id }})"
                                class="inline-flex items-center gap-1 px-3 py-2 text-sm font-medium text-red-600 hover:bg-red-50 rounded-lg transition-colors"
                            >
                                <i class="ri-delete-bin-line"></i>
                                {{ __('student.saved_payment_methods.delete') }}
                            </button>
                        </div>
                    </div>

                    <!-- Expiry Warning -->
                    @if($method->isExpired())
                        <div class="mt-3 p-3 bg-red-50 border border-red-200 rounded-lg">
                            <div class="flex items-center gap-2 text-sm text-red-700">
                                <i class="ri-error-warning-line"></i>
                                <span>{{ __('student.saved_payment_methods.expired_warning') }}</span>
                            </div>
                        </div>
                    @endif
                </div>
            @empty
                <!-- Empty State -->
                <div class="p-8 md:p-12 text-center">
                    <div class="w-16 h-16 mx-auto mb-4 bg-gray-100 rounded-full flex items-center justify-center">
                        <i class="ri-bank-card-line text-3xl text-gray-400"></i>
                    </div>
                    <h4 class="text-lg font-medium text-gray-900 mb-2">{{ __('student.saved_payment_methods.no_cards_title') }}</h4>
                    <p class="text-sm text-gray-500 mb-6 max-w-sm mx-auto">
                        {{ __('student.saved_payment_methods.no_cards_description') }}
                    </p>
                    <button
                        wire:click="openAddModal"
                        class="inline-flex items-center gap-2 px-6 py-3 bg-blue-600 text-white rounded-xl hover:bg-blue-700 transition-colors font-medium"
                    >
                        <i class="ri-add-line"></i>
                        {{ __('student.saved_payment_methods.add_new_card') }}
                    </button>
                </div>
            @endforelse
        </div>
    </div>

    <!-- Delete Confirmation Modal -->
    @if($showDeleteModal)
        <div class="fixed inset-0 z-50 overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
            <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:p-0">
                <!-- Backdrop -->
                <div
                    class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity"
                    wire:click="cancelDelete"
                ></div>

                <!-- Modal Panel -->
                <div class="relative bg-white rounded-2xl text-right overflow-hidden shadow-xl transform transition-all sm:my-8 sm:max-w-lg sm:w-full">
                    <div class="bg-white px-6 py-5">
                        <div class="flex items-start gap-4">
                            <div class="flex-shrink-0 w-12 h-12 bg-red-100 rounded-full flex items-center justify-center">
                                <i class="ri-delete-bin-line text-xl text-red-600"></i>
                            </div>
                            <div class="flex-1">
                                <h3 class="text-lg font-semibold text-gray-900 mb-2">
                                    {{ __('student.saved_payment_methods.delete_modal_title') }}
                                </h3>
                                <p class="text-sm text-gray-600">
                                    {{ __('student.saved_payment_methods.delete_modal_message') }}
                                </p>
                            </div>
                        </div>
                    </div>

                    <div class="bg-gray-50 px-6 py-4 flex flex-row-reverse gap-3">
                        <button
                            wire:click="deletePaymentMethod"
                            wire:loading.attr="disabled"
                            class="inline-flex items-center gap-2 px-4 py-2.5 bg-red-600 text-white rounded-xl hover:bg-red-700 transition-colors text-sm font-medium disabled:opacity-50"
                        >
                            <span wire:loading.remove wire:target="deletePaymentMethod">
                                <i class="ri-delete-bin-line"></i>
                                {{ __('student.saved_payment_methods.delete_confirm') }}
                            </span>
                            <span wire:loading wire:target="deletePaymentMethod" class="flex items-center gap-2">
                                <i class="ri-loader-4-line animate-spin"></i>
                                {{ __('student.saved_payment_methods.delete_loading') }}
                            </span>
                        </button>

                        <button
                            wire:click="cancelDelete"
                            class="inline-flex items-center px-4 py-2.5 bg-white border border-gray-300 text-gray-700 rounded-xl hover:bg-gray-50 transition-colors text-sm font-medium"
                        >
                            {{ __('student.saved_payment_methods.cancel') }}
                        </button>
                    </div>
                </div>
            </div>
        </div>
    @endif

    <!-- Add Payment Method Modal -->
    @if($showAddModal)
        <livewire:payment.add-payment-method-modal :show="true" />
    @endif
</div>
