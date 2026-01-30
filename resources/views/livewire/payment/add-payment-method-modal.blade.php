<div>
    {{-- Error Message (if any) --}}
    @if($errorMessage)
        <div class="fixed inset-0 z-50 overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
            <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:p-0">
                <!-- Backdrop -->
                <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity"></div>

                <!-- Modal Panel -->
                <div class="relative bg-white rounded-2xl text-right overflow-hidden shadow-xl transform transition-all sm:my-8 w-full max-w-md p-6">
                    <div class="flex flex-col items-center justify-center py-4">
                        <div class="w-16 h-16 bg-red-100 rounded-full flex items-center justify-center mb-4">
                            <i class="ri-error-warning-line text-3xl text-red-600"></i>
                        </div>
                        <p class="text-gray-900 font-medium mb-2">{{ __('student.saved_payment_methods.error_title') }}</p>
                        <p class="text-gray-600 text-sm text-center mb-6 max-w-sm">{{ $errorMessage }}</p>
                        <div class="flex gap-3">
                            <button
                                wire:click="initiateAddCard"
                                class="inline-flex items-center gap-2 px-4 py-2 bg-blue-600 text-white rounded-xl hover:bg-blue-700 transition-colors text-sm font-medium"
                            >
                                <i class="ri-refresh-line"></i>
                                {{ __('student.saved_payment_methods.retry') }}
                            </button>
                            <button
                                wire:click="$set('errorMessage', null)"
                                class="inline-flex items-center gap-2 px-4 py-2 bg-gray-100 text-gray-700 rounded-xl hover:bg-gray-200 transition-colors text-sm font-medium"
                            >
                                {{ __('student.saved_payment_methods.cancel') }}
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    @endif

    {{-- Loading Overlay --}}
    @if($isLoading)
        <div class="fixed inset-0 z-50 flex items-center justify-center bg-gray-500 bg-opacity-75">
            <div class="bg-white rounded-2xl p-8 flex flex-col items-center">
                <div class="w-12 h-12 border-4 border-blue-200 border-t-blue-600 rounded-full animate-spin mb-4"></div>
                <p class="text-gray-600">{{ __('student.saved_payment_methods.redirecting_to_payment') }}</p>
            </div>
        </div>
    @endif
</div>
