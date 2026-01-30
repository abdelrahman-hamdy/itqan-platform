<div>
    @if($show)
        <div class="fixed inset-0 z-50 overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
            <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:p-0">
                <!-- Backdrop -->
                <div
                    class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity"
                    wire:click="close"
                ></div>

                <!-- Modal Panel -->
                <div class="relative bg-white rounded-2xl text-right overflow-hidden shadow-xl transform transition-all sm:my-8 sm:max-w-2xl sm:w-full max-h-[90vh] flex flex-col">
                    <!-- Header -->
                    <div class="bg-white px-6 py-4 border-b border-gray-100">
                        <div class="flex items-center justify-between">
                            <h3 class="text-lg font-semibold text-gray-900 flex items-center gap-2">
                                <i class="ri-bank-card-line text-blue-600"></i>
                                {{ __('student.saved_payment_methods.add_modal_title') }}
                            </h3>
                            <button
                                wire:click="close"
                                class="p-2 text-gray-400 hover:text-gray-600 hover:bg-gray-100 rounded-lg transition-colors"
                            >
                                <i class="ri-close-line text-xl"></i>
                            </button>
                        </div>
                    </div>

                    <!-- Content -->
                    <div class="flex-1 overflow-auto p-6">
                        @if($isLoading)
                            <!-- Loading State -->
                            <div class="flex flex-col items-center justify-center py-12">
                                <div class="w-12 h-12 border-4 border-blue-200 border-t-blue-600 rounded-full animate-spin mb-4"></div>
                                <p class="text-gray-600">{{ __('student.saved_payment_methods.loading_form') }}</p>
                            </div>
                        @elseif($errorMessage)
                            <!-- Error State -->
                            <div class="flex flex-col items-center justify-center py-8">
                                <div class="w-16 h-16 bg-red-100 rounded-full flex items-center justify-center mb-4">
                                    <i class="ri-error-warning-line text-3xl text-red-600"></i>
                                </div>
                                <p class="text-gray-900 font-medium mb-2">{{ __('student.saved_payment_methods.error_title') }}</p>
                                <p class="text-gray-600 text-sm text-center mb-6 max-w-sm">{{ $errorMessage }}</p>
                                <button
                                    wire:click="retry"
                                    class="inline-flex items-center gap-2 px-4 py-2 bg-blue-600 text-white rounded-xl hover:bg-blue-700 transition-colors text-sm font-medium"
                                >
                                    <i class="ri-refresh-line"></i>
                                    {{ __('student.saved_payment_methods.retry') }}
                                </button>
                            </div>
                        @elseif($iframeUrl)
                            <!-- Iframe Container -->
                            <div class="relative">
                                <!-- Security Notice -->
                                <div class="mb-4 p-3 bg-green-50 border border-green-200 rounded-lg">
                                    <div class="flex items-center gap-2 text-sm text-green-700">
                                        <i class="ri-shield-check-line text-lg"></i>
                                        <span>{{ __('student.saved_payment_methods.security_notice') }}</span>
                                    </div>
                                </div>

                                <!-- Payment Iframe -->
                                <div class="bg-gray-50 rounded-xl border border-gray-200 overflow-hidden">
                                    <iframe
                                        src="{{ $iframeUrl }}"
                                        class="w-full min-h-[550px]"
                                        frameborder="0"
                                        allow="payment"
                                        id="payment-iframe"
                                    ></iframe>
                                </div>

                                <!-- Info Text -->
                                <div class="mt-4 text-center">
                                    <p class="text-xs text-gray-500">
                                        <i class="ri-lock-line"></i>
                                        {{ __('student.saved_payment_methods.paymob_notice') }}
                                    </p>
                                </div>
                            </div>
                        @else
                            <!-- Manual Card Input (Fallback) -->
                            <div class="space-y-4">
                                <div class="p-4 bg-amber-50 border border-amber-200 rounded-lg">
                                    <div class="flex items-start gap-3">
                                        <i class="ri-information-line text-xl text-amber-600 shrink-0"></i>
                                        <div class="text-sm text-amber-800">
                                            <p class="font-medium mb-1">{{ __('student.saved_payment_methods.add_card_info_title') }}</p>
                                            <p>{{ __('student.saved_payment_methods.add_card_info_message') }}</p>
                                        </div>
                                    </div>
                                </div>

                                <button
                                    wire:click="close"
                                    class="w-full inline-flex items-center justify-center gap-2 px-4 py-3 bg-gray-100 text-gray-700 rounded-xl hover:bg-gray-200 transition-colors font-medium"
                                >
                                    {{ __('student.saved_payment_methods.understood') }}
                                </button>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    @endif

    <!-- PostMessage Handler for iframe communication -->
    @if($iframeUrl)
        <script>
            window.addEventListener('message', function(event) {
                // Verify origin (should be Paymob domain)
                if (!event.origin.includes('paymob.com') && !event.origin.includes('accept.paymob')) {
                    return;
                }

                const data = event.data;

                if (data.type === 'tokenization_success' || data.success === true) {
                    // Card tokenized successfully
                    Livewire.dispatch('tokenization-success', {
                        data: {
                            token: data.token || data.card_token,
                            brand: data.card_brand || data.brand,
                            last_four: data.masked_pan || data.last_four,
                            expiry_month: data.expiry_month,
                            expiry_year: data.expiry_year,
                        }
                    });
                } else if (data.type === 'tokenization_failed' || data.success === false) {
                    // Tokenization failed
                    Livewire.dispatch('tokenization-failed', {
                        error: data.message || data.error || '{{ __('student.saved_payment_methods.tokenization_failed') }}'
                    });
                }
            });
        </script>
    @endif
</div>
